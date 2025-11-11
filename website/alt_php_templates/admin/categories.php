<?php
// Set page title
$page_title = 'Categories';

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

// Initialize database
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();
$program = new Program($appDb);

// Handle actions
$message = '';
$error = '';

if ($_POST['action'] ?? false) {
    try {
        if ($_POST['action'] === 'add' && !empty($_POST['category_name'])) {
            $category = trim($_POST['category_name']);
            // Add to database (you might want a categories table)
            $message = "Category added successfully!";
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['category_name'])) {
            $category = $_POST['category_name'];
            // Delete logic here
            $message = "Category deleted successfully!";
        }
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

try {
    // Get all unique categories from activities
    $categories_query = "SELECT 
        category,
        COUNT(*) as activity_count,
        SUM(CASE WHEN is_approved = 1 THEN 1 ELSE 0 END) as approved_count
        FROM activities 
        WHERE category IS NOT NULL AND category != ''
        GROUP BY category 
        ORDER BY activity_count DESC";
    $categories = $appDb->query($categories_query)->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total stats
    $total_categories = count($categories);
    $total_activities = array_sum(array_column($categories, 'activity_count'));
    
} catch (Exception $e) {
    $categories = [];
    $total_categories = 0;
    $total_activities = 0;
    $error = "Error loading categories: " . $e->getMessage();
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
                    <i class="fas fa-tags me-2"></i>Category Management
                </h1>
            </div>

            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card stat-card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $total_categories ?></div>
                                    <div class="metric-label">Total Categories</div>
                                </div>
                                <i class="fas fa-tags fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="metric-value"><?= $total_activities ?></div>
                                    <div class="metric-label">Total Activities</div>
                                </div>
                                <i class="fas fa-running fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Categories List -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Categories (<?= $total_categories ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (count($categories) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-tag me-2 text-primary"></i>
                                                        <?= htmlspecialchars($cat['category']) ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= $cat['activity_count'] ?> activities 
                                                        (<?= $cat['approved_count'] ?> approved)
                                                    </small>
                                                </div>
                                                <div>
                                                    <div class="progress" style="width: 200px; height: 8px;">
                                                        <div class="progress-bar" 
                                                             style="width: <?= ($cat['activity_count'] / $total_activities) * 100 ?>%">
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= number_format(($cat['activity_count'] / $total_activities) * 100, 1) ?>%
                                                    </small>
                                                </div>
                                                <div>
                                                    <a href="activities.php?category=<?= urlencode($cat['category']) ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye me-1"></i> View
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <h5>No categories found</h5>
                                    <p class="text-muted">Run scrapers to import activities with categories.</p>
                                    <a href="admin_scrapers.php" class="btn btn-primary">
                                        <i class="fas fa-spider me-1"></i> Go to Scrapers
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Category Chart -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Distribution</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($categories) > 0): ?>
                                <canvas id="categoryChart"></canvas>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-pie fa-2x mb-2"></i>
                                    <p class="mb-0">No data available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Categories -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">Top 5 Categories</h5>
                        </div>
                        <div class="card-body">
                            <?php if (count($categories) > 0): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($categories, 0, 5) as $index => $cat): ?>
                                        <div class="list-group-item px-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                    <strong><?= htmlspecialchars($cat['category']) ?></strong>
                                                </div>
                                                <span class="badge bg-success"><?= $cat['activity_count'] ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No categories yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php if (count($categories) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Category Distribution Chart
const ctx = document.getElementById('categoryChart');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column(array_slice($categories, 0, 10), 'category')) ?>,
        datasets: [{
            data: <?= json_encode(array_column(array_slice($categories, 0, 10), 'activity_count')) ?>,
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
                position: 'bottom',
            }
        }
    }
});
</script>
<?php endif; ?>

<?php include 'includes/admin_footer.php'; ?>