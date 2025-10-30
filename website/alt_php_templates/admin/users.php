<?php
require_once __DIR__ . '/includes/admin_auth.php';
require_once '../config/database.php';
require_once '../models/User.php';

// Check if admin is logged in
require_admin_login();

// Initialize database connection
$userDatabase = new Database('kidssmart_users');
$userDb = $userDatabase->getConnection();
$user = new User($userDb);

// Handle user actions
if ($_POST['action'] ?? false) {
    $user_id = $_POST['user_id'] ?? 0;
    
    switch ($_POST['action']) {
        case 'activate':
            if ($user_id) {
                $stmt = $userDb->prepare("UPDATE users SET is_active = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['flash_message'] = "User activated successfully!";
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'deactivate':
            if ($user_id) {
                $stmt = $userDb->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['flash_message'] = "User deactivated successfully!";
                $_SESSION['flash_type'] = 'warning';
            }
            break;
            
        case 'verify':
            if ($user_id) {
                $stmt = $userDb->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['flash_message'] = "User verified successfully!";
                $_SESSION['flash_type'] = 'success';
            }
            break;
            
        case 'delete':
            if ($user_id && $_POST['confirm_delete'] === 'yes') {
                // Delete user and related data
                $stmt = $userDb->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $_SESSION['flash_message'] = "User deleted successfully!";
                $_SESSION['flash_type'] = 'danger';
            }
            break;
    }
    
    header('Location: users.php');
    exit;
}

// Get filtering and sorting parameters
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($filter_status === 'active') {
    $where_conditions[] = "is_active = 1";
} elseif ($filter_status === 'inactive') {
    $where_conditions[] = "is_active = 0";
} elseif ($filter_status === 'unverified') {
    $where_conditions[] = "is_verified = 0";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users with pagination
$query = "SELECT * FROM users $where_clause ORDER BY $sort_by $sort_order LIMIT $per_page OFFSET $offset";
$stmt = $userDb->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM users $where_clause";
$count_stmt = $userDb->prepare($count_query);
$count_stmt->execute($params);
$total_users = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $per_page);

// Get user statistics
$stats_query = "SELECT 
    COUNT(*) as total_users,
    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users,
    SUM(CASE WHEN is_verified = 0 THEN 1 ELSE 0 END) as unverified_users,
    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_this_week
    FROM users";
$stats = $userDb->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Helper function to build query string
function buildQueryString($params) {
    $current = $_GET;
    foreach ($params as $key => $value) {
        $current[$key] = $value;
    }
    return http_build_query($current);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../static/css/style.css" rel="stylesheet">
    <style>
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #28a745);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .action-buttons .btn {
            margin: 1px;
        }
        .user-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include 'includes/admin_header.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <?php include 'includes/admin_sidebar.php'; ?>
            
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2 text-primary">
                        <i class="fas fa-users me-2"></i>User Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['flash_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
                <?php endif; ?>

                <!-- User Statistics -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-primary border-3">
                            <div class="card-body text-center">
                                <h6 class="text-primary fw-bold mb-1">Total Users</h6>
                                <h3 class="mb-0"><?= number_format($stats['total_users']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-success border-3">
                            <div class="card-body text-center">
                                <h6 class="text-success fw-bold mb-1">Active</h6>
                                <h3 class="mb-0"><?= number_format($stats['active_users']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-warning border-3">
                            <div class="card-body text-center">
                                <h6 class="text-warning fw-bold mb-1">Unverified</h6>
                                <h3 class="mb-0"><?= number_format($stats['unverified_users']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-secondary border-3">
                            <div class="card-body text-center">
                                <h6 class="text-secondary fw-bold mb-1">New Today</h6>
                                <h3 class="mb-0"><?= number_format($stats['new_today']) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-start border-dark border-3">
                            <div class="card-body text-center">
                                <h6 class="text-dark fw-bold mb-1">This Week</h6>
                                <h3 class="mb-0"><?= number_format($stats['new_this_week']) ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Users</label>
                                <input type="text" class="form-control" name="search" 
                                       value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Search by name, username, or email">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All Status</option>
                                    <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="unverified" <?= $filter_status === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Sort By</label>
                                <select class="form-select" name="sort">
                                    <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Join Date</option>
                                    <option value="username" <?= $sort_by === 'username' ? 'selected' : '' ?>>Username</option>
                                    <option value="email" <?= $sort_by === 'email' ? 'selected' : '' ?>>Email</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Order</label>
                                <select class="form-select" name="order">
                                    <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                                    <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Apply Filters
                                </button>
                                <a href="users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Users (<?= number_format($total_users) ?> total)
                        </h5>
                        <small class="text-muted">
                            Showing <?= $offset + 1 ?>-<?= min($offset + $per_page, $total_users) ?> of <?= number_format($total_users) ?>
                        </small>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($users)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>User</th>
                                            <th>
                                                <a href="?<?= buildQueryString(['sort' => 'email', 'order' => $sort_by === 'email' && $sort_order === 'ASC' ? 'DESC' : 'ASC']) ?>" 
                                                   class="text-decoration-none">
                                                    Email 
                                                    <?php if ($sort_by === 'email'): ?>
                                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Status</th>
                                            <th>
                                                <a href="?<?= buildQueryString(['sort' => 'created_at', 'order' => $sort_by === 'created_at' && $sort_order === 'ASC' ? 'DESC' : 'ASC']) ?>" 
                                                   class="text-decoration-none">
                                                    Joined 
                                                    <?php if ($sort_by === 'created_at'): ?>
                                                        <i class="fas fa-sort-<?= $sort_order === 'ASC' ? 'up' : 'down' ?>"></i>
                                                    <?php endif; ?>
                                                </a>
                                            </th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $user_data): ?>
                                            <tr class="user-row">
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-3">
                                                            <?= strtoupper(substr($user_data['username'], 0, 2)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($user_data['username']) ?></div>
                                                            <small class="text-muted">
                                                                <?= htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')) ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <a href="mailto:<?= htmlspecialchars($user_data['email']) ?>" class="text-decoration-none">
                                                        <?= htmlspecialchars($user_data['email']) ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="badge status-badge bg-<?= $user_data['is_active'] ? 'success' : 'danger' ?>">
                                                            <?= $user_data['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                        <?php if (!$user_data['is_verified']): ?>
                                                            <span class="badge status-badge bg-warning">Unverified</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?= date('M j, Y', strtotime($user_data['created_at'])) ?>
                                                        <br>
                                                        <small class="text-muted"><?= date('g:i A', strtotime($user_data['created_at'])) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <!-- Status Actions -->
                                                        <?php if ($user_data['is_active']): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="deactivate">
                                                                <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                <button type="submit" class="btn btn-outline-warning btn-sm" 
                                                                        onclick="return confirm('Deactivate this user?')" title="Deactivate">
                                                                    <i class="fas fa-pause"></i>
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="activate">
                                                                <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Activate">
                                                                    <i class="fas fa-play"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <!-- Verification -->
                                                        <?php if (!$user_data['is_verified']): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="verify">
                                                                <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                <button type="submit" class="btn btn-outline-info btn-sm" title="Verify User">
                                                                    <i class="fas fa-check-circle"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>

                                                        <!-- Delete -->
                                                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user_data['user_id'] ?>" title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No users found</h5>
                                <p class="text-muted">Try adjusting your search criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer">
                            <nav aria-label="User pagination">
                                <ul class="pagination justify-content-center mb-0">
                                    <!-- Previous Page -->
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>

                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => 1]) ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif;
                                    endif;

                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;

                                    if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled"><span class="page-link">...</span></li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $total_pages]) ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Next Page -->
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">
                                            Next <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modals -->
    <?php foreach ($users as $user_data): ?>
        <div class="modal fade" id="deleteModal<?= $user_data['user_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                            Confirm User Deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to <strong>permanently delete</strong> this user?</p>
                        <div class="alert alert-warning">
                            <strong>User:</strong> <?= htmlspecialchars($user_data['username']) ?><br>
                            <strong>Email:</strong> <?= htmlspecialchars($user_data['email']) ?><br>
                            <strong>Joined:</strong> <?= date('M j, Y', strtotime($user_data['created_at'])) ?>
                        </div>
                        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone. All user data, favorites, and reviews will be permanently removed.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                            <input type="hidden" name="confirm_delete" value="yes">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete User
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>