<?php
// PROFESSIONAL KidsSmart Homepage - Search-Focused Design
$page_title = 'KidsSmart - Find Amazing Activities for Your Children';
$page_description = 'Discover the best activities for children in your area. From sports and arts to educational programs, KidsSmart helps parents find engaging activities for kids of all ages.';
$page_keywords = 'kids activities, children programs, sports for kids, arts classes, educational activities, family fun, child development';

// Include required files using existing structure
require_once 'config/auth.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Program.php';
require_once __DIR__ . '/models/User.php';

// Initialize database connections
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();
$userDatabase = new Database('kidssmart_users');
$userDb = $userDatabase->getConnection();

// Initialize Program model with app database
$program = new Program($appDb);

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Rate limiting for search requests
function checkRateLimit($action = 'general', $limit = 100, $window = 3600) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "rate_limit_{$action}_{$ip}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $current_time = time();
    if ($current_time - $_SESSION[$key]['start_time'] > $window) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => $current_time];
    }
    
    $_SESSION[$key]['count']++;
    return $_SESSION[$key]['count'] <= $limit;
}

// Get featured programs, categories, and suburbs
$featured_programs = $program->getFeaturedPrograms(6);
$categories = $program->getCategories();
$suburbs = $program->getSuburbs();

// If user is logged in, get personalised recommendations
$recommended_programs = [];
if (is_logged_in()) {
    $user = new User($userDb);
    $user->user_id = get_current_user_id();
    if ($user->readOne()) {
        $recommended_programs = $program->getRecommendedActivities($user->suburb, $user->child_age_range, 3);
    }
}
$stats = ['total_activities' => 0, 'total_categories' => 0, 'total_providers' => 0];

// Handle quick search
$search_message = '';
if (isset($_GET['quick_search']) && !empty($_GET['q'])) {
    if (!checkRateLimit('search', 20, 3600)) {
        $search_message = 'Too many search requests. Please try again later.';
    } else {
        $search_query = filter_var(trim($_GET['q']), FILTER_SANITIZE_SPECIAL_CHARS);
        $search_location = filter_var(trim($_GET['location'] ?? ''), FILTER_SANITIZE_SPECIAL_CHARS);
        
        if (strlen($search_query) >= 2) {
            header("Location: search.php?q=" . urlencode($search_query) . "&location=" . urlencode($search_location));
            exit;
        } else {
            $search_message = 'Please enter at least 2 characters to search.';
        }
    }
}

