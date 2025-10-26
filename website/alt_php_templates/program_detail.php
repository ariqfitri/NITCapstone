<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Favourite.php';

$database = new Database('kidssmart_app');
$db = $database->getConnection();
$program = new Program($db);
$favourite = new Favourite($db);

// Handle favourite actions
if (is_logged_in() && ($_POST['favourite_action'] ?? false)) {
    $favourite->user_id = get_current_user_id();
    $favourite->activity_id = $_POST['activity_id'] ?? 0;
    
    if ($_POST['favourite_action'] === 'add') {
        $favourite->create();
    } elseif ($_POST['favourite_action'] === 'remove') {
        $favourite->delete();
    }
    
    // Redirect to avoid form resubmission
    header("Location: program_detail.php?id=" . $_POST['activity_id']);
    exit;
}

// Get program ID from URL
$program_id = $_GET['id'] ?? 0;

if (!$program_id) {
    header("Location: search.php");
    exit;
}

// Get program details
$activity = $program->getProgramById($program_id);

if (!$activity) {
    header("Location: search.php");
    exit;
}

// Check if current user has favourited this activity
$is_favourited = false;
if (is_logged_in()) {
    $favourite->user_id = get_current_user_id();
    $favourite->activity_id = $program_id;
    $is_favourited = $favourite->isFavourited();
}

// Get related programs (same category)
$related_programs = $program->searchPrograms('', $activity['category'], '', 1, 4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($activity['title']) ?> - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="search.php">Activities</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($activity['title']) ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <div class="card">
                    <?php if (!empty($activity['image_url'])): ?>
                        <img src="<?= htmlspecialchars($activity['image_url']) ?>" class="card-img-top" alt="<?= htmlspecialchars($activity['title']) ?>" style="max-height: 400px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h1 class="card-title"><?= htmlspecialchars($activity['title']) ?></h1>
                            <?php if (is_logged_in()): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                    <?php if ($is_favourited): ?>
                                        <input type="hidden" name="favourite_action" value="remove">
                                        <button type="submit" class="btn btn-danger" title="Remove from favourites">
                                            <i class="fas fa-heart"></i> Remove Favourite
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="favourite_action" value="add">
                                        <button type="submit" class="btn btn-outline-danger" title="Add to favourites">
                                            <i class="far fa-heart"></i> Add to Favourites
                                        </button>
                                    <?php endif; ?>
                                </form>
                            <?php else: ?>
                                <a href="login.php?redirect=program_detail.php?id=<?= $activity['activity_id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt"></i> Login to Save
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Badges -->
                        <div class="mb-3">
                            <?php if (!empty($activity['category'])): ?>
                                <span class="badge bg-primary me-2"><?= htmlspecialchars($activity['category']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($activity['age_range'])): ?>
                                <span class="badge bg-info me-2"><?= htmlspecialchars($activity['age_range']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($activity['cost'])): ?>
                                <span class="badge bg-success"><?= htmlspecialchars($activity['cost']) ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Location -->
                        <div class="mb-4">
                            <h5><i class="fas fa-map-marker-alt text-primary"></i> Location</h5>
                            <p class="mb-1">
                                <?php if (!empty($activity['suburb'])): ?>
                                    <strong>Suburb:</strong> <?= htmlspecialchars($activity['suburb']) ?>
                                    <?php if (!empty($activity['postcode'])): ?>
                                        , <?= htmlspecialchars($activity['postcode']) ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($activity['address'])): ?>
                                <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($activity['address']) ?></p>
                            <?php endif; ?>
                        </div>

                        <!-- Description -->
                        <?php if (!empty($activity['description'])): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-info-circle text-primary"></i> Description</h5>
                                <p><?= nl2br(htmlspecialchars($activity['description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Schedule -->
                        <?php if (!empty($activity['schedule'])): ?>
                            <div class="mb-4">
                                <h5><i class="fas fa-calendar-alt text-primary"></i> Schedule</h5>
                                <p><?= nl2br(htmlspecialchars($activity['schedule'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Contact Information -->
                        <div class="mb-4">
                            <h5><i class="fas fa-address-book text-primary"></i> Contact Information</h5>
                            <div class="row">
                                <?php if (!empty($activity['phone'])): ?>
                                    <div class="col-md-6">
                                        <p><strong>Phone:</strong> <?= htmlspecialchars($activity['phone']) ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($activity['email'])): ?>
                                    <div class="col-md-6">
                                        <p><strong>Email:</strong> 
                                            <a href="mailto:<?= htmlspecialchars($activity['email']) ?>">
                                                <?= htmlspecialchars($activity['email']) ?>
                                            </a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($activity['website'])): ?>
                                    <div class="col-12">
                                        <p><strong>Website:</strong> 
                                            <a href="<?= htmlspecialchars($activity['website']) ?>" target="_blank" rel="noopener">
                                                <?= htmlspecialchars($activity['website']) ?>
                                            </a>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Source Information -->
                        <?php if (!empty($activity['source_name'])): ?>
                            <div class="alert alert-info">
                                <small>
                                    <i class="fas fa-database"></i> 
                                    Source: <?= htmlspecialchars(ucfirst($activity['source_name'])) ?>
                                    <?php if (!empty($activity['scraped_at'])): ?>
                                        | Last updated: <?= date('M j, Y', strtotime($activity['scraped_at'])) ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <!-- Favourite Button -->
                        <?php if (is_logged_in()): ?>
                            <form method="post" class="d-grid mb-3">
                                <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                <?php if ($is_favourited): ?>
                                    <input type="hidden" name="favourite_action" value="remove">
                                    <button type="submit" class="btn btn-danger w-100 mb-2">
                                        <i class="fas fa-heart"></i> Remove from Favourites
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="favourite_action" value="add">
                                    <button type="submit" class="btn btn-outline-danger w-100 mb-2">
                                        <i class="far fa-heart"></i> Add to Favourites
                                    </button>
                                <?php endif; ?>
                            </form>
                        <?php else: ?>
                            <a href="login.php?redirect=program_detail.php?id=<?= $activity['activity_id'] ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-heart"></i> Login to Save
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($activity['website'])): ?>
                            <a href="<?= htmlspecialchars($activity['website']) ?>" target="_blank" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-external-link-alt"></i> Visit Website
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($activity['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($activity['phone']) ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-phone"></i> Call Now
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($activity['email'])): ?>
                            <a href="mailto:<?= htmlspecialchars($activity['email']) ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-envelope"></i> Send Email
                            </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-secondary w-100" onclick="window.print()">
                            <i class="fas fa-print"></i> Print Details
                        </button>
                    </div>
                </div>

                <!-- Location Map Placeholder -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Location</h5>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Map integration available</p>
                        <?php if (!empty($activity['address']) || !empty($activity['suburb'])): ?>
                            <small class="text-muted">
                                <?= htmlspecialchars($activity['address'] ?? $activity['suburb']) ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Related Programs -->
                <?php if (count($related_programs) > 1): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Related Activities</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($related_programs as $related): ?>
                                <?php if ($related['activity_id'] != $activity['activity_id']): ?>
                                    <div class="mb-3 pb-3 border-bottom">
                                        <h6 class="card-title">
                                            <a href="program_detail.php?id=<?= $related['activity_id'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($related['title']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?= htmlspecialchars($related['suburb']) ?>
                                            <?php if (!empty($related['postcode'])): ?>
                                                , <?= htmlspecialchars($related['postcode']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>