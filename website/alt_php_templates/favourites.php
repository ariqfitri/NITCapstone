<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Favourite.php';

redirect_if_not_logged_in();

$userDatabase = new Database('kidssmart_users');
$userDb = $userDatabase->getConnection();
$favourite = new Favourite($userDb);

$user_id = get_current_user_id();
$favourites = $favourite->getUserFavourites($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favourites - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1 class="mb-4">My Favourite Activities</h1>

        <?php if (count($favourites) > 0): ?>
            <div class="row">
                <?php foreach ($favourites as $fav): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <?php if (!empty($fav['image_url'])): ?>
                                <img src="<?= htmlspecialchars($fav['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($fav['title']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-child fa-3x text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($fav['title']) ?></h5>
                                <p class="card-text text-muted">
                                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($fav['suburb']) ?>
                                </p>
                                <?php if (!empty($fav['category'])): ?>
                                    <span class="badge bg-primary"><?= htmlspecialchars($fav['category']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="program_detail.php?id=<?= $fav['activity_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                <h3>No favourites yet</h3>
                <p class="text-muted">Start saving activities you love to see them here!</p>
                <a href="search.php" class="btn btn-primary">Explore Activities</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>