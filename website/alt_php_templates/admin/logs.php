<?php
// Set page title
$page_title = 'System Logs';

// Session and auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../models/Scraper.php';

// Initialize database
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();
$scraper = new Scraper($appDb);

// Get filter parameters
$type_filter = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

try {
    // Get scraper logs
    $where_sql = '';
    $params = [];
    
    if ($type_filter !== 'all') {
        $where_sql = "WHERE status = ?";
        $params[] = $type_filter;
    }
    
    $query = "SELECT * FROM scraping_logs $where_sql ORDER BY run_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $appDb->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM scraping_logs $where_sql";
    $count_stmt = $appDb->prepare($count_query);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $limit);
    
    // Get stats
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
        SUM(CASE WHEN status = 'started' THEN 1 ELSE 0 END) as running
        FROM scraping_logs";
    $stats = $appDb->query($stats_query)->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $logs = [];
    $total_count = 0;
    $total_pages = 0;
    $error = "Error loading logs: " . $e->getMessage();
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-clipboard-list me-2"></i>System Logs
                </h1>
                <button class="btn btn-outline-primary btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['total'] ?></div>
                                    <div class="metric-label">Total Runs</div>
                                </div>
                                <i class="fas fa-list fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['completed'] ?></div>
                                    <div class="metric-label">Completed</div>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-white bg-danger">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['failed'] ?></div>
                                    <div class="metric-label">Failed</div>
                                </div>
                                <i class="fas fa-times-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['running'] ?></div>
                                    <div class="metric-label">Running</div>
                                </div>
                                <i class="fas fa-spinner fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="type" class="form-select">
                                <option value="all" <?= $type_filter === 'all' ? 'selected' : '' ?>>All Logs</option>
                                <option value="completed" <?= $type_filter === 'completed' ? 'selected' : '' ?>>Completed Only</option>
                                <option value="failed" <?= $type_filter === 'failed' ? 'selected' : '' ?>>Failed Only</option>
                                <option value="started" <?= $type_filter === 'started' ? 'selected' : '' ?>>Running</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        Scraper Logs (<?= $total_count ?> entries)
                        <?php if ($page > 1 || $total_pages > 1): ?>
                            <span class="badge bg-secondary">Page <?= $page ?> of <?= $total_pages ?></span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($logs) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Scraper</th>
                                        <th>Status</th>
                                        <th>Message</th>
                                        <th>Run Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= $log['log_id'] ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($log['scraper_name']) ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                $status_class = [
                                                    'started' => 'warning',
                                                    'completed' => 'success',
                                                    'failed' => 'danger'
                                                ][$log['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $status_class ?>">
                                                    <?= htmlspecialchars($log['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($log['message'])): ?>
                                                    <small class="text-muted">
                                                        <?= htmlspecialchars(substr($log['message'], 0, 100)) ?>
                                                        <?php if (strlen($log['message']) > 100): ?>
                                                            ...
                                                            <button class="btn btn-sm btn-link p-0" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#logModal<?= $log['log_id'] ?>">
                                                                View Full
                                                            </button>
                                                        <?php endif; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?= date('M j, Y g:i A', strtotime($log['run_at'])) ?></small>
                                            </td>
                                        </tr>
                                        
                                        <!-- Modal for full message -->
                                        <?php if (!empty($log['message']) && strlen($log['message']) > 100): ?>
                                            <div class="modal fade" id="logModal<?= $log['log_id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Log Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p><strong>Scraper:</strong> <?= htmlspecialchars($log['scraper_name']) ?></p>
                                                            <p><strong>Status:</strong> <span class="badge bg-<?= $status_class ?>"><?= $log['status'] ?></span></p>
                                                            <p><strong>Time:</strong> <?= date('M j, Y g:i A', strtotime($log['run_at'])) ?></p>
                                                            <hr>
                                                            <p><strong>Message:</strong></p>
                                                            <pre class="bg-light p-3"><?= htmlspecialchars($log['message']) ?></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5>No logs found</h5>
                            <p class="text-muted">Run scrapers to generate log entries.</p>
                            <a href="admin_scrapers.php" class="btn btn-primary">
                                <i class="fas fa-spider me-1"></i> Go to Scrapers
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&type=<?= $type_filter ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&type=<?= $type_filter ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&type=<?= $type_filter ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>