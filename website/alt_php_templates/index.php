<?php
// Include database configuration and auth
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';
require_once __DIR__ . '/models/User.php';

// Initialize database connections
$appDatabase = new Database('kidssmart_app');
$appDb = $appDatabase->getConnection();

$userDatabase = new Database('kidssmart_users');
$userDb = $userDatabase->getConnection();

// Initialize Program model with app database
$program = new Program($appDb);

// Get featured programs, categories, and suburbs
$featured_programs = $program->getFeaturedPrograms(6);
$categories = $program->getCategories();
$suburbs = $program->getSuburbs();

// If user is logged in, get personalised recommendations
$recommended_programs = [];
if (is_logged_in()) {
    $user = new User($userDb);  // ✅ CORRECT! Using users database for User model
    $user->user_id = get_current_user_id();
    if ($user->readOne()) {
        $recommended_programs = $program->getRecommendedActivities($user->suburb, $user->child_age_range, 3);
    }
}
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
    <?php include 'includes/header.php'; ?>

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
                    <?php if (!is_logged_in()): ?>
                        <a href="signup.php" class="btn btn-outline-primary btn-lg mt-3">
                            <i class="fas fa-user-plus"></i> Join Free
                        </a>
                    <?php endif; ?>
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

    <!-- Personalised Recommendations for logged-in users -->
    <?php if (is_logged_in() && count($recommended_programs) > 0): ?>
    <section class="personalised-recommendations py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Recommended for You</h2>
            <div class="row">
                <?php foreach ($recommended_programs as $activity): ?>
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
            </div>
        </div>
    </section>
    <?php endif; ?>

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

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>