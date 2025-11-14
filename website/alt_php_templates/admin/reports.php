<?php
// Set page title
$page_title = 'Analytics & Reports';
$include_chartjs = true;

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

// Initialize databases with better error handling
try {
    $appDatabase = new Database('kidssmart_app');
    $appDb = $appDatabase->getConnection();
} catch (Exception $e) {
    die("Application Database Error: " . $e->getMessage());
}

try {
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
} catch (Exception $e) {
    $userDb = null;
    $user_db_error = $e->getMessage();
}

// Get date range and export parameters
$days = intval($_GET['days'] ?? 30);
$days = min(max($days, 7), 365); // Between 7 and 365 days
$export_format = $_GET['export'] ?? '';

// Handle export requests
if ($export_format && in_array($export_format, ['csv', 'json'])) {
    try {
        if ($export_format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="kidssmart_analytics_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Title', 'Category', 'Suburb', 'Status', 'Source', 'Postcode']);
            
            $export_query = "SELECT 
                DATE(scraped_at) as date,
                title,
                COALESCE(category, 'Unknown') as category,
                COALESCE(suburb, 'Unknown') as suburb,
                CASE WHEN is_approved = 1 THEN 'Approved' ELSE 'Pending' END as status,
                COALESCE(source_name, 'Unknown') as source,
                COALESCE(postcode, '') as postcode
                FROM activities 
                WHERE scraped_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                ORDER BY scraped_at DESC";
            
            $stmt = $appDb->prepare($export_query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            
        } elseif ($export_format === 'json') {
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="kidssmart_analytics_' . date('Y-m-d') . '.json"');
            
            // Get comprehensive data for JSON export
            $export_data = [
                'report_metadata' => [
                    'generated_at' => date('Y-m-d H:i:s'),
                    'period_days' => $days,
                    'period_start' => date('Y-m-d', strtotime("-{$days} days")),
                    'period_end' => date('Y-m-d')
                ],
                'summary' => [],
                'activity_data' => [],
                'category_breakdown' => [],
                'location_breakdown' => [],
                'source_analysis' => []
            ];
            
            // Get summary data
            $summary_query = "SELECT 
                COUNT(*) as total_activities,
                SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending,
                COUNT(DISTINCT COALESCE(category, 'Unknown')) as unique_categories,
                COUNT(DISTINCT COALESCE(suburb, 'Unknown')) as unique_suburbs
                FROM activities";
            $export_data['summary'] = $appDb->query($summary_query)->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode($export_data, JSON_PRETTY_PRINT);
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Export error: " . $e->getMessage();
        header('Location: reports.php');
        exit;
    }
}

// Initialize default values
$has_activities = false;
$filled_activity_growth = [];
$categories = [];
$suburbs = [];
$sources = [];
$scraper_performance = [];
$user_growth = [];
$summary_stats = [
    'total_activities' => 0,
    'approved_activities' => 0,
    'pending_activities' => 0,
    'unique_categories' => 0,
    'unique_suburbs' => 0,
    'unique_sources' => 0,
    'avg_title_length' => 0,
    'avg_description_length' => 0
];
$user_stats = ['total' => 0, 'new_today' => 0, 'active_week' => 0];
$trend_comparison = ['current_week' => 0, 'previous_week' => 0];

try {
    // Check if activities table exists and has data
    $table_check_query = "SELECT COUNT(*) as count FROM activities";
    $table_check = $appDb->query($table_check_query)->fetch(PDO::FETCH_ASSOC);
    $has_activities = ($table_check['count'] ?? 0) > 0;
    
    if ($has_activities) {
        // Activity growth over time with more granular data
        $activity_growth_query = "SELECT 
            DATE(scraped_at) as date,
            COUNT(*) as count,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count,
            SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_count
            FROM activities 
            WHERE scraped_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
            GROUP BY DATE(scraped_at)
            ORDER BY date ASC";
        $activity_growth = $appDb->query($activity_growth_query)->fetchAll(PDO::FETCH_ASSOC);
        
        // Fill in missing dates for better chart visualization
        $start_date = new DateTime("-{$days} days");
        $end_date = new DateTime();
        $growth_data_by_date = array_column($activity_growth, null, 'date');
        
        for ($date = clone $start_date; $date <= $end_date; $date->modify('+1 day')) {
            $date_str = $date->format('Y-m-d');
            $filled_activity_growth[] = [
                'date' => $date_str,
                'count' => $growth_data_by_date[$date_str]['count'] ?? 0,
                'approved_count' => $growth_data_by_date[$date_str]['approved_count'] ?? 0,
                'pending_count' => $growth_data_by_date[$date_str]['pending_count'] ?? 0
            ];
        }
        
        // Activities by category with approval rates
        $category_query = "SELECT 
            COALESCE(category, 'Unknown') as category,
            COUNT(*) as total_count,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count,
            ROUND((SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as approval_rate
            FROM activities 
            GROUP BY COALESCE(category, 'Unknown')
            ORDER BY total_count DESC 
            LIMIT 10";
        $categories = $appDb->query($category_query)->fetchAll(PDO::FETCH_ASSOC);
        
        // Activities by suburb (top 15)
        $suburb_query = "SELECT 
            suburb,
            COUNT(*) as count,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
            postcode
            FROM activities 
            WHERE suburb IS NOT NULL AND suburb != ''
            GROUP BY suburb, postcode
            ORDER BY count DESC 
            LIMIT 15";
        $suburbs = $appDb->query($suburb_query)->fetchAll(PDO::FETCH_ASSOC);
        
        // Activity sources analysis
        $sources_query = "SELECT 
            COALESCE(source_name, 'Unknown') as source,
            COUNT(*) as count,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved,
            ROUND(AVG(CHAR_LENGTH(COALESCE(description, ''))), 1) as avg_description_length
            FROM activities 
            GROUP BY COALESCE(source_name, 'Unknown')
            ORDER BY count DESC 
            LIMIT 10";
        $sources = $appDb->query($sources_query)->fetchAll(PDO::FETCH_ASSOC);
        
        // Check if scraping_logs table exists before querying
        $tables_query = "SHOW TABLES LIKE 'scraping_logs'";
        $table_exists = $appDb->query($tables_query)->fetch();
        
        if ($table_exists) {
            // Scraper performance analysis
            $scraper_perf_query = "SELECT 
                scraper_name,
                COUNT(*) as total_runs,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                MAX(run_at) as last_run
                FROM scraping_logs
                WHERE run_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
                GROUP BY scraper_name
                ORDER BY total_runs DESC";
            $scraper_performance = $appDb->query($scraper_perf_query)->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // User growth (if user database available)
        if ($userDb) {
            try {
                // Check if users table has expected columns
                $user_columns = $userDb->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
                
                if (in_array('created_at', $user_columns)) {
                    $user_growth_query = "SELECT 
                        DATE(created_at) as date,
                        COUNT(*) as count
                        FROM users 
                        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
                        GROUP BY DATE(created_at)
                        ORDER BY date ASC";
                    $user_growth = $userDb->query($user_growth_query)->fetchAll(PDO::FETCH_ASSOC);
                    
                    $user_stats_query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today";
                    
                    // Add last_login check if column exists
                    if (in_array('last_login', $user_columns)) {
                        $user_stats_query .= ",
                            SUM(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as active_week";
                    } else {
                        $user_stats_query .= ", 0 as active_week";
                    }
                    
                    $user_stats_query .= " FROM users";
                    $user_stats = $userDb->query($user_stats_query)->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                error_log("User analytics error: " . $e->getMessage());
            }
        }
        
        // Overall summary statistics
        $summary_stats_query = "SELECT 
            COUNT(*) as total_activities,
            SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_activities,
            SUM(CASE WHEN is_approved = 0 THEN 1 ELSE 0 END) as pending_activities,
            COUNT(DISTINCT COALESCE(category, 'Unknown')) as unique_categories,
            COUNT(DISTINCT COALESCE(suburb, 'Unknown')) as unique_suburbs,
            COUNT(DISTINCT COALESCE(source_name, 'Unknown')) as unique_sources,
            COALESCE(AVG(CHAR_LENGTH(title)), 0) as avg_title_length,
            COALESCE(AVG(CHAR_LENGTH(description)), 0) as avg_description_length
            FROM activities";
        $summary_stats = $appDb->query($summary_stats_query)->fetch(PDO::FETCH_ASSOC);
        
        // Recent activity trends (last 7 vs previous 7 days)
        $trend_query = "SELECT 
            CASE 
                WHEN scraped_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 'current_week'
                WHEN scraped_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) THEN 'previous_week'
                ELSE 'older'
            END as period,
            COUNT(*) as count
            FROM activities 
            WHERE scraped_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY period";
        $trend_data = $appDb->query($trend_query)->fetchAll(PDO::FETCH_ASSOC);
        $trend_comparison = array_column($trend_data, 'count', 'period');
    }
    
} catch (Exception $e) {
    $error = "Error loading analytics: " . $e->getMessage();
    $has_activities = false;
    error_log("Reports page error: " . $e->getMessage());
}

// Calculate trend percentage
$trend_percentage = 0;
if (($trend_comparison['previous_week'] ?? 0) > 0) {
    $current = $trend_comparison['current_week'] ?? 0;
    $previous = $trend_comparison['previous_week'] ?? 1;
    $trend_percentage = round(($current - $previous) / $previous * 100, 1);
}

// Handle messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Quick stats for the period
$period_stats = [
    'activities_this_period' => 0,
    'new_sources_this_period' => 0,
    'approval_rate_this_period' => 0
];

if ($has_activities) {
    try {
        $period_stats_query = "SELECT 
            COUNT(*) as activities_this_period,
            COUNT(DISTINCT source_name) as new_sources_this_period,
            ROUND(AVG(CASE WHEN is_approved = 1 THEN 100 ELSE 0 END), 1) as approval_rate_this_period
            FROM activities 
            WHERE scraped_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)";
        $period_stats = $appDb->query($period_stats_query)->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Period stats error: " . $e->getMessage());
    }
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
                    <i class="fas fa-chart-bar me-2"></i>Analytics & Reports
                </h1>
                <div class="btn-toolbar">
                    <div class="btn-group me-2" role="group">
                        <a href="?days=7" class="btn btn-sm btn-outline-primary <?= $days === 7 ? 'active' : '' ?>">7 Days</a>
                        <a href="?days=30" class="btn btn-sm btn-outline-primary <?= $days === 30 ? 'active' : '' ?>">30 Days</a>
                        <a href="?days=90" class="btn btn-sm btn-outline-primary <?= $days === 90 ? 'active' : '' ?>">90 Days</a>
                        <a href="?days=365" class="btn btn-sm btn-outline-primary <?= $days === 365 ? 'active' : '' ?>">1 Year</a>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?days=<?= $days ?>&export=csv">
                                <i class="fas fa-file-csv me-2"></i>Export CSV
                            </a></li>
                            <li><a class="dropdown-item" href="?days=<?= $days ?>&export=json">
                                <i class="fas fa-file-code me-2"></i>Export JSON
                            </a></li>
                        </ul>
                    </div>
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

            <?php if (isset($user_db_error)): ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>User Database Unavailable:</strong> User analytics are limited.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$has_activities): ?>
                <!-- No Data Available -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-warning">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-chart-line fa-4x text-warning mb-4"></i>
                                <h3 class="text-warning mb-3">No Activity Data Available</h3>
                                <p class="text-muted mb-4 lead">
                                    Your KidsSmart database is ready, but no activities have been imported yet.<br>
                                    Let's get started by running the activity scrapers!
                                </p>
                                <div class="row justify-content-center">
                                    <div class="col-md-8">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h5 class="mb-3">Get Started in 3 Steps:</h5>
                                                <div class="row text-start">
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                <strong>1</strong>
                                                            </div>
                                                            <div>
                                                                <strong>Run Scrapers</strong><br>
                                                                <small class="text-muted">Collect activity data</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                <strong>2</strong>
                                                            </div>
                                                            <div>
                                                                <strong>Review Activities</strong><br>
                                                                <small class="text-muted">Approve quality content</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                                                <strong>3</strong>
                                                            </div>
                                                            <div>
                                                                <strong>View Analytics</strong><br>
                                                                <small class="text-muted">Monitor your progress</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="mt-4">
                                                    <a href="admin_scrapers.php" class="btn btn-primary btn-lg me-3">
                                                        <i class="fas fa-spider me-2"></i>Run Activity Scrapers
                                                    </a>
                                                    <a href="activities.php" class="btn btn-outline-secondary">
                                                        <i class="fas fa-list me-2"></i>View Activities
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <!-- Summary Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card text-white bg-primary h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="metric-value"><?= number_format($summary_stats['total_activities']) ?></div>
                                        <small class="opacity-75">Total Activities</small>
                                        <div class="mt-1">
                                            <?php if ($trend_percentage > 0): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-arrow-up"></i> +<?= $trend_percentage ?>% this week
                                                </small>
                                            <?php elseif ($trend_percentage < 0): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-arrow-down"></i> <?= $trend_percentage ?>% this week
                                                </small>
                                            <?php else: ?>
                                                <small class="opacity-75">No change this week</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <i class="fas fa-list fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card text-white bg-success h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="metric-value"><?= number_format($summary_stats['approved_activities']) ?></div>
                                        <small class="opacity-75">Approved Activities</small>
                                        <div class="mt-1">
                                            <small class="opacity-75">
                                                <?= $summary_stats['total_activities'] > 0 ? 
                                                    round(($summary_stats['approved_activities'] / $summary_stats['total_activities']) * 100, 1) : 0 ?>% approval rate
                                            </small>
                                        </div>
                                    </div>
                                    <i class="fas fa-check fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card text-white bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="metric-value"><?= number_format($summary_stats['pending_activities']) ?></div>
                                        <small class="opacity-75 text-dark">Pending Approval</small>
                                        <div class="mt-1">
                                            <?php if ($summary_stats['pending_activities'] > 0): ?>
                                                <small class="opacity-75 text-dark">
                                                    <i class="fas fa-exclamation-triangle"></i> Needs attention
                                                </small>
                                            <?php else: ?>
                                                <small class="opacity-75 text-dark">All caught up!</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-3">
                        <div class="card text-white bg-info h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <div class="metric-value"><?= number_format($user_stats['total'] ?? 0) ?></div>
                                        <small class="opacity-75">Total Users</small>
                                        <div class="mt-1">
                                            <small class="opacity-75"><?= $user_stats['new_today'] ?? 0 ?> joined today</small>
                                        </div>
                                    </div>
                                    <i class="fas fa-users fa-2x opacity-75"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Period Summary Row -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <h4 class="text-primary"><?= number_format($period_stats['activities_this_period']) ?></h4>
                                <small class="text-muted">Added in Last <?= $days ?> Days</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <h4 class="text-success"><?= number_format($summary_stats['unique_suburbs']) ?></h4>
                                <small class="text-muted">Suburbs Covered</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <h4 class="text-info"><?= number_format($summary_stats['unique_categories']) ?></h4>
                                <small class="text-muted">Activity Categories</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body text-center">
                                <h4 class="text-warning"><?= round($period_stats['approval_rate_this_period'] ?? 0) ?>%</h4>
                                <small class="text-muted">Period Approval Rate</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Growth Chart -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-line me-2"></i>Activity Growth (Last <?= $days ?> Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activityGrowthChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Category Distribution -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-tags me-2"></i>Top Categories
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($categories)): ?>
                                    <canvas id="categoryChart" height="200"></canvas>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No category data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Location Distribution -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i>Top Locations
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($suburbs)): ?>
                                    <canvas id="suburbChart" height="200"></canvas>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-map fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No location data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <!-- Data Sources Analysis -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-database me-2"></i>Data Sources
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($sources)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Source</th>
                                                    <th class="text-center">Total</th>
                                                    <th class="text-center">Approved</th>
                                                    <th class="text-center">Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sources as $source): ?>
                                                    <tr>
                                                        <td>
                                                            <small><?= htmlspecialchars($source['source']) ?></small>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-primary"><?= number_format($source['count']) ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success"><?= number_format($source['approved']) ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <small><?= $source['count'] > 0 ? round(($source['approved'] / $source['count']) * 100, 1) : 0 ?>%</small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No source data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Scraper Performance -->
                    <div class="col-lg-6">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-spider me-2"></i>Scraper Performance
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($scraper_performance)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Scraper</th>
                                                    <th class="text-center">Runs</th>
                                                    <th class="text-center">Success Rate</th>
                                                    <th class="text-center">Last Run</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($scraper_performance as $scraper): ?>
                                                    <?php 
                                                    $success_rate = $scraper['total_runs'] > 0 ? 
                                                        round(($scraper['successful'] / $scraper['total_runs']) * 100, 1) : 0;
                                                    ?>
                                                    <tr>
                                                        <td>
                                                            <small><?= htmlspecialchars($scraper['scraper_name']) ?></small>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-info"><?= $scraper['total_runs'] ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge <?= $success_rate >= 90 ? 'bg-success' : ($success_rate >= 70 ? 'bg-warning' : 'bg-danger') ?>">
                                                                <?= $success_rate ?>%
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <small class="text-muted">
                                                                <?= $scraper['last_run'] ? date('M j, H:i', strtotime($scraper['last_run'])) : 'Never' ?>
                                                            </small>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-spider fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">No scraper data available</p>
                                        <a href="admin_scrapers.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-play me-1"></i>Run Scrapers
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Growth Chart (if available) -->
                <?php if ($userDb && !empty($user_growth)): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">
                                    <i class="fas fa-users me-2"></i>User Growth (Last <?= $days ?> Days)
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="userGrowthChart" height="80"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php if ($has_activities): ?>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="activities.php?status=pending" class="btn btn-warning w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-clock fa-2x mb-2"></i>
                                            <strong>Review Pending</strong><br>
                                            <small><?= number_format($summary_stats['pending_activities']) ?> items need review</small>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="admin_scrapers.php" class="btn btn-primary w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-spider fa-2x mb-2"></i>
                                            <strong>Run Scrapers</strong><br>
                                            <small>Update activity data</small>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <a href="categories.php" class="btn btn-success w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-tags fa-2x mb-2"></i>
                                            <strong>Manage Categories</strong><br>
                                            <small><?= number_format($summary_stats['unique_categories']) ?> categories</small>
                                        </a>
                                    </div>
                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <button type="button" class="btn btn-info w-100 h-100 d-flex flex-column justify-content-center" onclick="window.print()">
                                            <i class="fas fa-print fa-2x mb-2"></i>
                                            <strong>Print Report</strong><br>
                                            <small>Current analytics view</small>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="col-md-4 mb-3">
                                        <a href="admin_scrapers.php" class="btn btn-primary w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-spider fa-3x mb-3"></i>
                                            <strong>Run Activity Scrapers</strong><br>
                                            <small>Start collecting activity data</small>
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="activities.php" class="btn btn-outline-secondary w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-list fa-3x mb-3"></i>
                                            <strong>View Activities</strong><br>
                                            <small>Manage imported activities</small>
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="categories.php" class="btn btn-outline-info w-100 h-100 d-flex flex-column justify-content-center">
                                            <i class="fas fa-tags fa-3x mb-3"></i>
                                            <strong>Manage Categories</strong><br>
                                            <small>Organize activity types</small>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<style>
.metric-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1.2;
    margin-bottom: 0.25rem;
}

.card {
    transition: box-shadow 0.3s ease, transform 0.2s ease;
    border: 1px solid rgba(0,0,0,.125);
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.table th {
    border-top: none;
    font-weight: 600;
    background-color: #f8f9fa;
}

.badge {
    font-size: 0.75rem;
    font-weight: 500;
}

.h-100 {
    height: 100% !important;
}

@media print {
    .btn-toolbar,
    .btn,
    .navbar,
    .sidebar,
    .alert {
        display: none !important;
    }
    
    .col-md-9,
    .col-lg-10 {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        break-inside: avoid;
        page-break-inside: avoid;
    }
}

.text-success i { color: #198754; }
.text-warning i { color: #ffc107; }
.text-danger i { color: #dc3545; }

canvas {
    max-height: 300px;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}
</style>

<?php if ($has_activities): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set Chart.js defaults
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.8)';
    Chart.defaults.plugins.tooltip.titleColor = '#fff';
    Chart.defaults.plugins.tooltip.bodyColor = '#fff';
    Chart.defaults.plugins.tooltip.borderColor = '#dee2e6';
    Chart.defaults.plugins.tooltip.borderWidth = 1;

    // Activity Growth Chart with dual datasets
    const activityGrowthCtx = document.getElementById('activityGrowthChart');
    if (activityGrowthCtx) {
        new Chart(activityGrowthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($filled_activity_growth, 'date')) ?>,
                datasets: [{
                    label: 'Total Activities',
                    data: <?= json_encode(array_column($filled_activity_growth, 'count')) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Approved',
                    data: <?= json_encode(array_column($filled_activity_growth, 'approved_count')) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: false
                }, {
                    label: 'Pending',
                    data: <?= json_encode(array_column($filled_activity_growth, 'pending_count')) ?>,
                    borderColor: '#ffc107',
                    backgroundColor: 'rgba(255, 193, 7, 0.1)',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && <?= !empty($categories) ? 'true' : 'false' ?>) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($categories, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($categories, 'total_count')) ?>,
                    backgroundColor: [
                        '#0d6efd', '#198754', '#dc3545', '#ffc107', '#6f42c1',
                        '#fd7e14', '#20c997', '#6610f2', '#d63384', '#0dcaf0'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label;
                                const value = context.formattedValue;
                                const approvalRate = <?= json_encode(array_column($categories, 'approval_rate')) ?>[context.dataIndex];
                                return `${label}: ${value} activities (${approvalRate}% approved)`;
                            }
                        }
                    }
                }
            }
        });
    }

    // Suburb Chart
    const suburbCtx = document.getElementById('suburbChart');
    if (suburbCtx && <?= !empty($suburbs) ? 'true' : 'false' ?>) {
        new Chart(suburbCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($suburbs, 'suburb')) ?>,
                datasets: [{
                    label: 'Total Activities',
                    data: <?= json_encode(array_column($suburbs, 'count')) ?>,
                    backgroundColor: '#0d6efd',
                    borderColor: '#0a58ca',
                    borderWidth: 1
                }, {
                    label: 'Approved',
                    data: <?= json_encode(array_column($suburbs, 'approved')) ?>,
                    backgroundColor: '#198754',
                    borderColor: '#146c43',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    }
                }
            }
        });
    }

    <?php if ($userDb && !empty($user_growth)): ?>
    // User Growth Chart
    const userGrowthCtx = document.getElementById('userGrowthChart');
    if (userGrowthCtx) {
        new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($user_growth, 'date')) ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?= json_encode(array_column($user_growth, 'count')) ?>,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>
<?php endif; ?>

<?php include 'includes/admin_footer.php'; ?>