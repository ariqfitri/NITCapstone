<?php
// Set page title
$page_title = 'Dashboard';

// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

try {
    require_once '../config/database.php';
    require_once '../models/Program.php';
    require_once '../models/User.php';
    require_once '../models/Scraper.php';
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

// Initialize database connections with error handling
try {
    $appDatabase = new Database('kidssmart_app');
    $appDb = $appDatabase->getConnection();
    $program = new Program($appDb);
    $scraper = new Scraper($appDb);
} catch (Exception $e) {
    die("App Database Error: " . $e->getMessage());
}

try {
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
    $user = new User($userDb);
} catch (Exception $e) {
    // Continue without user database if it fails
    $userDb = null;
    $user = null;
    $user_db_error = $e->getMessage();
}

// Handle actions
$message = '';
if ($_POST['action'] ?? false) {
    $activity_id = intval($_POST['activity_id'] ?? 0); // ✅ FIX: Cast to int for safety
    
    try {
        if ($_POST['action'] === 'approve' && $activity_id) {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $message = "Activity approved successfully!";
        } elseif ($_POST['action'] === 'reject' && $activity_id) {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 0 WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $message = "Activity rejected successfully!";
        } elseif ($_POST['action'] === 'delete' && $activity_id) {
            $stmt = $appDb->prepare("DELETE FROM activities WHERE activity_id = ?");
            $stmt->execute([$activity_id]);
            $message = "Activity deleted successfully!";
        } elseif ($_POST['action'] === 'approve_all') {
            $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE is_approved = 0");
            $stmt->execute();
            $affected = $stmt->rowCount(); // ✅ FIX: Get affected count
            $message = "$affected activities approved successfully!";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage(); // ✅ FIX: Better error handling
    }
}

// Get statistics with error handling
$stats = [
    'total_activities' => 0,
    'pending_activities' => 0,
    'approved_activities' => 0,
    'total_users' => 0,
    'new_users_today' => 0,
    'active_users_today' => 0
];

// Get activity statistics
try {
    $activity_stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending
        FROM activities";
    $activity_stats = $appDb->query($activity_stats_query)->fetch(PDO::FETCH_ASSOC);
    
    $stats['total_activities'] = $activity_stats['total'] ?? 0;
    $stats['approved_activities'] = $activity_stats['approved'] ?? 0;
    $stats['pending_activities'] = $activity_stats['pending'] ?? 0;
} catch (Exception $e) {
    error_log("Activity stats error: " . $e->getMessage());
}

// Get user statistics (if user database is available)
if ($userDb) {
    try {
        $user_stats_query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
            FROM users";
        $user_stats = $userDb->query($user_stats_query)->fetch(PDO::FETCH_ASSOC);
        
        $stats['total_users'] = $user_stats['total'] ?? 0;
        $stats['new_users_today'] = $user_stats['new_today'] ?? 0;
        $stats['active_users_today'] = $user_stats['active'] ?? 0;
    } catch (Exception $e) {
        error_log("User stats error: " . $e->getMessage());
    }
}

// Get category statistics
$category_stats = [];
try {
    $category_query = "SELECT category, COUNT(*) as activity_count 
                      FROM activities 
                      WHERE category IS NOT NULL AND is_approved = 1
                      GROUP BY category 
                      ORDER BY activity_count DESC
                      LIMIT 5"; // ✅ FIX: Added LIMIT for performance
    $category_stats = $appDb->query($category_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Category stats error: " . $e->getMessage());
}

// Get recent activities
$recent_activities = [];
try {
    $recent_query = "SELECT activity_id, title, category, suburb, scraped_at, is_approved 
                    FROM activities 
                    ORDER BY activity_id DESC 
                    LIMIT 5";
    $recent_activities = $appDb->query($recent_query)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Recent activities error: " . $e->getMessage());
}

// Get scraper statistics
$scraper_stats = [];
$recent_scraper_runs = [];
try {
    $scraper_stats = $scraper->getScraperStats();
    $recent_scraper_runs = $scraper->getRecentRuns(5);
} catch (Exception $e) {
    error_log("Scraper stats error: " . $e->getMessage());
}

// Get pending activities for approval section  
$pending_activities = [];
$total_pending = 0;
try {
    // ✅ FIX: Removed incorrect JOIN - categories table might not exist
    $pending_query = "SELECT * FROM activities 
                     WHERE is_approved = 0 
                     ORDER BY activity_id DESC 
                     LIMIT 10";
    $pending_activities = $appDb->query($pending_query)->fetchAll(PDO::FETCH_ASSOC);
    $total_pending = count($pending_activities);
} catch (Exception $e) {
    error_log("Pending activities error: " . $e->getMessage());
}

// Get today's statistics
$today_stats = [];
try {
    $today_query = "SELECT 
        COUNT(*) as scraped_today,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_today
        FROM activities 
        WHERE DATE(scraped_at) = CURDATE()";
    $today_stats = $appDb->query($today_query)->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $today_stats = ['scraped_today' => 0, 'approved_today' => 0];
    error_log("Today stats error: " . $e->getMessage());
}
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2 text-primary mb-0">
                        <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
                    </h1>
                    <p class="text-muted">
                        <span class="status-dot status-healthy"></span>
                        System operational • Last updated: <?= date('M j, g:i A') ?>
                    </p>
                </div>
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Alerts -->
            <?php if ($stats['pending_activities'] > 0): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention!</strong> <?= $stats['pending_activities'] ?> activities are waiting for approval.
                    <a href="activities.php?status=pending" class="alert-link">Review them now</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($user_db_error)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> User database connection failed. User statistics unavailable.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- ✅ FIX: Added message display -->
            <?php if ($message): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Main Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['total_activities'] ?></div>
                                    <div class="metric-label">Total Activities</div>
                                </div>
                                <i class="fas fa-running fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['pending_activities'] ?></div>
                                    <div class="metric-label">Pending Approval</div>
                                </div>
                                <i class="fas fa-clock fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['approved_activities'] ?></div>
                                    <div class="metric-label">Approved Activities</div>
                                </div>
                                <i class="fas fa-check-circle fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $stats['total_users'] ?></div>
                                    <div class="metric-label">Total Users</div>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Statistics -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 class="text-primary"><?= $today_stats['scraped_today'] ?? 0 ?></h3>
                            <small class="text-muted">Activities Added Today</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 class="text-success"><?= $today_stats['approved_today'] ?? 0 ?></h3>
                            <small class="text-muted">Approved Today</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 class="text-info"><?= $stats['new_users_today'] ?></h3>
                            <small class="text-muted">New Users Today</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h3 class="text-secondary"><?= count($category_stats) ?></h3>
                            <small class="text-muted">Active Categories</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Top Categories -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Top Categories</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($category_stats) > 0): ?>
                                <div class="row">
                                    <?php foreach ($category_stats as $cat): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold"><?= htmlspecialchars($cat['category']) ?></span>
                                                <span class="badge bg-primary"><?= $cat['activity_count'] ?></span>
                                            </div>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar" 
                                                     style="width: <?= ($cat['activity_count'] / max(1, $stats['total_activities'])) * 100 ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No categories available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Pending Activities Section -->
                    <?php if ($total_pending > 0): ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock me-2"></i>Pending Activities 
                                    <span class="badge bg-warning"><?= $total_pending ?></span>
                                </h5>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="action" value="approve_all">
                                    <button type="submit" class="btn btn-warning btn-sm" 
                                            onclick="return confirm('Approve ALL <?= $stats['pending_activities'] ?> pending activities?')">
                                        <i class="fas fa-check-double me-1"></i> Approve All
                                    </button>
                                </form>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>Location</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_activities as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                                        <?php if (!empty($activity['description'])): ?>
                                                            <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 80) ?>...</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                                    <td>
                                                        <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                                        <?php if (!empty($activity['postcode'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($activity['postcode']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-success btn-sm" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </form>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                            <input type="hidden" name="action" value="delete">
                                                            <button type="submit" class="btn btn-danger btn-sm" 
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
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Recent Scraper Runs -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Scraper Runs</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_scraper_runs) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_scraper_runs as $run): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <span class="badge bg-secondary"><?= htmlspecialchars($run['scraper_name']) ?></span>
                                                <?php 
                                                $status_class = [
                                                    'started' => 'warning',
                                                    'completed' => 'success', 
                                                    'failed' => 'danger'
                                                ][$run['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $status_class ?>">
                                                    <?= htmlspecialchars($run['status']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-clock me-1"></i><?= date('M j, g:i A', strtotime($run['run_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No recent scraper runs</p>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer text-center">
                            <a href="admin_scrapers.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-cog me-1"></i> Manage Scrapers
                            </a>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Recent Activities</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($recent_activities) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recent_activities as $activity): ?>
                                        <div class="list-group-item px-0">
                                            <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></small>
                                                <?php if ($activity['is_approved']): ?>
                                                    <span class="badge bg-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No recent activities</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="activities.php" class="btn btn-outline-primary">
                                    <i class="fas fa-running me-2"></i> Manage Activities
                                </a>
                                <a href="admin_scrapers.php" class="btn btn-outline-info">
                                    <i class="fas fa-spider me-2"></i> Run Scrapers
                                </a>
                                <a href="users.php" class="btn btn-outline-success">
                                    <i class="fas fa-users me-2"></i> View Users
                                </a>
                                <a href="categories.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-tags me-2"></i> Manage Categories
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>