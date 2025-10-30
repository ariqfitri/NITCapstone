<?php
// Only start session if one hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Scraper.php';
require_once __DIR__ . '/../models/Program.php';

// Check if user is admin
if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

$scraper = new Scraper($appDb);
$program = new Program($appDb);

// Handle scraper execution
if (isset($_POST['run_scraper'])) {
    $scraper_name = $_POST['scraper_name'];
    
    // Log the start
    $scraper->logScraperRun($scraper_name, 'started');
    
    // Execute scraper in background
    $output = [];
    $return_var = 0;
    
    $command = "cd /var/www/html/kidssmart && scrapy crawl " . escapeshellarg($scraper_name) . " 2>&1";
    exec($command, $output, $return_var);
    
    $status = ($return_var === 0) ? 'completed' : 'failed';
    $message = implode("\n", $output);
    
    $scraper->logScraperRun($scraper_name, $status, $message);
    
    $_SESSION['flash_message'] = "Scraper $scraper_name $status";
    header('Location: admin_scrapers.php');
    exit;
}

// Get statistics
$scraper_stats = $scraper->getScraperStats();
$recent_runs = $scraper->getRecentRuns();
$total_activities = $program->getTotalProgramsCount();
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
                            <button type="button" class="btn btn-outline-primary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <!-- Scraper Controls and Stats Row -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-play me-2"></i>Available Scrapers
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="d-grid gap-3">
                                    <button type="submit" name="run_scraper" value="activities" 
                                            class="btn btn-primary btn-lg">
                                        <i class="fas fa-spider me-2"></i> Run Activities Spider
                                        <br><small class="d-block">Scrapes from ActiveActivities.com.au</small>
                                    </button>
                                    <button type="submit" name="run_scraper" value="kidsbook" 
                                            class="btn btn-info btn-lg">
                                        <i class="fas fa-book me-2"></i> Run KidsBook Spider
                                        <br><small class="d-block">Scrapes from KidsBook.com.au</small>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Scraper Statistics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <h3 class="text-primary"><?= $total_activities ?></h3>
                                        <small class="text-muted">Total Activities</small>
                                    </div>
                                    <div class="col-6">
                                        <h3 class="text-success"><?= count($scraper_stats) ?></h3>
                                        <small class="text-muted">Data Sources</small>
                                    </div>
                                </div>
                                
                                <?php if ($scraper_stats): ?>
                                    <?php foreach ($scraper_stats as $stat): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                            <div>
                                                <span class="badge bg-primary"><?= htmlspecialchars($stat['source_name']) ?></span>
                                                <?php if ($stat['last_scraped']): ?>
                                                    <br><small class="text-muted">
                                                        Last: <?= date('M j, g:i A', strtotime($stat['last_scraped'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            <span class="fw-bold text-success"><?= $stat['count'] ?> activities</span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-database fa-2x mb-2"></i>
                                        <p>No scraper data available</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scraper Runs -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Scraper Runs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_runs): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>Scraper</th>
                                            <th>Status</th>
                                            <th>Message</th>
                                            <th>Run At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_runs as $run): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary"><?= htmlspecialchars($run['scraper_name']) ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = [
                                                        'started' => 'warning',
                                                        'completed' => 'success', 
                                                        'failed' => 'danger'
                                                    ][$run['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $status_class ?>">
                                                        <i class="fas fa-<?= $run['status'] === 'completed' ? 'check' : ($run['status'] === 'failed' ? 'times' : 'spinner') ?> me-1"></i>
                                                        <?= htmlspecialchars($run['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($run['message'])): ?>
                                                        <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#message-<?= $run['log_id'] ?>">
                                                            <i class="fas fa-eye"></i> View Log
                                                        </button>
                                                        <div class="collapse mt-2" id="message-<?= $run['log_id'] ?>">
                                                            <div class="card card-body">
                                                                <small class="font-monospace"><?= htmlspecialchars(substr($run['message'], 0, 500)) ?><?= strlen($run['message']) > 500 ? '...' : '' ?></small>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">No message</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?= date('M j, g:i A', strtotime($run['run_at'])) ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <h5>No recent scraper runs</h5>
                                <p class="text-muted">Run a scraper to see execution logs here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function refreshStats() {
        location.reload();
    }
    </script>
</body>
</html>