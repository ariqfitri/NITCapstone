<?php
// Set page title
$page_title = 'Activity Management';

// Session and auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../models/Program.php';

// Initialize database
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();
$program = new Program($appDb);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $activity_id = intval($_POST['activity_id'] ?? 0);
    
    try {
        if ($action === 'approve' && $activity_id) {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $_SESSION['success_message'] = "Activity approved successfully!";
        } elseif ($action === 'reject' && $activity_id) {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 0 WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $_SESSION['success_message'] = "Activity rejected successfully!";
        } elseif ($action === 'delete' && $activity_id) {
            // Check if activity exists before attempting delete
            $check_stmt = $appDb->prepare("SELECT activity_id FROM activities WHERE activity_id = ?");
            $check_stmt->execute([$activity_id]);
            if ($check_stmt->fetch()) {
                $stmt = $appDb->prepare("DELETE FROM activities WHERE activity_id = ?");
                $stmt->execute([$activity_id]);
                $_SESSION['success_message'] = "Activity deleted successfully!";
            } else {
                $_SESSION['error_message'] = "Activity not found!";
            }
        } elseif ($action === 'approve_all') {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE is_approved = 0");
            $stmt->execute();
            $affected = $stmt->rowCount();
            $_SESSION['success_message'] = "$affected activities approved successfully!";
        } elseif ($action === 'bulk_delete' && !empty($_POST['activity_ids'])) {
            $ids = array_map('intval', $_POST['activity_ids']);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $appDb->prepare("DELETE FROM activities WHERE activity_id IN ($placeholders)");
                $stmt->execute($ids);
                $affected = $stmt->rowCount();
                $_SESSION['success_message'] = "$affected activities deleted successfully!";
            } else {
                $_SESSION['error_message'] = "No valid activities selected for deletion!";
            }
        } elseif ($action === 'bulk_approve' && !empty($_POST['activity_ids'])) {
            $ids = array_map('intval', $_POST['activity_ids']);
            $ids = array_filter($ids, function($id) { return $id > 0; });
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE activity_id IN ($placeholders)");
                $stmt->execute($ids);
                $affected = $stmt->rowCount();
                $_SESSION['success_message'] = "$affected activities approved successfully!";
            } else {
                $_SESSION['error_message'] = "No valid activities selected for approval!";
            }
        }
        
        // Redirect to preserve GET parameters
        $redirect_url = 'activities.php';
        $params = [];
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $params['status'] = $_GET['status'];
        }
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $params['category'] = $_GET['category'];
        }
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $params['search'] = $_GET['search'];
        }
        if (isset($_GET['page']) && !empty($_GET['page'])) {
            $params['page'] = $_GET['page'];
        }
        
        if (!empty($params)) {
            $redirect_url .= '?' . http_build_query($params);
        }
        
        header('Location: ' . $redirect_url);
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$category_filter = $_GET['category'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter === 'pending') {
    $where_clauses[] = "is_approved = 0";
} elseif ($status_filter === 'approved') {
    $where_clauses[] = "is_approved = 1";
}

if ($search) {
    $where_clauses[] = "(title LIKE ? OR description LIKE ? OR suburb LIKE ? OR category LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_filter && $category_filter !== 'all') {
    $where_clauses[] = "category = ?";
    $params[] = $category_filter;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // Get activities
    $query = "SELECT * FROM activities $where_sql ORDER BY activity_id DESC LIMIT $limit OFFSET $offset";
    $stmt = $appDb->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM activities $where_sql";
    $stmt = $appDb->prepare($count_query);
    $stmt->execute($params);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $limit);
    
    // Get categories for filter
    $categories = $program->getCategories();
    
    // Get stats
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending
        FROM activities";
    $stats = $appDb->query($stats_query)->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $activities = [];
    $total_count = 0;
    $total_pages = 0;
    $categories = [];
    $stats = ['total' => 0, 'approved' => 0, 'pending' => 0];
    $error = "Error loading activities: " . $e->getMessage();
}

