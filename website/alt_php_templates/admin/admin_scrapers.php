<?php
// No session requirements - using same approach as working dashboard
require_once '../config/database.php';
require_once '../models/Scraper.php';
require_once '../models/Program.php';

// Initialize database connection
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

$scraper = new Scraper($appDb);
$program = new Program($appDb);

// Available scrapers based on your project
$available_scrapers = [
    'activities' => [
        'name' => 'Active Activities',
        'description' => 'Scrapes sports and active activities from activeactivities.com.au',
        'last_run' => null,
        'status' => 'unknown',
        'activities_count' => 0
    ],
    'kidsbook' => [
        'name' => 'Kids Book Activities', 
        'description' => 'Scrapes educational and book-related activities',
        'last_run' => null,
        'status' => 'unknown',
        'activities_count' => 0
    ]
];

// Handle scraper execution
if (isset($_POST['run_scraper'])) {
    $scraper_name = $_POST['scraper_name'];
    
    if (!array_key_exists($scraper_name, $available_scrapers)) {
        $flash_message = "Invalid scraper: $scraper_name";
        $flash_type = 'danger';
    } else {
        // Log the start
        $scraper->logScraperRun($scraper_name, 'started');
        
        // Execute scraper
        $output = [];
        $return_var = 0;
        
        $command = "cd /var/www/html/kidssmart && scrapy crawl " . escapeshellarg($scraper_name) . " 2>&1";
        exec($command, $output, $return_var);
        
        $status = ($return_var === 0) ? 'completed' : 'failed';
        $message = implode("\n", $output);
        
        // Log the completion
        $scraper->logScraperRun($scraper_name, $status, $message);
        
        $flash_message = "Scraper '$scraper_name' $status";
        $flash_type = ($status === 'completed') ? 'success' : 'danger';
    }
}

// Handle bulk scraper run
if (isset($_POST['run_all_scrapers'])) {
    $results = [];
    foreach ($available_scrapers as $scraper_name => $config) {
        $scraper->logScraperRun($scraper_name, 'started');
        
        $output = [];
        $return_var = 0;
        $command = "cd /var/www/html/kidssmart && scrapy crawl " . escapeshellarg($scraper_name) . " 2>&1";
        exec($command, $output, $return_var);
        
        $status = ($return_var === 0) ? 'completed' : 'failed';
        $message = implode("\n", $output);
        
        $scraper->logScraperRun($scraper_name, $status, $message);
        $results[] = "$scraper_name: $status";
    }
    
    $flash_message = "Bulk run completed: " . implode(', ', $results);
    $flash_type = 'info';
}

// Get statistics and update scraper status
$scraper_stats = $scraper->getScraperStats();
$recent_runs = $scraper->getRecentRuns(10);
$total_activities = $program->getTotalProgramsCount();

// Update available scrapers with actual data
foreach ($scraper_stats as $stat) {
    if (isset($available_scrapers[$stat['source_name']])) {
        $available_scrapers[$stat['source_name']]['last_run'] = $stat['last_scraped'];
        $available_scrapers[$stat['source_name']]['activities_count'] = $stat['count'];
    }
}

// Get recent run status for each scraper
foreach ($recent_runs as $run) {
    if (isset($available_scrapers[$run['scraper_name']])) {
        $available_scrapers[$run['scraper_name']]['status'] = $run['status'];
        if (!$available_scrapers[$run['scraper_name']]['last_run']) {
            $available_scrapers[$run['scraper_name']]['last_run'] = $run['run_at'];
        }
    }
}

