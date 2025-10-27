<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Scraper.php';
require_once __DIR__ . '/../models/Program.php';

// Check if user is admin
if (!is_logged_in() || !is_admin()) {
    header('Location: ../login.php');
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
</head>
<body>
    <?php include '../includes/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Scraper Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="refreshStats()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <!-- Scraper Controls -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Available Scrapers</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="d-grid gap-2">
                                    <button type="submit" name="run_scraper" value="activities" 
                                            class="btn btn-primary">
                                        <i class="fas fa-spider"></i> Run Activities Spider
                                    </button>
                                    <button type="submit" name="run_scraper" value="kidsbook" 
                                            class="btn btn-info">
                                        <i class="fas fa-book"></i> Run KidsBook Spider
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Scraper Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <h3><?= $total_activities ?></h3>
                                        <small class="text-muted">Total Activities</small>
                                    </div>
                                    <div class="col-6">
                                        <h3><?= count($scraper_stats) ?></h3>
                                        <small class="text-muted">Data Sources</small>
                                    </div>
                                </div>
                                <hr>
                                <?php if ($scraper_stats): ?>
                                    <?php foreach ($scraper_stats as $stat): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-primary"><?= htmlspecialchars($stat['source_name']) ?></span>
                                            <span class="fw-bold"><?= $stat['count'] ?> activities</span>
                                        </div>
                                        <?php if ($stat['last_scraped']): ?>
                                            <small class="text-muted">
                                                Last: <?= date('M j, g:i A', strtotime($stat['last_scraped'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">No scraper data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scraper Runs -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Scraper Runs</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_runs): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
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
                                                        <?= htmlspecialchars($run['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?= !empty($run['message']) ? htmlspecialchars(substr($run['message'], 0, 100)) . '...' : 'No message' ?>
                                                    </small>
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
                            <p class="text-muted">No recent scraper runs</p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
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