<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';

$database = new Database();
$db = $database->getConnection();
$program = new Program($db);

// Get some stats for the about page
$total_activities = $program->getTotalProgramsCount();
$categories = $program->getCategories();
$suburbs = $program->getSuburbs();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <h1 class="text-center mb-4">About KidsSmart</h1>
                
                <!-- Stats Section -->
                <div class="row mb-5">
                    <div class="col-md-4 text-center mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h2 class="display-4"><?= $total_activities ?></h2>
                                <p class="mb-0">Activities</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h2 class="display-4"><?= count($categories) ?></h2>
                                <p class="mb-0">Categories</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-center mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h2 class="display-4"><?= count($suburbs) ?></h2>
                                <p class="mb-0">Suburbs</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">Our Mission</h3>
                        <p class="card-text">
                            KidsSmart is dedicated to helping parents and guardians find the perfect activities 
                            for their children. We believe every child deserves access to quality sports, arts, 
                            education, and recreational programs that help them grow, learn, and have fun.
                        </p>
                        <p class="card-text">
                            Our platform brings together activities from various sources, making it easy for 
                            parents to discover new opportunities for their children in their local area.
                        </p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">What We Offer</h3>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-search text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Comprehensive Search</h5>
                                        <p class="text-muted">Find activities by category, location, age range, and more with our powerful search tools.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-map-marker-alt text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Local Focus</h5>
                                        <p class="text-muted">Discover programs in your neighborhood and surrounding areas that are convenient for your family.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-heart text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Save Favourites</h5>
                                        <p class="text-muted">Create an account to save your favourite activities and access them anytime.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-user text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Personalised Experience</h5>
                                        <p class="text-muted">Get personalised recommendations based on your location and preferences.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Detailed Information</h5>
                                        <p class="text-muted">Get all the details you need - schedules, costs, contact info, and more.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-sync-alt text-primary fa-2x me-3"></i>
                                    </div>
                                    <div>
                                        <h5>Always Updated</h5>
                                        <p class="text-muted">Our database is continuously updated with new programs and information.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <h3 class="card-title">For Activity Providers</h3>
                        <p>
                            Are you an activity provider? We're always looking to expand our database with 
                            quality programs for children. While we currently aggregate information from 
                            various sources, we're working on direct submission features for providers.
                        </p>
                        <p class="mb-0">
                            <strong>Contact us to learn more about featuring your programs.</strong>
                        </p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="card-title">Get Started Today</h3>
                        <p class="card-text">
                            Ready to find amazing activities for your children? Start exploring now!
                        </p>
                        <div class="d-flex justify-content-center flex-wrap gap-3">
                            <a href="search.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-search"></i> Find Activities
                            </a>
                            <a href="categories.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-list"></i> Browse Categories
                            </a>
                            <?php if (!is_logged_in()): ?>
                                <a href="signup.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-user-plus"></i> Join Free
                                </a>
                            <?php else: ?>
                                <a href="dashboard.php" class="btn btn-success btn-lg">
                                    <i class="fas fa-tachometer-alt"></i> Your Dashboard
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>