$failed_scrapers = method_exists($scraper, 'getFailedScrapers') ? $scraper->getFailedScrapers() : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraper Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
        .scraper-card {
            transition: transform 0.2s;
        }
        .scraper-card:hover {
            transform: translateY(-2px);
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-completed { background-color: #28a745; }
        .status-failed { background-color: #dc3545; }
        .status-started { background-color: #ffc107; }
        .status-unknown { background-color: #6c757d; }
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
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link active" href="admin_scrapers.php">
                                    <i class="fas fa-spider me-2"></i>Scrapers
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="activities.php">
                                    <i class="fas fa-running me-2"></i>Activities
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="users.php">
                                    <i class="fas fa-users me-2"></i>Users
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h2 text-primary">
                            <i class="fas fa-spider me-2"></i>Scraper Management
                        </h1>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                            <form method="post" class="d-inline">
                                <button type="submit" name="run_all_scrapers" class="btn btn-warning" 
                                        onclick="return confirm('Run all scrapers? This may take several minutes.')">
                                    <i class="fas fa-play-circle"></i> Run All
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Flash Message -->
                    <?php if (isset($flash_message)): ?>
                        <div class="alert alert-<?= $flash_type ?? 'info' ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= htmlspecialchars($flash_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Failed Scrapers Alert -->
                    <?php if (!empty($failed_scrapers)): ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Attention:</strong> <?= count($failed_scrapers) ?> scraper(s) failed recently.
                        </div>
                    <?php endif; ?>

                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 bg-primary text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= number_format($total_activities) ?></h3>
                                    <small>Total Activities</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-success text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= count($available_scrapers) ?></h3>
                                    <small>Active Scrapers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-info text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= count($recent_runs) ?></h3>
                                    <small>Recent Runs</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-warning text-white">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?= count($failed_scrapers) ?></h3>
                                    <small>Failed Runs</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Individual Scrapers -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h4 class="mb-3">Individual Scrapers</h4>
                        </div>
                        <?php foreach ($available_scrapers as $scraper_name => $config): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card scraper-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <span class="status-indicator status-<?= $config['status'] ?>"></span>
                                            <?= htmlspecialchars($config['name']) ?>
                                        </h5>
                                        <span class="badge bg-<?= $config['status'] === 'completed' ? 'success' : ($config['status'] === 'failed' ? 'danger' : ($config['status'] === 'started' ? 'warning' : 'secondary')) ?>">
                                            <?= ucfirst($config['status']) ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted mb-3"><?= htmlspecialchars($config['description']) ?></p>
                                        
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <small class="text-muted">Activities:</small>
                                                <div class="fw-bold"><?= number_format($config['activities_count'] ?? 0) ?></div>
                                            </div>
                                            <div class="col-sm-6">
                                                <small class="text-muted">Last Run:</small>
                                                <div class="fw-bold">
                                                    <?php if ($config['last_run']): ?>
                                                        <?= date('M j, g:i A', strtotime($config['last_run'])) ?>
                                                    <?php else: ?>
                                                        Never
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="scraper_name" value="<?= $scraper_name ?>">
                                            <button type="submit" name="run_scraper" class="btn btn-primary btn-sm"
                                                    <?= $config['status'] === 'started' ? 'disabled' : '' ?>>
                                                <i class="fas fa-play"></i>
                                                <?= $config['status'] === 'started' ? 'Running...' : 'Run Scraper' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Recent Runs -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i>Recent Scraper Runs
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_runs)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Scraper</th>
                                                <th>Status</th>
                                                <th>Run Time</th>
                                                <th>Message</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_runs as $run): ?>
                                                <tr>
                                                    <td>
                                                        <span class="status-indicator status-<?= $run['status'] ?>"></span>
                                                        <?= htmlspecialchars($run['scraper_name']) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?= $run['status'] === 'completed' ? 'success' : ($run['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                                            <?= ucfirst($run['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= date('M j, Y g:i A', strtotime($run['run_at'])) ?></td>
                                                    <td>
                                                        <?php if ($run['message']): ?>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars(substr($run['message'], 0, 50)) ?>
                                                                <?= strlen($run['message']) > 50 ? '...' : '' ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <span class="text-muted">No output</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-spider fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No recent scraper runs</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh if scrapers are running
        const runningScraper = <?= json_encode(array_filter($available_scrapers, function($s) { return $s['status'] === 'started'; })) ?>;
        if (Object.keys(runningScraper).length > 0) {
            setTimeout(() => location.reload(), 10000);
        }
    </script>
</body>
</html>