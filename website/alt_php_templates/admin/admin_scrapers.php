<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
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
    $scraper_name = $_POST['run_scraper'];
    
    // Validate scraper name
    $allowed_scrapers = ['activities', 'kidsbook'];
    if (!in_array($scraper_name, $allowed_scrapers)) {
        $_SESSION['flash_message'] = "Invalid scraper name";
        header('Location: admin_scrapers.php');
        exit;
    }
    
    // Log the start
    $scraper->logScraperRun($scraper_name, 'started');
    
    // Execute scraper using docker compose
    $output = [];
    $return_var = 0;
    
    // Get the project directory (assuming we're in /var/www/html/admin)
    $project_dir = '/var/www/html';
    
    // Build the docker command with proper escaping
    $command = 'docker run --rm --network nitcapstone_kidssmart_network ' .
               '--env DB_HOST=database ' .
               '--env DB_NAME=kidssmart_app ' .
               '--env DB_USER=app_user ' .
               '--env "DB_PASSWORD=AppPass123!" ' .
               'nitcapstone-scraper bash -c "cd /app/kidssmart && scrapy crawl ' . 
               escapeshellarg($scraper_name) . '" 2>&1';
    
    // Debug: Log the command and execution details
    error_log("SCRAPER COMMAND: " . $command);
    error_log("SCRAPER START TIME: " . date('Y-m-d H:i:s'));
    
    // Execute with timeout to prevent hanging
    $start_time = time();
    exec($command, $output, $return_var);
    $end_time = time();
    $execution_time = $end_time - $start_time;
    
    // Debug: Log the results
    error_log("SCRAPER EXECUTION TIME: {$execution_time}s");
    error_log("SCRAPER RETURN CODE: $return_var");
    error_log("SCRAPER OUTPUT: " . implode("\n", $output));
    
    // If failed, try to get more info
    if ($return_var !== 0) {
        $debug_output = [];
        exec("docker images | grep scraper", $debug_output);
        error_log("SCRAPER IMAGES: " . implode("\n", $debug_output));
    }
    
    // Determine status
    $status = ($return_var === 0) ? 'completed' : 'failed';
    $message = implode("\n", array_slice($output, -50)); // Last 50 lines
    
    // Add execution time to message
    $message = "Execution time: {$execution_time}s\n\n" . $message;
    
    // Log the result
    $scraper->logScraperRun($scraper_name, $status, $message);
    
    // Set flash message
    if ($status === 'completed') {
        $_SESSION['flash_message'] = "✅ Scraper $scraper_name completed successfully in {$execution_time}s!";
    } else {
        $_SESSION['flash_message'] = "❌ Scraper $scraper_name failed after {$execution_time}s. Check logs for details.";
    }
    
    header('Location: admin_scrapers.php');
    exit;
}

