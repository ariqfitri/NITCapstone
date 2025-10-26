<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/models/Program.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Favourite.php';

$database = new Database('kidssmart_app');
$db = $database->getConnection();
$program = new Program($db);

// Get search parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$suburb = $_GET['suburb'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12;

// Get search results
$programs = $program->searchPrograms($search, $category, $suburb, $page, $limit);
$categories = $program->getCategories();
$suburbs = $program->getSuburbs();

// Use the new count method instead of searching again
$total_results = $program->getSearchCount($search, $category, $suburb);
$total_pages = ceil($total_results / $limit);

// Check favourites for logged-in users
$favourite = new Favourite($db);
if (is_logged_in()) {
    $user_favourites = [];
    foreach ($programs as $activity) {
        $favourite->user_id = get_current_user_id();
        $favourite->activity_id = $activity['activity_id'];
        $user_favourites[$activity['activity_id']] = $favourite->isFavourited();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Activities - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="static/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container mt-4">
        <h1>Find Kids Activities</h1>
        
        <?php if (is_logged_in()): ?>
            <div class="alert alert-info">
                <i class="fas fa-star"></i> You're logged in! Save your favourite activities for quick access.
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i> <a href="signup.php" class="alert-link">Sign up</a> or <a href="login.php" class="alert-link">login</a> to save your favourite activities and get personalised recommendations!
            </div>
        <?php endif; ?>
        
        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search activities..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>" <?= $category == $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="suburb" class="form-select">
                            <option value="">All Suburbs</option>
                            <?php foreach ($suburbs as $sub): ?>
                                <option value="<?= htmlspecialchars($sub) ?>" <?= $suburb == $sub ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="row">
            <div class="col-12">
                <p class="text-muted">Found <?= $total_results ?> results</p>
            </div>
            
            <?php if (count($programs) > 0): ?>
                <?php foreach ($programs as $activity): ?>
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
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title"><?= htmlspecialchars($activity['title']) ?></h5>
                                    <?php if (is_logged_in()): ?>
                                        <form method="post" action="program_detail.php?id=<?= $activity['activity_id'] ?>" class="d-inline">
                                            <input type="hidden" name="activity_id" value="<?= $activity['activity_id'] ?>">
                                            <?php if ($user_favourites[$activity['activity_id']] ?? false): ?>
                                                <input type="hidden" name="favourite_action" value="remove">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Remove from favourites">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            <?php else: ?>
                                                <input type="hidden" name="favourite_action" value="add">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Add to favourites">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    <?php endif; ?>
                                </div>
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
                                <?php if (!empty($activity['description'])): ?>
                                    <p class="card-text mt-2"><?= substr(htmlspecialchars($activity['description']), 0, 100) ?>...</p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <a href="program_detail.php?id=<?= $activity['activity_id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h3>No activities found</h3>
                    <p class="text-muted">Try adjusting your search criteria</p>
                    <a href="search.php" class="btn btn-primary">Clear Search</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Search results pagination">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                            <a class="page-link" href="?search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&suburb=<?= urlencode($suburb) ?>&page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>