// Handle messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-running me-2"></i>Activity Management
                </h1>
                <div class="btn-toolbar">
                    <button type="button" class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="metric-value"><?= number_format($stats['total'] ?? 0) ?></div>
                                    <small class="opacity-75">TOTAL ACTIVITIES</small>
                                </div>
                                <i class="fas fa-list fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="metric-value"><?= number_format($stats['approved'] ?? 0) ?></div>
                                    <small class="opacity-75">APPROVED</small>
                                </div>
                                <i class="fas fa-check fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="metric-value"><?= number_format($stats['pending'] ?? 0) ?></div>
                                    <small class="opacity-75">PENDING APPROVAL</small>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Activities</option>
                                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Category</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= htmlspecialchars($category) ?>" 
                                                <?= $category_filter === $category ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($category) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="Wellbeing" <?= $category_filter === 'Wellbeing' ? 'selected' : '' ?>>Wellbeing</option>
                                    <option value="Education" <?= $category_filter === 'Education' ? 'selected' : '' ?>>Education</option>
                                    <option value="Sports" <?= $category_filter === 'Sports' ? 'selected' : '' ?>>Sports</option>
                                    <option value="Arts" <?= $category_filter === 'Arts' ? 'selected' : '' ?>>Arts</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" name="search" id="search" class="form-control" 
                                   value="<?= htmlspecialchars($search) ?>" 
                                   placeholder="Search title, description, location...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Activities List -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Activities 
                        <?php if ($total_count > 0): ?>
                            <span class="badge bg-secondary"><?= $total_count ?> results</span>
                        <?php endif; ?>
                    </h5>
                    
                    <?php if (!empty($activities)): ?>
                        <!-- Bulk Actions -->
                        <div class="d-flex gap-2">
                            <form method="POST" class="d-inline" onsubmit="return confirmBulkAction('approve')">
                                <input type="hidden" name="action" value="bulk_approve">
                                <button type="submit" class="btn btn-success btn-sm" id="bulkApproveBtn" disabled>
                                    <i class="fas fa-check me-1"></i> Approve Selected
                                </button>
                                <div id="selectedActivities"></div>
                            </form>
                            
                            <form method="POST" class="d-inline" onsubmit="return confirmBulkAction('delete')">
                                <input type="hidden" name="action" value="bulk_delete">
                                <button type="submit" class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                                    <i class="fas fa-trash me-1"></i> Delete Selected
                                </button>
                                <div id="selectedActivitiesDelete"></div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body p-0">
                    <?php if (!empty($activities)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="40">
                                            <input type="checkbox" class="form-check-input" id="selectAll">
                                            <small class="d-block text-muted mt-1" id="selectedCount">0 selected</small>
                                        </th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="form-check-input activity-checkbox" 
                                                       value="<?= $activity['activity_id'] ?>">
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                                    <?php if (!empty($activity['description'])): ?>
                                                        <br><small class="text-muted">
                                                            <?= htmlspecialchars(substr($activity['description'], 0, 100)) ?>
                                                            <?= strlen($activity['description']) > 100 ? '...' : '' ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= htmlspecialchars($activity['category'] ?: 'Unknown') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($activity['suburb'])): ?>
                                                    <?= htmlspecialchars($activity['suburb']) ?>
                                                    <?php if (!empty($activity['postcode'])): ?>
                                                        VIC <?= htmlspecialchars($activity['postcode']) ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($activity['is_approved']): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($activity['source'] ?: 'N/A') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (!$activity['is_approved']): ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="approve">
                                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                            <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="reject">
                                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                            <button type="submit" class="btn btn-warning btn-sm" title="Reject">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this activity?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">
                                            Showing <?= number_format($offset + 1) ?> to <?= number_format(min($offset + $limit, $total_count)) ?> 
                                            of <?= number_format($total_count) ?> activities
                                        </small>
                                    </div>
                                    
                                    <nav>
                                        <ul class="pagination pagination-sm mb-0">
                                            <?php
                                            $params = $_GET;
                                            unset($params['page']);
                                            $base_url = 'activities.php?' . http_build_query($params);
                                            $base_url .= empty($params) ? 'page=' : '&page=';
                                            ?>
                                            
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $base_url ?>1">First</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $base_url ?><?= $page - 1 ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $start = max(1, $page - 2);
                                            $end = min($total_pages, $page + 2);
                                            
                                            for ($i = $start; $i <= $end; $i++):
                                            ?>
                                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                    <a class="page-link" href="<?= $base_url ?><?= $i ?>"><?= $i ?></a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $base_url ?><?= $page + 1 ?>">Next</a>
                                                </li>
                                                <li class="page-item">
                                                    <a class="page-link" href="<?= $base_url ?><?= $total_pages ?>">Last</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Activities Found</h5>
                            <p class="text-muted">
                                <?php if ($search || $category_filter || $status_filter !== 'all'): ?>
                                    Try adjusting your filters or <a href="activities.php">view all activities</a>
                                <?php else: ?>
                                    Run scrapers to populate activity data
                                <?php endif; ?>
                            </p>
                            <a href="admin_scrapers.php" class="btn btn-primary">
                                <i class="fas fa-spider me-1"></i> Go to Scrapers
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.metric-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.card-body .metric-value {
    margin-bottom: 0.5rem;
}

