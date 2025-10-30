<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../models/Program.php';

// Check if admin is logged in
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();
$program = new Program($appDb);

// Handle actions
if ($_POST['action'] ?? false) {
    $activity_id = $_POST['activity_id'] ?? 0;
    
    if ($_POST['action'] === 'approve' && $activity_id) {
        $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $_SESSION['flash_message'] = "Activity approved successfully!";
    } elseif ($_POST['action'] === 'reject' && $activity_id) {
        $stmt = $appDb->prepare("UPDATE activities SET is_approved = 0 WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $_SESSION['flash_message'] = "Activity rejected successfully!";
    } elseif ($_POST['action'] === 'delete' && $activity_id) {
        $stmt = $appDb->prepare("DELETE FROM activities WHERE activity_id = ?");
        $stmt->execute([$activity_id]);
        $_SESSION['flash_message'] = "Activity deleted successfully!";
    }
    
    header('Location: activities.php');
    exit;
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? '';
$source = $_GET['source'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where_conditions = [];
$params = [];

if ($status === 'pending') {
    $where_conditions[] = "is_approved = 0";
} elseif ($status === 'approved') {
    $where_conditions[] = "is_approved = 1";
}

if ($category) {
    $where_conditions[] = "category = ?";
    $params[] = $category;
}

if ($source) {
    $where_conditions[] = "source_name = ?";
    $params[] = $source;
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total count
$count_query = "SELECT COUNT(*) as total FROM activities $where_clause";
$count_stmt = $appDb->prepare($count_query);
$count_stmt->execute($params);
$total_activities = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get activities
$query = "SELECT * FROM activities $where_clause ORDER BY activity_id DESC LIMIT $limit OFFSET $offset";
$stmt = $appDb->prepare($query);
$stmt->execute($params);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get filter options
$categories = $program->getCategories();
$sources_stmt = $appDb->query("SELECT DISTINCT source_name FROM activities WHERE source_name IS NOT NULL ORDER BY source_name");
$sources = $sources_stmt->fetchAll(PDO::FETCH_COLUMN);

$total_pages = ceil($total_activities / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-primary">
                        <i class="fas fa-running me-2"></i>Activity Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <!-- Filters -->
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-filter me-2"></i>Filter Activities
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="get" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All Activities</option>
                                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Approval</option>
                                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="category" class="form-label">Category</label>
                                <select name="category" id="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="source" class="form-label">Source</label>
                                <select name="source" id="source" class="form-select">
                                    <option value="">All Sources</option>
                                    <?php foreach ($sources as $src): ?>
                                        <option value="<?= htmlspecialchars($src) ?>" <?= $source === $src ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($src) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Apply Filter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Results Summary -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">
                        <i class="fas fa-list me-1"></i>
                        Showing <?= count($activities) ?> of <?= $total_activities ?> activities
                        <?php if ($status !== 'all'): ?>
                            (<?= $status ?>)
                        <?php endif; ?>
                    </span>
                    <span class="text-muted">Page <?= $page ?> of <?= $total_pages ?></span>
                </div>

                <!-- Activities Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php if (count($activities) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Source</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($activities as $activity): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                                    <?php if (!empty($activity['description'])): ?>
                                                        <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</small>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted">ID: <?= $activity['activity_id'] ?></small>
                                                </td>
                                                <td>
                                                    <?php if (!empty($activity['category'])): ?>
                                                        <span class="badge bg-primary"><?= htmlspecialchars($activity['category']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                                    <?php if (!empty($activity['postcode'])): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($activity['postcode']) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></span>
                                                    <?php if (!empty($activity['scraped_at'])): ?>
                                                        <br><small class="text-muted"><?= date('M j, Y', strtotime($activity['scraped_at'])) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($activity['is_approved']): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i>Approved
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock me-1"></i>Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if (!$activity['is_approved']): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                                <input type="hidden" name="action" value="approve">
                                                                <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                                    <i class="fas fa-check"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="submit" class="btn btn-warning btn-sm" title="Unapprove">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="../program_detail.php?id=<?= $activity['activity_id'] ?>" 
                                                           class="btn btn-info btn-sm" target="_blank" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-danger btn-sm" 
                                                                    onclick="return confirm('Delete this activity permanently?')" title="Delete">
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
                                <nav aria-label="Activities pagination" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <?php
                                        $query_params = http_build_query(array_filter([
                                            'status' => $status !== 'all' ? $status : null,
                                            'category' => $category ?: null,
                                            'source' => $source ?: null
                                        ]));
                                        ?>
                                        
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= $query_params ?>">
                                                    <i class="fas fa-chevron-left"></i> Previous
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&<?= $query_params ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= $query_params ?>">
                                                    Next <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            <?php endif; ?>

                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5>No activities found</h5>
                                <p class="text-muted">Try adjusting your filters or check the scraper status.</p>
                                <a href="admin_scrapers.php" class="btn btn-primary">
                                    <i class="fas fa-spider"></i> Manage Scrapers
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>