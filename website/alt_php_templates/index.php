<?php
// Include database configuration
require_once 'config/database.php';
require_once 'models/Program.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Program model
$program = new Program($db);

// Get featured programs (recently added)
$featured_programs = $program->getFeaturedPrograms(6);

// Get categories for filter
$categories = $program->getCategories();

// Get suburbs for filter
$suburbs = $program->getSuburbs();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KidsSmart - Find Kids Activities & Programs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-child"></i> KidsSmart
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="search.php"><i class="fas fa-search"></i> Find Activities</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-list"></i> Categories</a>
                <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section bg-light py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-primary">Find Amazing Activities for Your Kids</h1>
                    <p class="lead">Discover the best sports, arts, education, and recreational programs in your area.</p>
                    <a href="search.php" class="btn btn-primary btn-lg mt-3">
                        <i class="fas fa-search"></i> Explore Activities
                    </a>
                </div>
                <div class="col-lg-6">
                    <img src="static/images/hero-image.jpg" alt="Kids Activities" class="img-fluid rounded" style="max-height: 400px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Search -->
    <section class="quick-search py-4 bg-white">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form action="search.php" method="get" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search activities...">
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="suburb" class="form-select">
                                <option value="">All Suburbs</option>
                                <?php foreach ($suburbs as $sub): ?>
                                    <option value="<?= htmlspecialchars($sub) ?>"><?= htmlspecialchars($sub) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Programs -->
    <section class="featured-programs py-5">
        <div class="container">
            <h2 class="text-center mb-5">Featured Activities</h2>
            <div class="row">
                <?php if ($featured_programs && count($featured_programs) > 0): ?>
                    <?php foreach ($featured_programs as $activity): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 shadow-sm">
                                <?php if (!empty($activity['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($activity['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($activity['title']) ?>" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="fas fa-child fa-3x text-white"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($activity['title']) ?></h5>
                                    <p class="card-text text-muted">
                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activity['suburb']) ?>
                                        <?php if (!empty($activity['postcode'])): ?>
                                            , <?= htmlspecialchars($activity['postcode']) ?>
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($activity['category'])): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($activity['category']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($activity['age_range'])): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($activity['age_range']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-footer">
                                    <a href="program_detail.php?id=<?= $activity['activity_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No featured programs available at the moment.</p>
                        <a href="search.php" class="btn btn-primary">Browse All Activities</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section py-4 bg-primary text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3">
                    <h3 class="fw-bold"><?= $program->getTotalProgramsCount() ?></h3>
                    <p>Activities</p>
                </div>
                <div class="col-md-3">
                    <h3 class="fw-bold"><?= count($categories) ?></h3>
                    <p>Categories</p>
                </div>
                <div class="col-md-3">
                    <h3 class="fw-bold"><?= count($suburbs) ?></h3>
                    <p>Suburbs</p>
                </div>
                <div class="col-md-3">
                    <h3 class="fw-bold">24/7</h3>
                    <p>Updated</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>KidsSmart</h5>
                    <p>Helping parents find the best activities for their children.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="search.php" class="text-white">Find Activities</a></li>
                        <li><a href="categories.php" class="text-white">Categories</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Connect</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="text-center">
                <p>&copy; 2024 KidsSmart. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>