// Get real data from database
try {
    if (file_exists('config/database.php')) {
        require_once 'config/database.php';
        if (class_exists('Database')) {
            $database = new Database('kidssmart_app');
            $db = $database->getConnection();
            
            if ($db) {
                // Get real statistics
                $count_stmt = $db->query("SELECT COUNT(*) as count FROM activities WHERE is_approved = 1");
                $activity_count = $count_stmt->fetch(PDO::FETCH_ASSOC);
                if ($activity_count) {
                    $stats['total_activities'] = $activity_count['count'];
                }
                
                // Get REAL categories with activity counts
                $cat_stmt = $db->query("
                    SELECT category, COUNT(*) as count 
                    FROM activities 
                    WHERE category IS NOT NULL AND category != '' AND is_approved = 1
                    GROUP BY category 
                    ORDER BY count DESC 
                    LIMIT 8
                ");
                $real_categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
                $stats['total_categories'] = count($real_categories);
                
                // Get provider count estimate
                $provider_stmt = $db->query("SELECT COUNT(DISTINCT provider_name) as count FROM activities WHERE provider_name IS NOT NULL");
                $provider_count = $provider_stmt->fetch(PDO::FETCH_ASSOC);
                if ($provider_count) {
                    $stats['total_providers'] = $provider_count['count'];
                }
                
                // Get featured activities
                $featured_stmt = $db->prepare("SELECT * FROM activities WHERE is_approved = 1 ORDER BY created_at DESC LIMIT 6");
                $featured_stmt->execute();
                $featured_programs = $featured_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
} catch (Exception $e) {
    error_log("Database connection failed in index.php: " . $e->getMessage());
    // Keep default values
}

// Fallback if no real categories found
if (empty($real_categories)) {
    $real_categories = [
        ['category' => 'Sports', 'count' => 150],
        ['category' => 'Arts', 'count' => 120],
        ['category' => 'Music', 'count' => 100],
        ['category' => 'Education', 'count' => 80]
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($page_keywords) ?>">
    
    <!-- Open Graph / Social Media Meta Tags -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($page_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($page_description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>">
    <meta property="og:site_name" content="KidsSmart">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/') ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="static/images/favicon.ico">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
    
    <!-- TEMPORARY CSS FIX UNTIL EXTERNAL CSS IS UPDATED -->
    <style>
    /* DROPDOWN VISIBILITY FIX */
    .dropdown-menu {
        z-index: 1050 !important;
        background-color: #ffffff !important;
        border: 1px solid rgba(0,0,0,.125) !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
        border-radius: 10px !important;
        padding: 10px 0 !important;
        min-width: 180px !important;
    }
    
    .navbar-dark .dropdown-menu {
        background-color: #ffffff !important;
    }
    
    .dropdown-item, 
    .navbar .dropdown-item {
        padding: 10px 20px !important;
        color: #333 !important;
        background-color: transparent !important;
        transition: all 0.2s ease !important;
        text-decoration: none !important;
        display: block !important;
    }
    
    .dropdown-item:hover,
    .dropdown-item:focus,
    .navbar .dropdown-item:hover,
    .navbar .dropdown-item:focus {
        background-color: rgba(0,123,255,0.1) !important;
        color: #007bff !important;
    }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- PROMINENT SEARCH HERO -->
    <section class="hero-search">
        <div class="container">
            <h1 class="search-title">Find Amazing Activities for Your Children</h1>
            <p class="search-subtitle">Search through <?= number_format($stats['total_activities']) ?> activities from trusted providers across Australia</p>
            
            <div class="search-card">
                <?php if ($search_message): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($search_message) ?>
                    </div>
                <?php endif; ?>
                
                <form method="GET" action="search.php" class="needs-validation" novalidate>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label for="search-query" class="form-label fw-bold">What activity are you looking for?</label>
                            <input type="text" 
                                   id="search-query" 
                                   name="q" 
                                   class="form-control main-search-input" 
                                   placeholder="e.g. soccer, arts, learning"
                                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                                   required 
                                   minlength="2"
                                   maxlength="100">
                        </div>
                        <div class="col-md-4">
                            <label for="search-location" class="form-label fw-bold">Location</label>
                            <input type="text" 
                                   id="search-location" 
                                   name="location" 
                                   class="form-control main-search-input" 
                                   placeholder="Suburb or postcode"
                                   value="<?= htmlspecialchars($_GET['location'] ?? '') ?>"
                                   maxlength="50">
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary main-search-btn w-100">
                                <i class="fas fa-search me-2"></i>Find Activities
                            </button>
                        </div>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-shield-alt me-1"></i>All activities are verified and from trusted providers
                    </small>
                </div>
            </div>
        </div>
    </section>

    <!-- STATISTICS -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-running category-icon text-primary"></i>
                        <div class="stat-number"><?= number_format($stats['total_activities']) ?></div>
                        <h5>Activities Available</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-list category-icon text-warning"></i>
                        <div class="stat-number"><?= number_format($stats['total_categories']) ?></div>
                        <h5>Categories</h5>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="fas fa-building category-icon text-success"></i>
                        <div class="stat-number"><?= number_format($stats['total_providers']) ?></div>
                        <h5>Trusted Providers</h5>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- REAL CATEGORIES -->
    <section class="py-5">
        <div class="container">
            <h2 class="section-title">Browse by Category</h2>
            <p class="text-center text-muted mb-5">Find activities in our most popular categories</p>
            
            <div class="row">
                <?php foreach (array_slice($real_categories, 0, 6) as $index => $category): ?>
                    <?php
                    // Assign icons based on category name
                    $icons = [
                        'swimming' => 'fas fa-swimmer text-primary',
                        'sports' => 'fas fa-futbol text-success', 
                        'sport' => 'fas fa-futbol text-success',
                        'arts' => 'fas fa-palette text-warning',
                        'art' => 'fas fa-palette text-warning',
                        'music' => 'fas fa-music text-info',
                        'education' => 'fas fa-graduation-cap text-purple',
                        'dance' => 'fas fa-music text-pink',
                        'martial' => 'fas fa-fist-raised text-danger'
                    ];
                    
                    $icon = 'fas fa-star text-primary'; // default
                    foreach ($icons as $key => $value) {
                        if (stripos($category['category'], $key) !== false) {
                            $icon = $value;
                            break;
                        }
                    }
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="category-card">
                            <i class="<?= $icon ?> category-icon"></i>
                            <h4><?= htmlspecialchars($category['category']) ?></h4>
                            <p class="text-muted"><?= number_format($category['count']) ?> activities available</p>
                            <a href="search.php?category=<?= urlencode($category['category']) ?>" class="btn btn-outline-primary">
                                Explore <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="categories.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View All Categories
                </a>
            </div>
        </div>
    </section>

    <!-- FEATURED ACTIVITIES -->
    <?php if (!empty($featured_programs)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="section-title">Featured Activities</h2>
            <p class="text-center text-muted mb-5">Recently added activities from trusted providers</p>
            
            <div class="row">
                <?php foreach (array_slice($featured_programs, 0, 6) as $activity): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card activity-card h-100">
                            <?php if (!empty($activity['image_url'])): ?>
                                <img src="<?= htmlspecialchars($activity['image_url']) ?>" 
                                     class="card-img-top" 
                                     alt="<?= htmlspecialchars($activity['title']) ?>" 
                                     style="height: 200px; object-fit: cover;"
                                     loading="lazy"
                                     onerror="this.src='https://via.placeholder.com/400x200/f8f9fa/6c757d?text=Activity+Image'">
                            <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-child fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($activity['title']) ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    <?= htmlspecialchars($activity['suburb']) ?>
                                    <?php if (!empty($activity['postcode'])): ?>
                                        , <?= htmlspecialchars($activity['postcode']) ?>
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($activity['category'])): ?>
                                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($activity['category']) ?></span>
                                <?php endif; ?>
                                <div class="mt-auto">
                                    <a href="program_detail.php?id=<?= urlencode($activity['activity_id']) ?>" 
                                       class="btn btn-outline-primary">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="search.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse All Activities
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CALL TO ACTION -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-6 fw-bold mb-3">Ready to Find the Perfect Activity?</h2>
                    <p class="lead">Join thousands of parents who trust KidsSmart to discover amazing activities for their children.</p>
                </div>
                <div class="col-lg-4 text-center">
                    <?php if (!is_logged_in()): ?>
                        <a href="signup.php" class="btn btn-warning btn-lg fw-bold me-3">
                            <i class="fas fa-user-plus me-2"></i>Sign Up Free
                        </a>
                    <?php else: ?>
                        <a href="dashboard.php" class="btn btn-warning btn-lg fw-bold">
                            <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();

        // Auto-focus search input
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-query');
            if (searchInput) {
                searchInput.focus();
            }
        });

        // Search suggestions (basic)
        const searchInput = document.getElementById('search-query');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    // Could add autocomplete suggestions here
                }
            });
        }
    </script>
</body>
</html>