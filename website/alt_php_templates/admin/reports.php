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
require_once '../models/User.php';
require_once '../models/Scraper.php';

// Initialize databases
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

try {
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
} catch (Exception $e) {
    $userDb = null;
}

// Get date range
$days = intval($_GET['days'] ?? 30);
$days = min(max($days, 7), 365); // Between 7 and 365 days

try {
    // Activity growth over time
    $activity_growth_query = "SELECT 
        DATE(scraped_at) as date,
        COUNT(*) as count
        FROM activities 
        WHERE scraped_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
        GROUP BY DATE(scraped_at)
        ORDER BY date ASC";
    $activity_growth = $appDb->query($activity_growth_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Activities by category
    $category_query = "SELECT 
        category,
        COUNT(*) as count
        FROM activities 
        WHERE category IS NOT NULL
        GROUP BY category 
        ORDER BY count DESC 
        LIMIT 10";
    $categories = $appDb->query($category_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Activities by suburb (top 10)
    $suburb_query = "SELECT 
        suburb,
        COUNT(*) as count
        FROM activities 
        WHERE suburb IS NOT NULL AND suburb != ''
        GROUP BY suburb 
        ORDER BY count DESC 
        LIMIT 10";
    $suburbs = $appDb->query($suburb_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Scraper performance
    $scraper_perf_query = "SELECT 
        scraper_name,
        COUNT(*) as total_runs,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as successful,
        SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
        FROM scraping_logs
        WHERE run_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
        GROUP BY scraper_name";
    $scraper_performance = $appDb->query($scraper_perf_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // User growth (if available)
    $user_growth = [];
    if ($userDb) {
        $user_growth_query = "SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
            FROM users 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date ASC";
        $user_growth = $userDb->query($user_growth_query)->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $error = "Error loading analytics: " . $e->getMessage();
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
                    <div class="btn-group me-2">
                        <a href="?days=7" class="btn btn-sm btn-outline-primary <?= $days === 7 ? 'active' : '' ?>">7 Days</a>
                        <a href="?days=30" class="btn btn-sm btn-outline-primary <?= $days === 30 ? 'active' : '' ?>">30 Days</a>
                        <a href="?days=90" class="btn btn-sm btn-outline-primary <?= $days === 90 ? 'active' : '' ?>">90 Days</a>
                        <a href="?days=365" class="btn btn-sm btn-outline-primary <?= $days === 365 ? 'active' : '' ?>">1 Year</a>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Activity Growth Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Activity Growth (Last <?= $days ?> Days)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="activityGrowthChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <!-- Category Distribution -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Top Categories</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Suburb Distribution -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Top Suburbs</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="suburbChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scraper Performance -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Scraper Performance</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($scraper_performance) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Scraper</th>
                                                <th>Total Runs</th>
                                                <th>Successful</th>
                                                <th>Failed</th>
                                                <th>Success Rate</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($scraper_performance as $scraper): ?>
                                                <tr>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($scraper['scraper_name']) ?></span></td>
                                                    <td><?= $scraper['total_runs'] ?></td>
                                                    <td><span class="text-success"><?= $scraper['successful'] ?></span></td>
                                                    <td><span class="text-danger"><?= $scraper['failed'] ?></span></td>
                                                    <td>
                                                        <?php 
                                                        $success_rate = $scraper['total_runs'] > 0 
                                                            ? ($scraper['successful'] / $scraper['total_runs']) * 100 
                                                            : 0;
                                                        $badge_class = $success_rate >= 80 ? 'success' : ($success_rate >= 50 ? 'warning' : 'danger');
                                                        ?>
                                                        <span class="badge bg-<?= $badge_class ?>">
                                                            <?= number_format($success_rate, 1) ?>%
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No scraper runs in selected time period.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (count($user_growth) > 0): ?>
            <!-- User Growth Chart -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">User Registration Growth</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="userGrowthChart" height="80"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script>
// Activity Growth Chart
const activityGrowthCtx = document.getElementById('activityGrowthChart');
new Chart(activityGrowthCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($activity_growth, 'date')) ?>,
        datasets: [{
            label: 'Activities Added',
            data: <?= json_encode(array_column($activity_growth, 'count')) ?>,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13, 110, 253, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Category Chart
const categoryCtx = document.getElementById('categoryChart');
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($categories, 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($categories, 'count')) ?>,
            backgroundColor: [
                '#0d6efd', '#6610f2', '#6f42c1', '#d63384', '#dc3545',
                '#fd7e14', '#ffc107', '#198754', '#20c997', '#0dcaf0'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Suburb Chart
const suburbCtx = document.getElementById('suburbChart');
new Chart(suburbCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($suburbs, 'suburb')) ?>,
        datasets: [{
            label: 'Activities',
            data: <?= json_encode(array_column($suburbs, 'count')) ?>,
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

<?php if (count($user_growth) > 0): ?>
// User Growth Chart
const userGrowthCtx = document.getElementById('userGrowthChart');
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
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include 'includes/admin_footer.php'; ?>