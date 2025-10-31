<?php
// Fixed Dashboard with better error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $categories = $program->getCategories();
    foreach ($categories as $category) {
        $count_query = "SELECT COUNT(*) as count FROM activities WHERE category = ? AND is_approved = 1";
        $stmt = $appDb->prepare($count_query);
        $stmt->execute([$category]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        $category_stats[] = ['category' => $category, 'count' => $count];
    }
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

// Demo metrics for pages that don't have analytics yet
$page_views_today = rand(50, 200);
$page_views_week = rand(300, 1500);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .metric-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-healthy { background: #28a745; }
        .status-warning { background: #ffc107; }
        .status-error { background: #dc3545; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-child me-2"></i>KidsSmart Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i> View Site
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="activities.php">
                                <i class="fas fa-running me-2"></i> Activities
                                <?php if ($stats['pending_activities'] > 0): ?>
                                    <span class="badge bg-warning ms-1"><?= $stats['pending_activities'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users me-2"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_scrapers.php">
                                <i class="fas fa-spider me-2"></i> Scrapers
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
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

                    <!-- Key Metrics -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card metric-card border shadow-sm">
                                <div class="card-body bg-primary text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1" style="color: white !important;">Total Activities</h6>
                                            <h2 class="mb-0" style="color: white !important;"><?= number_format($stats['total_activities']) ?></h2>
                                            <small style="color: rgba(255,255,255,0.8) !important;">Approved: <?= number_format($stats['approved_activities']) ?></small>
                                        </div>
                                        <div>
                                            <i class="fas fa-running fa-2x" style="color: rgba(255,255,255,0.8) !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card metric-card border shadow-sm">
                                <div class="card-body bg-success text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1" style="color: white !important;">Total Users</h6>
                                            <h2 class="mb-0" style="color: white !important;"><?= number_format($stats['total_users']) ?></h2>
                                            <small style="color: rgba(255,255,255,0.8) !important;">New today: <?= number_format($stats['new_users_today']) ?></small>
                                        </div>
                                        <div>
                                            <i class="fas fa-users fa-2x" style="color: rgba(255,255,255,0.8) !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card metric-card border shadow-sm">
                                <div class="card-body bg-warning text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1" style="color: white !important;">Pending Review</h6>
                                            <h2 class="mb-0" style="color: white !important;"><?= number_format($stats['pending_activities']) ?></h2>
                                            <small style="color: rgba(255,255,255,0.8) !important;">Need approval</small>
                                        </div>
                                        <div>
                                            <i class="fas fa-clock fa-2x" style="color: rgba(255,255,255,0.8) !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="card metric-card border shadow-sm">
                                <div class="card-body bg-info text-white">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="fw-bold mb-1" style="color: white !important;">Page Views Today</h6>
                                            <h2 class="mb-0" style="color: white !important;"><?= number_format($page_views_today) ?></h2>
                                            <small style="color: rgba(255,255,255,0.8) !important;">Week: <?= number_format($page_views_week) ?></small>
                                        </div>
                                        <div>
                                            <i class="fas fa-chart-line fa-2x" style="color: rgba(255,255,255,0.8) !important;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-2">
                                    <a href="activities.php?status=pending" class="btn btn-warning w-100">
                                        <i class="fas fa-running me-2"></i>Review Activities
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="users.php" class="btn btn-outline-success w-100">
                                        <i class="fas fa-users me-2"></i>Manage Users
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="admin_scrapers.php" class="btn btn-outline-info w-100">
                                        <i class="fas fa-spider me-2"></i>Run Scrapers
                                    </a>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <a href="../index.php" target="_blank" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-external-link-alt me-2"></i>View Site
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Row -->
                    <div class="row">
                        <!-- Categories -->
                        <div class="col-lg-8 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Activity Categories
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($category_stats)): ?>
                                        <div class="row">
                                            <?php 
                                            $colors = ['primary', 'success', 'info', 'warning', 'danger', 'secondary'];
                                            foreach (array_slice($category_stats, 0, 6) as $index => $stat): 
                                                $color = $colors[$index % count($colors)];
                                            ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="fw-bold"><?= htmlspecialchars($stat['category']) ?></span>
                                                        <span class="badge bg-<?= $color ?>"><?= $stat['count'] ?></span>
                                                    </div>
                                                    <div class="progress" style="height: 6px;">
                                                        <?php
                                                        $max_count = max(array_column($category_stats, 'count')) ?: 1;
                                                        $percentage = ($stat['count'] / $max_count) * 100;
                                                        ?>
                                                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percentage ?>%"></div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-chart-pie fa-3x text-muted mb-3"></i>
                                            <h6>No category data available</h6>
                                            <p class="text-muted">Run scrapers to populate activity data</p>
                                            <a href="admin_scrapers.php" class="btn btn-primary">
                                                <i class="fas fa-spider me-1"></i> Run Scrapers
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activities & System Info -->
                        <div class="col-lg-4">
                            <!-- Recent Activities -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Activities
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recent_activities)): ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($recent_activities as $activity): ?>
                                                <div class="list-group-item px-0">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1"><?= htmlspecialchars(substr($activity['title'], 0, 40)) ?>...</h6>
                                                            <small class="text-muted"><?= htmlspecialchars($activity['category']) ?> • <?= htmlspecialchars($activity['suburb']) ?></small>
                                                        </div>
                                                        <span class="badge bg-<?= $activity['is_approved'] ? 'success' : 'warning' ?>">
                                                            <?= $activity['is_approved'] ? 'Approved' : 'Pending' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-running fa-2x text-muted mb-2"></i>
                                            <p class="text-muted">No activities yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- System Status -->
                            <div class="card">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">
                                        <i class="fas fa-server me-2"></i>System Status
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>App Database</span>
                                        <span class="badge bg-success">Connected</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>User Database</span>
                                        <span class="badge bg-<?= $userDb ? 'success' : 'danger' ?>">
                                            <?= $userDb ? 'Connected' : 'Error' ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Scrapers</span>
                                        <span class="badge bg-info"><?= count($scraper_stats) ?> configured</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>PHP Version</span>
                                        <span class="badge bg-secondary"><?= PHP_VERSION ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>