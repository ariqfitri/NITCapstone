<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once '../config/database.php';
require_once '../models/Program.php';
require_once '../models/User.php';
require_once '../models/Scraper.php';

// Check if admin is logged in
require_admin_login();

// Initialize dual database connections
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

$userDatabase = new Database('kidssmart_users'); 
$userDb = $userDatabase->getConnection();

// Initialize models with correct databases
$program = new Program($appDb);        // Uses kidssmart_app
$user = new User($userDb);             // Uses kidssmart_users
$scraper = new Scraper($appDb);        // Uses kidssmart_app

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
    } elseif ($_POST['action'] === 'approve_all') {
        $stmt = $appDb->prepare("UPDATE activities SET is_approved = 1 WHERE is_approved = 0");
        $stmt->execute();
        $_SESSION['flash_message'] = "All activities approved successfully!";
    }
    
    // Redirect to avoid form resubmission
    header('Location: dashboard.php');
    exit;
}

// Get metrics data
$pending_activities = $program->getPendingActivities();
$approved_activities = $program->getApprovedActivities();
$total_pending = count($pending_activities);
$total_approved = count($approved_activities);
$total_activities = $total_pending + $total_approved;

// User metrics
$total_users = $user->getTotalUsersCount();
$recent_users = $user->getRecentUsers(5);

// Category distribution
$category_stats = $program->getCategoryStats();

// Recent activity (last 7 days)
$recent_activities = $program->getRecentActivities(7);

// Scraper statistics
$scraper_stats = $scraper->getScraperStats();
$recent_scraper_runs = $scraper->getRecentRuns(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../static/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
            <h1 class="h2 text-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group me-2">
                    <button type="button" class="btn btn-outline-primary" onclick="refreshDashboard()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="btn-group">
                    <a href="../index.php" class="btn btn-primary" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Site
                    </a>
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

        <!-- Attention Alerts -->
        <?php if ($total_pending > 0): ?>
            <div class="alert alert-warning d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention needed!</strong> There are <?= $total_pending ?> activities waiting for approval.
                </div>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="approve_all">
                    <button type="submit" class="btn btn-warning" 
                            onclick="return confirm('Approve ALL <?= $total_pending ?> pending activities?')">
                        <i class="fas fa-check-double"></i> Approve All
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/admin_sidebar.php'; ?>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Key Metrics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-primary h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="card-title"><?= $total_activities ?></h3>
                                        <p class="card-text">Total Activities</p>
                                        <small><?= $total_approved ?> approved • <?= $total_pending ?> pending</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-running fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-success h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="card-title"><?= $total_users ?></h3>
                                        <p class="card-text">Registered Users</p>
                                        <small>Website users</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-info h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="card-title"><?= count($category_stats) ?></h3>
                                        <p class="card-text">Categories</p>
                                        <small>Activity types</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-tags fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card text-white bg-warning h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h3 class="card-title text-dark"><?= count($scraper_stats) ?></h3>
                                        <p class="card-text text-dark">Data Sources</p>
                                        <small class="text-dark">Scraper sources</small>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-spider fa-2x text-dark"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div><i class="fas fa-spider fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-lg-8">
                        <!-- Activity Overview Chart -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Activity Overview
                                </h5>
                            </div>
                            <div class="card-body">
                                <canvas id="activityChart" height="100"></canvas>
                            </div>
                        </div>

                        <!-- Category Distribution -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Categories Distribution
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($category_stats): ?>
                                    <div class="row">
                                        <?php foreach ($category_stats as $category): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <span class="fw-medium"><?= htmlspecialchars($category['category_name']) ?></span>
                                                    <span class="badge bg-primary"><?= $category['activity_count'] ?></span>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?= ($category['activity_count'] / $total_activities) * 100 ?>%">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No category data available</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Scraper Statistics -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-spider me-2"></i>Scraper Statistics
                                </h5>
                                <a href="admin_scrapers.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-cog"></i> Manage
                                </a>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($scraper_stats): ?>
                                        <?php foreach ($scraper_stats as $stat): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card border-start border-primary border-3">
                                                    <div class="card-body py-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="card-title mb-1"><?= htmlspecialchars($stat['source_name']) ?></h6>
                                                            <span class="badge bg-primary"><?= $stat['count'] ?></span>
                                                        </div>
                                                        <p class="card-text mb-0">
                                                            <small class="text-muted">
                                                                <?php if ($stat['last_scraped']): ?>
                                                                    Last run: <?= date('M j, g:i A', strtotime($stat['last_scraped'])) ?>
                                                                <?php else: ?>
                                                                    Never run
                                                                <?php endif; ?>
                                                            </small>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-12">
                                            <p class="text-muted">No scraper data available</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-lg-4">
                        <!-- Recent Users -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-user-plus me-2"></i>Recent Users
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_users) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_users as $recent_user): ?>
                                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?= htmlspecialchars($recent_user['username']) ?></h6>
                                                    <small class="text-muted"><?= htmlspecialchars($recent_user['email']) ?></small>
                                                </div>
                                                <small class="text-muted">
                                                    <?= date('M j', strtotime($recent_user['created_at'])) ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent users</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Scraper Runs -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-history me-2"></i>Recent Scraper Runs
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (count($recent_scraper_runs) > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php foreach ($recent_scraper_runs as $run): ?>
                                            <div class="list-group-item px-0">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
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
                                                <small class="text-muted">
                                                    <?= date('M j, g:i A', strtotime($run['run_at'])) ?>
                                                </small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No recent scraper runs</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="#pending-activities" class="btn btn-warning">
                                        <i class="fas fa-clock"></i> Review Pending (<?= $total_pending ?>)
                                    </a>
                                    <a href="admin_scrapers.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-spider"></i> Manage Scrapers
                                    </a>
                                    <a href="../search.php" class="btn btn-outline-primary" target="_blank">
                                        <i class="fas fa-search"></i> View Public Site
                                    </a>
                                    <?php if ($total_pending > 5): ?>
                                        <form method="post" class="d-grid">
                                            <input type="hidden" name="action" value="approve_all">
                                            <button type="submit" class="btn btn-success" 
                                                    onclick="return confirm('Approve ALL pending activities?')">
                                                <i class="fas fa-check-double"></i> Approve All
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Activities Section -->
                <div class="card mb-4" id="pending-activities">
                    <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Pending Approval (<?= $total_pending ?>)
                        </h4>
                        <?php if ($total_pending > 0): ?>
                            <form method="post">
                                <input type="hidden" name="action" value="approve_all">
                                <button type="submit" class="btn btn-dark btn-sm" 
                                        onclick="return confirm('Approve ALL pending activities?')">
                                    <i class="fas fa-check-double"></i> Approve All
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($total_pending > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Category</th>
                                            <th>Location</th>
                                            <th>Source</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($pending_activities, 0, 10) as $activity): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                                    <?php if (!empty($activity['description'])): ?>
                                                        <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</small>
                                                    <?php endif; ?>
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
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
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
                            <?php if ($total_pending > 10): ?>
                                <p class="text-muted mt-3">Showing 10 of <?= $total_pending ?> pending activities</p>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                <h5>All caught up!</h5>
                                <p class="text-muted">No activities pending approval.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Approved', 'Pending'],
                datasets: [{
                    data: [<?= $total_approved ?>, <?= $total_pending ?>],
                    backgroundColor: [
                        '#28a745',
                        '#ffc107'
                    ],
                    borderColor: [
                        '#ffffff',
                        '#ffffff'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });

        function refreshDashboard() {
            location.reload();
        }

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>>
</body>
</html>>
</body>
</html>