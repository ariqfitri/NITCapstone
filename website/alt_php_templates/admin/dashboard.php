<?php
session_start();
require_once '../config/database.php';
require_once '../models/Program.php';
require_once '../models/User.php';
require_once '../models/Scraper.php';

// Check if admin is logged in
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

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
        $message = "All activities approved successfully!";
    }
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .attention-alert {
            border-left: 4px solid #dc3545;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <span class="navbar-brand">KidsSmart Admin Dashboard</span>
            <div class="navbar-nav ms-auto">
                <a href="../index.php" class="nav-link" target="_blank">View Site</a>
                <a href="admin_scrapers.php" class="nav-link">Scraper Management</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Attention Alerts -->
        <?php if ($total_pending > 0): ?>
            <div class="alert alert-warning attention-alert d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Attention needed!</strong> There are <?= $total_pending ?> activities waiting for approval.
                </div>
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="approve_all">
                    <button type="submit" class="btn btn-warning btn-sm" 
                            onclick="return confirm('Approve ALL <?= $total_pending ?> pending activities?')">
                        Approve All
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-white bg-primary">
                    <div class="card-body">
                        <div class="metric-value"><?= $total_activities ?></div>
                        <div class="metric-label">Total Activities</div>
                        <small><?= $total_approved ?> approved • <?= $total_pending ?> pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-white bg-success">
                    <div class="card-body">
                        <div class="metric-value"><?= $total_users ?></div>
                        <div class="metric-label">Registered Users</div>
                        <small>PHP Website Users</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-white bg-info">
                    <div class="card-body">
                        <div class="metric-value"><?= count($category_stats) ?></div>
                        <div class="metric-label">Categories</div>
                        <small>Activity categories</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card stat-card text-white bg-secondary">
                    <div class="card-body">
                        <div class="metric-value"><?= count($scraper_stats) ?></div>
                        <div class="metric-label">Data Sources</div>
                        <small>Scraper sources</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Left Column -->
            <div class="col-lg-8">
                <!-- Quick Stats Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Activity Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="activityChart" height="100"></canvas>
                    </div>
                </div>

                <!-- Category Distribution -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Categories Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($category_stats as $category): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><?= htmlspecialchars($category['category_name']) ?></span>
                                        <span class="badge bg-primary"><?= $category['activity_count'] ?></span>
                                    </div>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= ($category['activity_count'] / $total_activities) * 100 ?>%">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Scraper Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Scraper Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($scraper_stats): ?>
                                <?php foreach ($scraper_stats as $stat): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <h6 class="card-title"><?= htmlspecialchars($stat['source_name']) ?></h6>
                                                    <span class="badge bg-primary"><?= $stat['count'] ?></span>
                                                </div>
                                                <p class="card-text">
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
                        <h5 class="mb-0">Recent Users</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_users) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($user['username']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('M j', strtotime($user['created_at'])) ?>
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
                        <h5 class="mb-0">Recent Scraper Runs</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_scraper_runs) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_scraper_runs as $run): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
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
                        <h5 class="mb-0">Quick Actions</h5>
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
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Activities Section -->
        <div class="card mb-4" id="pending-activities">
            <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Pending Approval (<?= $total_pending ?>)</h4>
                <?php if ($total_pending > 0): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="approve_all">
                        <button type="submit" class="btn btn-dark btn-sm" 
                                onclick="return confirm('Approve ALL pending activities?')">
                            Approve All
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
                                <?php foreach ($pending_activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                            <?php if (!empty($activity['description'])): ?>
                                                <br><small class="text-muted"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                        <td>
                                            <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                            <?php if (!empty($activity['postcode'])): ?>
                                                , <?= htmlspecialchars($activity['postcode']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-warning btn-sm">Reject</button>
                                            </form>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Delete this activity permanently?')">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending activities for approval.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Approved Activities (Collapsible) -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">
                    <button class="btn btn-link text-white text-decoration-none" data-bs-toggle="collapse" data-bs-target="#approvedActivities">
                        Approved Activities (<?= $total_approved ?>) <i class="fas fa-chevron-down"></i>
                    </button>
                </h4>
            </div>
            <div class="collapse" id="approvedActivities">
                <div class="card-body">
                    <?php if ($total_approved > 0): ?>
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
                                    <?php foreach (array_slice($approved_activities, 0, 10) as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($activity['title']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($activity['category'] ?? 'N/A') ?></td>
                                            <td>
                                                <?= htmlspecialchars($activity['suburb'] ?? 'N/A') ?>
                                                <?php if (!empty($activity['postcode'])): ?>
                                                    , <?= htmlspecialchars($activity['postcode']) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($activity['source_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="btn btn-warning btn-sm">Unapprove</button>
                                                </form>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Delete this activity permanently?')">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($total_approved > 10): ?>
                            <p class="text-muted">Showing 10 of <?= $total_approved ?> approved activities</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">No approved activities yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        const activityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Approved', 'Pending'],
                datasets: [{
                    label: 'Activities',
                    data: [<?= $total_approved ?>, <?= $total_pending ?>],
                    backgroundColor: [
                        'rgba(40, 167, 69, 0.8)',
                        'rgba(255, 193, 7, 0.8)'
                    ],
                    borderColor: [
                        'rgba(40, 167, 69, 1)',
                        'rgba(255, 193, 7, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>