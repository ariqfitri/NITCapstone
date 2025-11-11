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
    $activity_id = $_POST['activity_id'] ?? 0;
    
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
            // ✅ FIX: Added check to prevent deleting non-existent records
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
            $ids = array_filter($ids, function($id) { return $id > 0; }); // ✅ FIX: Filter invalid IDs
            if (!empty($ids)) {
                $placeholders = str_repeat('?,', count($ids) - 1) . '?';
                $stmt = $appDb->prepare("DELETE FROM activities WHERE activity_id IN ($placeholders)");
                $stmt->execute($ids);
                $affected = $stmt->rowCount();
                $_SESSION['success_message'] = "$affected activities deleted successfully!";
            } else {
                $_SESSION['error_message'] = "No valid activities selected for deletion!";
            }
        }
        
        // ✅ FIX: Proper URL construction with null coalescing
        $redirect_url = 'activities.php';
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $redirect_url .= '?status=' . urlencode($_GET['status']);
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
$search = trim($_GET['search'] ?? ''); // ✅ FIX: Trim search input
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
    $where_clauses[] = "(title LIKE ? OR description LIKE ? OR suburb LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($category_filter) {
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
    $error = "Error loading activities: " . $e->getMessage();
}

// ✅ FIX: Handle both success and error messages
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

            <!-- ✅ FIX: Added error message handling -->
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
                                    <div class="metric-value"><?= $stats['total'] ?? 0 ?></div>
                                    <div class="metric-label">Total Activities</div>
                                </div>
                                <i class="fas fa-list fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="metric-value"><?= $stats['approved'] ?? 0 ?></div>
                                    <div class="metric-label">Approved</div>
                                </div>
                                <i class="fas fa-check fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="metric-value"><?= $stats['pending'] ?? 0 ?></div>
                                    <div class="metric-label">Pending Approval</div>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Activities</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending Only</option>
                                <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved Only</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php if (isset($categories) && is_array($categories)): ?>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat['category'] ?? '') ?>" 
                                                <?= $category_filter === ($cat['category'] ?? '') ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['category'] ?? 'Unknown') ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" 
                                   placeholder="Search title, description, location..." 
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions Form -->
            <!-- ✅ FIX: Added proper bulk actions form -->
            <form method="POST" id="bulkForm" onsubmit="return confirmBulkAction()">
                <div class="card mb-2">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <input type="checkbox" id="selectAll" class="form-check-input me-2">
                            <label for="selectAll" class="form-check-label me-3">Select All</label>
                            <span id="selectedCount" class="badge bg-secondary me-3">0 selected</span>
                            <button type="submit" name="action" value="bulk_delete" 
                                    class="btn btn-danger btn-sm" id="bulkDeleteBtn" disabled>
                                <i class="fas fa-trash me-1"></i> Delete Selected
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Activities Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        Activities 
                        <span class="badge bg-secondary"><?= $total_count ?> results</span>
                        <?php if ($search || $category_filter || $status_filter !== 'all'): ?>
                            <a href="activities.php" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($activities) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="30"></th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Source</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="activity_ids[]" value="<?= $activity['activity_id'] ?>" 
                                                       form="bulkForm" class="form-check-input activity-checkbox">
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                                <?php if (!empty($activity['description'])): ?>
                                                    <br><small class="text-muted">
                                                        <?= htmlspecialchars(substr($activity['description'], 0, 100)) ?>...
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                            <td>
                                                <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                                <?php if (!empty($activity['postcode'])): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($activity['postcode']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($activity['is_approved']): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></small></td>
                                            <td class="text-end">
                                                <?php if (!$activity['is_approved']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" title="Approve">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                        <button type="submit" name="action" value="reject" class="btn btn-warning btn-sm" title="Reject">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Delete this activity?')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>">
                                                    Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= urlencode($status_filter) ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category_filter) ?>">
                                                    Next
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?> (<?= $total_count ?> total activities)</small>
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

<script>
// Select all functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.activity-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

// Update selected count
function updateSelectedCount() {
    const checked = document.querySelectorAll('.activity-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked + ' selected';
    document.getElementById('bulkDeleteBtn').disabled = checked === 0;
}

// ✅ FIX: Added event listeners to existing checkboxes and new ones
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.activity-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
});

function confirmBulkAction() {
    const checked = document.querySelectorAll('.activity-checkbox:checked').length;
    if (checked === 0) {
        alert('Please select at least one activity');
        return false;
    }
    return confirm(`Are you sure you want to perform this action on ${checked} activities?`);
}
</script>

<?php include 'includes/admin_footer.php'; ?>