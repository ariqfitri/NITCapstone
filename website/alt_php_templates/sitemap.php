<?php
// Site Map Page
$page_title = 'Site Map - KidsSmart';
$page_description = 'Find your way around KidsSmart with our comprehensive site map. Quick access to all pages and features.';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

require_once 'config/auth.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <meta name="robots" content="index, follow">
    
    <link rel="canonical" href="<?= htmlspecialchars($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/sitemap.php') ?>">
    <link rel="icon" type="image/x-icon" href="static/images/favicon.ico">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
    
    <style>
        .sitemap-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #0d6efd;
        }
        .sitemap-section h3 {
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .page-link {
            display: block;
            padding: 8px 0;
            text-decoration: none;
            color: #495057;
            border-bottom: 1px solid #e9ecef;
            transition: all 0.2s ease;
        }
        .page-link:hover {
            color: #0d6efd;
            padding-left: 15px;
            border-left: 3px solid #0d6efd;
        }
        .page-link:last-child {
            border-bottom: none;
        }
        .page-description {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 3px;
        }
        .stats-grid {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .stat-item {
            text-align: center;
            padding: 15px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main class="container mt-4 mb-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Site Map</li>
            </ol>
        </nav>

        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="display-4 fw-bold text-primary">
                <i class="fas fa-sitemap me-3"></i>Site Map
            </h1>
            <p class="lead text-muted">Navigate KidsSmart with ease - find everything you need</p>
        </div>

        <div class="row">
            <div class="col-lg-6">
                <!-- Main Pages -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-home text-primary me-2"></i>Main Pages</h3>
                    <a href="index.php" class="page-link">
                        <strong>Home</strong>
                        <div class="page-description">Welcome page with search and featured activities</div>
                    </a>
                    <a href="search.php" class="page-link">
                        <strong>Find Activities</strong>
                        <div class="page-description">Search for children's activities by location and type</div>
                    </a>
                    <a href="categories.php" class="page-link">
                        <strong>Categories</strong>
                        <div class="page-description">Browse activities by category (sports, arts, music, etc.)</div>
                    </a>
                    <a href="about.php" class="page-link">
                        <strong>About Us</strong>
                        <div class="page-description">Learn about KidsSmart and our mission</div>
                    </a>
                </div>

                <!-- User Account Pages -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-user text-primary me-2"></i>Account & Profile</h3>
                    <?php if (is_logged_in()): ?>
                        <a href="dashboard.php" class="page-link">
                            <strong>Dashboard</strong>
                            <div class="page-description">Your personal activity dashboard</div>
                        </a>
                        <a href="profile.php" class="page-link">
                            <strong>Profile Settings</strong>
                            <div class="page-description">Manage your account information and preferences</div>
                        </a>
                        <a href="favourites.php" class="page-link">
                            <strong>My Favourites</strong>
                            <div class="page-description">View and manage your saved activities</div>
                        </a>
                        <a href="logout.php" class="page-link">
                            <strong>Logout</strong>
                            <div class="page-description">Sign out of your account</div>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="page-link">
                            <strong>Login</strong>
                            <div class="page-description">Sign in to your KidsSmart account</div>
                        </a>
                        <a href="signup.php" class="page-link">
                            <strong>Sign Up</strong>
                            <div class="page-description">Create a new KidsSmart account</div>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Legal -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-gavel text-primary me-2"></i>Legal</h3>
                    <a href="privacy-policy.php" class="page-link">
                        <strong>Privacy Policy</strong>
                        <div class="page-description">How we collect, use, and protect your data</div>
                    </a>
                    <a href="terms-conditions.php" class="page-link">
                        <strong>Terms & Conditions</strong>
                        <div class="page-description">Rules and guidelines for using our services</div>
                    </a>
                    <a href="sitemap.php" class="page-link">
                        <strong>Site Map</strong>
                        <div class="page-description">Navigate all pages on KidsSmart</div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Additional Information -->
        <div class="row mt-5">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Quick Search</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Looking for something specific?</p>
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search site..." id="siteSearch">
                            <button class="btn btn-outline-primary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>Explore Activities</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Ready to find activities for your kids?</p>
                        <a href="search.php" class="btn btn-success w-100">
                            <i class="fas fa-search me-2"></i>Start Searching
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-heart me-2"></i>Join KidsSmart</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Create an account to save your favourites</p>
                        <?php if (!is_logged_in()): ?>
                            <a href="signup.php" class="btn btn-info w-100">
                                <i class="fas fa-user-plus me-2"></i>Sign Up Free
                            </a>
                        <?php else: ?>
                            <a href="dashboard.php" class="btn btn-info w-100">
                                <i class="fas fa-tachometer-alt me-2"></i>View Dashboard
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- XML Sitemap -->
        <div class="text-center mt-5 p-4 bg-light rounded">
            <h4><i class="fas fa-code text-primary me-2"></i>For Developers & SEO</h4>
            <p class="text-muted mb-3">Looking for machine-readable site structure?</p>
            <div class="btn-group" role="group">
                <a href="/sitemap.xml" class="btn btn-outline-primary">
                    <i class="fas fa-file-code me-2"></i>XML Sitemap
                </a>
                <a href="/robots.txt" class="btn btn-outline-secondary">
                    <i class="fas fa-robot me-2"></i>Robots.txt
                </a>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const siteSearchInput = document.getElementById('siteSearch');
            const searchButton = siteSearchInput.nextElementSibling;
            
            function performSearch() {
                const query = siteSearchInput.value.trim();
                if (query) {
                    window.location.href = `search.php?q=${encodeURIComponent(query)}`;
                }
            }
            
            searchButton.addEventListener('click', performSearch);
            siteSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    performSearch();
                }
            });
            
            // Live search highlighting
            const pageLinks = document.querySelectorAll('.page-link');
            siteSearchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                
                pageLinks.forEach(link => {
                    const text = link.textContent.toLowerCase();
                    
                    if (query === '') {
                        link.style.backgroundColor = '';
                    } else if (text.includes(query)) {
                        link.style.backgroundColor = '#fff3cd';
                    } else {
                        link.style.backgroundColor = '';
                    }
                });
            });
        });
    </script>
</body>
</html>