.table th {
    border-top: none;
    font-weight: 600;
}

.btn-group .btn {
    padding: 0.25rem 0.5rem;
}

.activity-checkbox {
    cursor: pointer;
}

#selectAll {
    cursor: pointer;
}

.pagination-sm .page-link {
    font-size: 0.875rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const activityCheckboxes = document.querySelectorAll('.activity-checkbox');
    const selectedCountElement = document.getElementById('selectedCount');
    const bulkApproveBtn = document.getElementById('bulkApproveBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const selectedActivitiesDiv = document.getElementById('selectedActivities');
    const selectedActivitiesDeleteDiv = document.getElementById('selectedActivitiesDelete');

    // Select all functionality
    selectAllCheckbox?.addEventListener('change', function() {
        activityCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    // Individual checkbox functionality
    activityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedCount();
            
            // Update select all checkbox state
            const checkedBoxes = document.querySelectorAll('.activity-checkbox:checked');
            selectAllCheckbox.checked = checkedBoxes.length === activityCheckboxes.length;
            selectAllCheckbox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < activityCheckboxes.length;
        });
    });

    function updateSelectedCount() {
        const checkedBoxes = document.querySelectorAll('.activity-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (selectedCountElement) {
            selectedCountElement.textContent = count + ' selected';
        }
        
        // Enable/disable bulk action buttons
        if (bulkApproveBtn) {
            bulkApproveBtn.disabled = count === 0;
        }
        if (bulkDeleteBtn) {
            bulkDeleteBtn.disabled = count === 0;
        }
        
        // Add hidden inputs for selected activities
        if (selectedActivitiesDiv) {
            selectedActivitiesDiv.innerHTML = '';
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activity_ids[]';
                input.value = checkbox.value;
                selectedActivitiesDiv.appendChild(input);
            });
        }
        
        if (selectedActivitiesDeleteDiv) {
            selectedActivitiesDeleteDiv.innerHTML = '';
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'activity_ids[]';
                input.value = checkbox.value;
                selectedActivitiesDeleteDiv.appendChild(input);
            });
        }
    }

    // Initial count update
    updateSelectedCount();
});

function confirmBulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.activity-checkbox:checked');
    const count = checkedBoxes.length;
    
    if (count === 0) {
        alert('Please select at least one activity');
        return false;
    }
    
    const actionText = action === 'approve' ? 'approve' : 'delete';
    return confirm(`Are you sure you want to ${actionText} ${count} selected activities?`);
}

// Auto-submit search form on Enter
document.getElementById('search')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        this.closest('form').submit();
    }
});
</script>

<?php include 'includes/admin_footer.php'; ?>