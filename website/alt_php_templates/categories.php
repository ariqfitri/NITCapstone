<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';

$database = new Database('kidssmart_app');
$db = $database->getConnection();
$program = new Program($db);

// Get all categories with counts
$categories = $program->getCategories();
$category_counts = [];

foreach ($categories as $category) {
    $programs_in_category = $program->searchPrograms('', $category, '', 1, 1000);
    $category_counts[$category] = count($programs_in_category);
}

// Get all suburbs with counts
$suburbs = $program->getSuburbs();
$suburb_counts = [];

foreach ($suburbs as $suburb) {
    $programs_in_suburb = $program->searchPrograms('', '', $suburb, 1, 1000);
    $suburb_counts[$suburb] = count($programs_in_suburb);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1>Browse by Category & Location</h1>
        <p class="lead">Explore activities by category or find programs in your area.</p>

        <!-- Categories Section -->
        <section class="mb-5">
            <h2 class="mb-4">Activity Categories</h2>
            <div class="row">
                <?php foreach ($categories as $category): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="card h-100 text-center">
                            <div class="card-body">
                                <i class="fas fa-futbol fa-2x text-primary mb-3"></i>
                                <h5 class="card-title"><?= htmlspecialchars($category) ?></h5>
                                <p class="card-text text-muted">
                                    <?= $category_counts[$category] ?> program<?= $category_counts[$category] != 1 ? 's' : '' ?>
                                </p>
                                <a href="search.php?category=<?= urlencode($category) ?>" class="btn btn-outline-primary btn-sm">
                                    Explore
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Suburbs Section -->
        <section class="mb-5">
            <h2 class="mb-4">Browse by Suburb</h2>
            <div class="row">
                <?php foreach ($suburbs as $suburb): ?>
                    <div class="col-lg-2 col-md-3 col-sm-4 col-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center p-3">
                                <i class="fas fa-map-marker-alt text-danger mb-2"></i>
                                <h6 class="card-title mb-1"><?= htmlspecialchars($suburb) ?></h6>
                                <small class="text-muted">
                                    <?= $suburb_counts[$suburb] ?> program<?= $suburb_counts[$suburb] != 1 ? 's' : '' ?>
                                </small>
                                <a href="search.php?suburb=<?= urlencode($suburb) ?>" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Popular Combinations -->
        <section class="mb-5">
            <h2 class="mb-4">Popular Searches</h2>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Sports in Popular Suburbs</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="search.php?category=Sports&suburb=Melbourne" class="btn btn-outline-primary btn-sm">Sports in Melbourne</a>
                                <a href="search.php?category=Sports&suburb=Sydney" class="btn btn-outline-primary btn-sm">Sports in Sydney</a>
                                <a href="search.php?category=Sports&suburb=Brisbane" class="btn btn-outline-primary btn-sm">Sports in Brisbane</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Arts & Education</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="search.php?category=Arts+%26+Crafts" class="btn btn-outline-primary btn-sm">Arts & Crafts</a>
                                <a href="search.php?category=Music" class="btn btn-outline-primary btn-sm">Music</a>
                                <a href="search.php?category=Education" class="btn btn-outline-primary btn-sm">Education</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>