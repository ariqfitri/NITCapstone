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

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="row">
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">50+</span>
                        <small>Pages & Features</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">1000+</span>
                        <small>Activities Listed</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">15+</span>
                        <small>Activity Categories</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-item">
                        <span class="stat-number">100+</span>
                        <small>Trusted Providers</small>
                    </div>
                </div>
            </div>
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
                    <a href="contact.php" class="page-link">
                        <strong>Contact</strong>
                        <div class="page-description">Get in touch with our support team</div>
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
                            <div class="page-description">Create a new account (free)</div>
                        </a>
                        <a href="forgot-password.php" class="page-link">
                            <strong>Forgot Password</strong>
                            <div class="page-description">Reset your account password</div>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Activity Categories -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-list text-primary me-2"></i>Activity Categories</h3>
                    <a href="categories.php?cat=swimming" class="page-link">
                        <strong>Swimming & Water Sports</strong>
                        <div class="page-description">Swimming lessons, water safety, diving programs</div>
                    </a>
                    <a href="categories.php?cat=sports" class="page-link">
                        <strong>Sports & Fitness</strong>
                        <div class="page-description">Soccer, basketball, gymnastics, martial arts</div>
                    </a>
                    <a href="categories.php?cat=arts" class="page-link">
                        <strong>Arts & Crafts</strong>
                        <div class="page-description">Drawing, painting, pottery, creative workshops</div>
                    </a>
                    <a href="categories.php?cat=music" class="page-link">
                        <strong>Music & Performing Arts</strong>
                        <div class="page-description">Piano, guitar, singing, dance, drama classes</div>
                    </a>
                    <a href="categories.php?cat=education" class="page-link">
                        <strong>Educational Programs</strong>
                        <div class="page-description">Tutoring, STEM, coding, language classes</div>
                    </a>
                    <a href="categories.php?cat=outdoor" class="page-link">
                        <strong>Outdoor Activities</strong>
                        <div class="page-description">Nature programs, camping, adventure activities</div>
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Support & Help -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-life-ring text-primary me-2"></i>Support & Help</h3>
                    <a href="help.php" class="page-link">
                        <strong>Help Center</strong>
                        <div class="page-description">Comprehensive help and user guides</div>
                    </a>
                    <a href="faq.php" class="page-link">
                        <strong>FAQ</strong>
                        <div class="page-description">Frequently asked questions and answers</div>
                    </a>
                    <a href="feedback.php" class="page-link">
                        <strong>Feedback</strong>
                        <div class="page-description">Share your suggestions and feedback</div>
                    </a>
                    <a href="report-issue.php" class="page-link">
                        <strong>Report Issue</strong>
                        <div class="page-description">Report technical problems or concerns</div>
                    </a>
                    <a href="accessibility.php" class="page-link">
                        <strong>Accessibility</strong>
                        <div class="page-description">Information about our accessibility features</div>
                    </a>
                </div>

                <!-- Legal & Policies -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-gavel text-primary me-2"></i>Legal & Policies</h3>
                    <a href="privacy-policy.php" class="page-link">
                        <strong>Privacy Policy</strong>
                        <div class="page-description">How we collect, use, and protect your data</div>
                    </a>
                    <a href="terms-conditions.php" class="page-link">
                        <strong>Terms & Conditions</strong>
                        <div class="page-description">Rules and guidelines for using our services</div>
                    </a>
                    <a href="cookie-policy.php" class="page-link">
                        <strong>Cookie Policy</strong>
                        <div class="page-description">Information about cookies and tracking</div>
                    </a>
                    <a href="data-protection.php" class="page-link">
                        <strong>Data Protection</strong>
                        <div class="page-description">Our commitment to data security</div>
                    </a>
                </div>

                <!-- Additional Features -->
                <div class="sitemap-section">
                    <h3><i class="fas fa-star text-primary me-2"></i>Features & Tools</h3>
                    <a href="advanced-search.php" class="page-link">
                        <strong>Advanced Search</strong>
                        <div class="page-description">Detailed search with multiple filters</div>
                    </a>
                    <a href="activity-map.php" class="page-link">
                        <strong>Activity Map</strong>
                        <div class="page-description">Interactive map of activities by location</div>
                    </a>
                    <a href="age-appropriate.php" class="page-link">
                        <strong>Age-Appropriate Activities</strong>
                        <div class="page-description">Find activities suitable for specific age groups</div>
                    </a>
                    <a href="seasonal-activities.php" class="page-link">
                        <strong>Seasonal Activities</strong>
                        <div class="page-description">Activities organized by season and holidays</div>
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
                        <h5 class="mb-0"><i class="fas fa-rss me-2"></i>Stay Updated</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Get notified about new activities</p>
                        <a href="newsletter.php" class="btn btn-success w-100">
                            <i class="fas fa-envelope me-2"></i>Subscribe to Newsletter
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-mobile-alt me-2"></i>Mobile App</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Take KidsSmart on the go</p>
                        <a href="mobile-app.php" class="btn btn-info w-100">
                            <i class="fas fa-download me-2"></i>Coming Soon
                        </a>
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