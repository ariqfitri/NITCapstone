<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Favourite.php';

redirect_if_not_logged_in();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$favourite = new Favourite($db);
$program = new Program($db);

$user->user_id = get_current_user_id();
$user->readOne();

// Get user's favourites
$favourites = $favourite->getUserFavourites($user->user_id);

// Get recommended activities based on user preferences
$recommended_activities = $program->getRecommendedActivities($user->suburb, $user->child_age_range, 6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="card-title">Welcome back, <?= htmlspecialchars($user->first_name) ?>!</h1>
                                <p class="card-text">Here's your personalised activity dashboard</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="bg-white rounded p-3 text-dark">
                                    <h4 class="mb-0"><?= count($favourites) ?></h4>
                                    <small class="text-muted">Saved Activities</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Stats -->
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-heart fa-2x text-danger mb-3"></i>
                        <h3><?= count($favourites) ?></h3>
                        <p class="text-muted">Favourites</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-map-marker-alt fa-2x text-primary mb-3"></i>
                        <h3><?= $user->suburb ? htmlspecialchars($user->suburb) : 'Not set' ?></h3>
                        <p class="text-muted">Location</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-child fa-2x text-success mb-3"></i>
                        <h3><?= $user->child_age_range ?: 'Not set' ?></h3>
                        <p class="text-muted">Age Range</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="fas fa-calendar-alt fa-2x text-warning mb-3"></i>
                        <h3><?= count($recommended_activities) ?></h3>
                        <p class="text-muted">Recommended</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recommended Activities -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Recommended for You</h4>
                        <p class="text-muted mb-0">
                            Based on your location and preferences
                            <?php if ($user->suburb): ?>
                                in <?= htmlspecialchars($user->suburb) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="card-body">
                        <?php if (count($recommended_activities) > 0): ?>
                            <div class="row">
                                <?php foreach ($recommended_activities as $activity): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <?php if (!empty($activity['image_url'])): ?>
                                                <img src="<?= htmlspecialchars($activity['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($activity['title']) ?>" style="height: 150px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 150px;">
                                                    <i class="fas fa-child fa-2x text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="card-body">
                                                <h6 class="card-title"><?= htmlspecialchars($activity['title']) ?></h6>
                                                <p class="card-text text-muted small">
                                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($activity['suburb']) ?>
                                                    <?php if ($activity['postcode']): ?>, <?= htmlspecialchars($activity['postcode']) ?><?php endif; ?>
                                                </p>
                                                <?php if ($activity['category']): ?>
                                                    <span class="badge bg-primary"><?= htmlspecialchars($activity['category']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-footer">
                                                <a href="program_detail.php?id=<?= $activity['activity_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                <h5>No recommendations yet</h5>
                                <p class="text-muted">Update your profile to get personalised recommendations</p>
                                <a href="profile.php" class="btn btn-primary">Update Profile</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Favourites Sidebar -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Your Favourites</h4>
                    </div>
                    <div class="card-body">
                        <?php if (count($favourites) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($favourites as $fav): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($fav['title']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($fav['category']) ?> • <?= htmlspecialchars($fav['suburb']) ?></small>
                                        </div>
                                        <a href="program_detail.php?id=<?= $fav['activity_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-3 text-center">
                                <a href="favourites.php" class="btn btn-primary">View All Favourites</a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                                <h5>No favourites yet</h5>
                                <p class="text-muted">Start saving activities you love!</p>
                                <a href="search.php" class="btn btn-primary">Explore Activities</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="search.php" class="btn btn-outline-primary">
                                <i class="fas fa-search"></i> Find Activities
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user"></i> Edit Profile
                            </a>
                            <a href="favourites.php" class="btn btn-outline-primary">
                                <i class="fas fa-heart"></i> My Favourites
                            </a>
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