// Get statistics
try {
    $scraper_stats = $scraper->getScraperStats();
    $recent_runs = $scraper->getRecentRuns();
    $total_activities = $program->getTotalProgramsCount();
} catch (Exception $e) {
    $scraper_stats = [];
    $recent_runs = [];
    $total_activities = 0;
    $error = "Error loading scraper data: " . $e->getMessage();
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
    <link href="css/admin_style.css" rel="stylesheet">
    <style>
    .scraper-btn {
        transition: all 0.3s ease;
    }
    .scraper-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .running {
        background: linear-gradient(-45deg, #ee7752, #e73c7e, #23a6d5, #23d5ab);
        background-size: 400% 400%;
        animation: gradient 2s ease infinite;
        color: white !important;
        border: none !important;
    }
    @keyframes gradient {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-spider me-2"></i>Scraper Management
                    </h1>
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
                        <?= $_SESSION['flash_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message']); ?>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Scraper Controls -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-rocket me-2"></i>One-Click Execution
                                </h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="d-grid gap-3">
                                    <button type="submit" name="run_scraper" value="activities" 
                                            class="btn btn-primary btn-lg scraper-btn"
                                            onclick="startScraper(this, 'Activities')">
                                        <i class="fas fa-play me-2"></i>
                                        Run Activities Spider
                                        <small class="d-block">Scrapes activeactivities.com.au</small>
                                    </button>
                                    
                                    <button type="submit" name="run_scraper" value="kidsbook" 
                                            class="btn btn-info btn-lg scraper-btn"
                                            onclick="startScraper(this, 'KidsBook')">
                                        <i class="fas fa-spider me-2"></i>
                                        Run KidsBook Spider
                                        <small class="d-block">Scrapes kidsbook.com.au</small>
                                    </button>
                                </form>
                                
                                <div class="mt-3 p-3 bg-light rounded">
                                    <h6 class="mb-2">
                                        <i class="fas fa-info-circle text-primary me-2"></i>
                                        How it works
                                    </h6>
                                    <ul class="mb-0 small">
                                        <li>Click button to start scraper immediately</li>
                                        <li>Page will reload when scraping is complete</li>
                                        <li>Check "Recent Runs" for execution details</li>
                                        <li>New activities appear in statistics automatically</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Statistics
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-3">
                                    <div class="col-6">
                                        <div class="p-3 bg-primary bg-opacity-10 rounded">
                                            <h2 class="text-primary mb-1"><?= number_format($total_activities) ?></h2>
                                            <small class="text-muted">Total Activities</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-info bg-opacity-10 rounded">
                                            <h2 class="text-info mb-1"><?= count($scraper_stats) ?></h2>
                                            <small class="text-muted">Active Sources</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($scraper_stats): ?>
                                    <?php foreach ($scraper_stats as $stat): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <span class="badge bg-primary"><?= htmlspecialchars($stat['source_name']) ?></span>
                                            <span class="fw-bold"><?= number_format($stat['count']) ?> activities</span>
                                        </div>
                                        <?php if (isset($stat['last_scraped']) && $stat['last_scraped']): ?>
                                            <small class="text-muted d-block mb-2">
                                                Last updated: <?= date('M j, g:i A', strtotime($stat['last_scraped'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-database fa-2x mb-2"></i>
                                        <p class="mb-0">No data yet. Run a scraper to get started!</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scraper Runs -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>Recent Executions
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_runs): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Scraper</th>
                                            <th>Status</th>
                                            <th>Details</th>
                                            <th>Executed At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_runs as $run): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary fs-6"><?= htmlspecialchars($run['scraper_name']) ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_config = [
                                                        'started' => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Running'],
                                                        'completed' => ['class' => 'success', 'icon' => 'check-circle', 'text' => 'Success'], 
                                                        'failed' => ['class' => 'danger', 'icon' => 'times-circle', 'text' => 'Failed']
                                                    ];
                                                    $config = $status_config[$run['status']] ?? ['class' => 'secondary', 'icon' => 'question', 'text' => 'Unknown'];
                                                    ?>
                                                    <span class="badge bg-<?= $config['class'] ?> fs-6">
                                                        <i class="fas fa-<?= $config['icon'] ?> me-1"></i>
                                                        <?= $config['text'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($run['message'])): ?>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars(substr($run['message'], 0, 100)) ?>
                                                            <?php if (strlen($run['message']) > 100): ?>...<?php endif; ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted">No details</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small><?= date('M j, Y g:i A', strtotime($run['run_at'])) ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-rocket fa-3x text-muted mb-3"></i>
                                <h5>Ready for Launch!</h5>
                                <p class="text-muted">Click the buttons above to run your first scraper.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function startScraper(button, name) {
        // Add running animation
        button.classList.add('running');
        button.innerHTML = `
            <i class="fas fa-spinner fa-spin me-2"></i>
            Running ${name} Spider...
            <small class="d-block">This may take several minutes</small>
        `;
        
        // Show page loading overlay
        document.body.style.cursor = 'wait';
        
        // ACTUALLY SUBMIT THE FORM!
        button.form.submit();
    }

    function refreshStats() {
        location.reload();
    }
    
    // Auto-refresh if there are running scrapers
    document.addEventListener('DOMContentLoaded', function() {
        const runningStatus = document.querySelector('.badge.bg-warning');
        if (runningStatus) {
            setTimeout(() => {
                location.reload();
            }, 10000); // Check every 10 seconds
        }
    });
    </script>
</body>
</html>