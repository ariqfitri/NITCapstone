<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scraper.php';
require_once __DIR__ . '/../models/Program.php';
require_once __DIR__ . '/includes/admin_auth.php';

// Check if user is admin
require_admin_login();

// Initialize database connection
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

$scraper = new Scraper($appDb);
$program = new Program($appDb);

// Available scrapers configuration based on your spiders
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
        $_SESSION['flash_message'] = "Invalid scraper: $scraper_name";
        $_SESSION['flash_type'] = 'danger';
        header('Location: admin_scrapers.php');
        exit;
    }
    
    // Log the start
    $scraper->logScraperRun($scraper_name, 'started');
    
    // Execute scraper - update path to match your docker setup
    $output = [];
    $return_var = 0;
    
    // Use the correct command from your setup
    $command = "cd /var/www/html/kidssmart && scrapy crawl " . escapeshellarg($scraper_name) . " 2>&1";
    exec($command, $output, $return_var);
    
    $status = ($return_var === 0) ? 'completed' : 'failed';
    $message = implode("\n", $output);
    
    // Log the completion
    $scraper->logScraperRun($scraper_name, $status, $message);
    
    $_SESSION['flash_message'] = "Scraper '$scraper_name' $status";
    $_SESSION['flash_type'] = ($status === 'completed') ? 'success' : 'danger';
    header('Location: admin_scrapers.php');
    exit;
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
    
    $_SESSION['flash_message'] = "Bulk run completed: " . implode(', ', $results);
    $_SESSION['flash_type'] = 'info';
    header('Location: admin_scrapers.php');
    exit;
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

// Get failed scrapers for alerts
try {
    $failed_scrapers = $scraper->getFailedScrapers();
} catch (Error $e) {
    // Fallback query if method doesn't exist
    $stmt = $appDb->prepare("SELECT DISTINCT scraper_name FROM scraping_logs WHERE status = 'failed' AND run_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $failed_scrapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scraper Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../static/css/style.css" rel="stylesheet">
    <style>
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
        .log-output {
            max-height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <div class="col-lg-9">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-primary">
                        <i class="fas fa-spider me-2"></i>Scraper Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh Status
                            </button>
                        </div>
                        <div class="btn-group">
                            <form method="post" class="d-inline">
                                <button type="submit" name="run_all_scrapers" class="btn btn-warning" 
                                        onclick="return confirm('Run all scrapers? This may take several minutes.')">
                                    <i class="fas fa-play-circle"></i> Run All Scrapers
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <!-- Failed Scrapers Alert -->
                <?php if (!empty($failed_scrapers)): ?>
                    <div class="alert alert-danger mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Attention:</strong> <?= count($failed_scrapers) ?> scraper(s) failed recently: 
                        <?= implode(', ', array_column($failed_scrapers, 'scraper_name')) ?>
                    </div>
                <?php endif; ?>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-start border-primary border-3">
                            <div class="card-body">
                                <h6 class="text-primary fw-bold mb-1">Total Activities</h6>
                                <h3 class="mb-0"><?= number_format($total_activities) ?></h3>
                                <small class="text-muted">Across all sources</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-success border-3">
                            <div class="card-body">
                                <h6 class="text-success fw-bold mb-1">Active Scrapers</h6>
                                <h3 class="mb-0"><?= count($available_scrapers) ?></h3>
                                <small class="text-muted">Configured scrapers</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-warning border-3">
                            <div class="card-body">
                                <h6 class="text-warning fw-bold mb-1">Recent Runs</h6>
                                <h3 class="mb-0"><?= count($recent_runs) ?></h3>
                                <small class="text-muted">Last 10 executions</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-start border-danger border-3">
                            <div class="card-body">
                                <h6 class="text-danger fw-bold mb-1">Failed Runs</h6>
                                <h3 class="mb-0"><?= count($failed_scrapers) ?></h3>
                                <small class="text-muted">Need attention</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Individual Scrapers -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 class="mb-3">
                            <i class="fas fa-cogs me-2"></i>Individual Scrapers
                        </h4>
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
                                    <p class="card-text text-muted mb-3"><?= htmlspecialchars($config['description']) ?></p>
                                    
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <small class="text-muted">Activities Collected:</small>
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
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            data-bs-toggle="modal" data-bs-target="#logModal<?= $scraper_name ?>">
                                        <i class="fas fa-eye"></i> View Logs
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Recent Scraper Runs -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
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
                                                    <th>Actions</th>
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
                                                                <button type="button" class="btn btn-outline-info btn-sm" 
                                                                        data-bs-toggle="modal" data-bs-target="#outputModal<?= $run['log_id'] ?>">
                                                                    <i class="fas fa-file-alt"></i> View Output
                                                                </button>
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
                                    <p class="text-muted">No recent scraper runs</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Modals for each scraper -->
    <?php foreach ($available_scrapers as $scraper_name => $config): ?>
        <div class="modal fade" id="logModal<?= $scraper_name ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-alt me-2"></i>
                            <?= htmlspecialchars($config['name']) ?> - Recent Logs
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $scraper_logs = array_filter($recent_runs, function($run) use ($scraper_name) {
                            return $run['scraper_name'] === $scraper_name;
                        });
                        ?>
                        <?php if (!empty($scraper_logs)): ?>
                            <?php foreach (array_slice($scraper_logs, 0, 3) as $log): ?>
                                <div class="mb-3">
                                    <h6>
                                        <span class="badge bg-<?= $log['status'] === 'completed' ? 'success' : ($log['status'] === 'failed' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($log['status']) ?>
                                        </span>
                                        <?= date('M j, Y g:i A', strtotime($log['run_at'])) ?>
                                    </h6>
                                    <?php if ($log['message']): ?>
                                        <div class="log-output">
                                            <?= nl2br(htmlspecialchars($log['message'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No output logged</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No logs available for this scraper</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Output Modals for recent runs -->
    <?php foreach ($recent_runs as $run): ?>
        <?php if ($run['message']): ?>
            <div class="modal fade" id="outputModal<?= $run['log_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                <i class="fas fa-terminal me-2"></i>
                                <?= htmlspecialchars($run['scraper_name']) ?> Output
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="log-output">
                                <?= nl2br(htmlspecialchars($run['message'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh status every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);

        // Show running status if any scrapers are running
        const runningScrapers = <?= json_encode(array_filter($available_scrapers, function($s) { return $s['status'] === 'started'; })) ?>;
        if (Object.keys(runningScrapers).length > 0) {
            // Refresh more frequently if scrapers are running
            setTimeout(() => {
                location.reload();
            }, 10000);
        }
    </script>
</body>
</html>