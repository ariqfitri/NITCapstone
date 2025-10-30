<?php
// No session requirements - using same approach as working dashboard
require_once '../config/database.php';
require_once '../models/User.php';

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
                $flash_message = "User activated successfully!";
                $flash_type = 'success';
            }
            break;
            
        case 'deactivate':
            if ($user_id) {
                $stmt = $userDb->prepare("UPDATE users SET is_active = 0 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $flash_message = "User deactivated successfully!";
                $flash_type = 'warning';
            }
            break;
            
        case 'verify':
            if ($user_id) {
                $stmt = $userDb->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $flash_message = "User verified successfully!";
                $flash_type = 'success';
            }
            break;
            
        case 'delete':
            if ($user_id && $_POST['confirm_delete'] === 'yes') {
                $stmt = $userDb->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $flash_message = "User deleted successfully!";
                $flash_type = 'danger';
            }
            break;
    }
}

// Get filtering and sorting parameters
$search = $_GET['search'] ?? '';
$filter_status = $_GET['status'] ?? 'all';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 15;
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

// Get total count
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

// Helper function for query strings
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
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }
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
        .user-row:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-child me-2"></i>KidsSmart Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php" target="_blank">
                    <i class="fas fa-external-link-alt me-1"></i> View Site
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-3">
                        <ul class="nav flex-column">
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="admin_scrapers.php">
                                    <i class="fas fa-spider me-2"></i>Scrapers
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link" href="activities.php">
                                    <i class="fas fa-running me-2"></i>Activities
                                </a>
                            </li>
                            <li class="nav-item mb-2">
                                <a class="nav-link active" href="users.php">
                                    <i class="fas fa-users me-2"></i>Users
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h2 text-primary">
                            <i class="fas fa-users me-2"></i>User Management
                        </h1>
                        <button type="button" class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>

                    <!-- Flash Message -->
                    <?php if (isset($flash_message)): ?>
                        <div class="alert alert-<?= $flash_type ?? 'info' ?> alert-dismissible fade show" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <?= htmlspecialchars($flash_message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- User Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-2 mb-3">
                            <div class="card border-0 bg-primary text-white text-center">
                                <div class="card-body">
                                    <h4 class="mb-0"><?= number_format($stats['total_users']) ?></h4>
                                    <small>Total Users</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card border-0 bg-success text-white text-center">
                                <div class="card-body">
                                    <h4 class="mb-0"><?= number_format($stats['active_users']) ?></h4>
                                    <small>Active</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card border-0 bg-warning text-white text-center">
                                <div class="card-body">
                                    <h4 class="mb-0"><?= number_format($stats['unverified_users']) ?></h4>
                                    <small>Unverified</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card border-0 bg-info text-white text-center">
                                <div class="card-body">
                                    <h4 class="mb-0"><?= number_format($stats['new_today']) ?></h4>
                                    <small>New Today</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 mb-3">
                            <div class="card border-0 bg-secondary text-white text-center">
                                <div class="card-body">
                                    <h4 class="mb-0"><?= number_format($stats['new_this_week']) ?></h4>
                                    <small>This Week</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Search Users</label>
                                    <input type="text" class="form-control" name="search" 
                                           value="<?= htmlspecialchars($search) ?>" 
                                           placeholder="Search by name, username, or email">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>All</option>
                                        <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="unverified" <?= $filter_status === 'unverified' ? 'selected' : '' ?>>Unverified</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Sort</label>
                                    <select class="form-select" name="sort">
                                        <option value="created_at" <?= $sort_by === 'created_at' ? 'selected' : '' ?>>Join Date</option>
                                        <option value="username" <?= $sort_by === 'username' ? 'selected' : '' ?>>Username</option>
                                        <option value="email" <?= $sort_by === 'email' ? 'selected' : '' ?>>Email</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Order</label>
                                    <select class="form-select" name="order">
                                        <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Newest</option>
                                        <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Oldest</option>
                                    </select>
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Users (<?= number_format($total_users) ?> total)</h5>
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
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Joined</th>
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
                                                        <div class="btn-group btn-group-sm">
                                                            <!-- Status Toggle -->
                                                            <?php if ($user_data['is_active']): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="deactivate">
                                                                    <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-warning" 
                                                                            onclick="return confirm('Deactivate this user?')" title="Deactivate">
                                                                        <i class="fas fa-pause"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="activate">
                                                                    <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-success" title="Activate">
                                                                        <i class="fas fa-play"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>

                                                            <!-- Verification -->
                                                            <?php if (!$user_data['is_verified']): ?>
                                                                <form method="post" class="d-inline">
                                                                    <input type="hidden" name="action" value="verify">
                                                                    <input type="hidden" name="user_id" value="<?= $user_data['user_id'] ?>">
                                                                    <button type="submit" class="btn btn-outline-info" title="Verify">
                                                                        <i class="fas fa-check-circle"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>

                                                            <!-- Delete -->
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user_data['user_id'] ?>" title="Delete">
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
                                <nav>
                                    <ul class="pagination justify-content-center mb-0">
                                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $page - 1]) ?>">Previous</a>
                                        </li>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?<?= buildQueryString(['page' => $i]) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                            <a class="page-link" href="?<?= buildQueryString(['page' => $page + 1]) ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modals -->
    <?php foreach ($users as $user_data): ?>
        <div class="modal fade" id="deleteModal<?= $user_data['user_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                            Confirm Deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this user?</p>
                        <div class="alert alert-warning">
                            <strong>User:</strong> <?= htmlspecialchars($user_data['username']) ?><br>
                            <strong>Email:</strong> <?= htmlspecialchars($user_data['email']) ?>
                        </div>
                        <p class="text-danger"><strong>Warning:</strong> This action cannot be undone.</p>
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