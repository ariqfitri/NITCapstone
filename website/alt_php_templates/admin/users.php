<?php
// Set page title  
$page_title = 'User Management';

// Session and auth check
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!($_SESSION['admin_logged_in'] ?? false)) {
    header('Location: login.php');
    exit;
}

require_once '../config/database.php';
require_once '../models/User.php';

// Initialize database
$userDatabase = new Database('kidssmart_users');
$userDb = $userDatabase->getConnection();
$user = new User($userDb);

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        if ($action === 'toggle_active' && $user_id) {
            $stmt = $userDb->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "User status updated successfully!";
        } elseif ($action === 'delete' && $user_id) {
            $stmt = $userDb->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "User deleted successfully!";
        } elseif ($action === 'make_admin' && $user_id) {
            $stmt = $userDb->prepare("UPDATE users SET is_admin = 1 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "User promoted to admin!";
        } elseif ($action === 'remove_admin' && $user_id) {
            $stmt = $userDb->prepare("UPDATE users SET is_admin = 0 WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $_SESSION['success_message'] = "Admin privileges removed!";
        }
        
        header('Location: users.php');
        exit;
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Get filter parameters
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 25;
$offset = ($page - 1) * $limit;
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query
$where_clauses = [];
$params = [];

if ($status_filter === 'active') {
    $where_clauses[] = "is_active = 1";
} elseif ($status_filter === 'inactive') {
    $where_clauses[] = "is_active = 0";
} elseif ($status_filter === 'admin') {
    $where_clauses[] = "is_admin = 1";
}

if ($search) {
    $where_clauses[] = "(username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

try {
    // Get users
    $query = "SELECT * FROM users $where_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
    $stmt = $userDb->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM users $where_sql";
    $stmt = $userDb->prepare($count_query);
    $stmt->execute($params);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $limit);
    
    // Get stats
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN is_admin = 1 THEN 1 ELSE 0 END) as admins,
        SUM(CASE WHEN DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_last_30_days
        FROM users";
    $stats = $userDb->query($stats_query)->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $users = [];
    $total_count = 0;
    $total_pages = 0;
    $error = "Error loading users: " . $e->getMessage();
}

$success_message = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']);
?>

<?php include 'includes/admin_header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include 'includes/admin_sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-users me-2"></i>User Management
                </h1>
                <div class="btn-toolbar">
                    <button type="button" class="btn btn-primary btn-sm" onclick="location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-white bg-primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= $stats['total'] ?></h3>
                                    <small>Total Users</small>
                                </div>
                                <i class="fas fa-users fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= $stats['active'] ?></h3>
                                    <small>Active Users</small>
                                </div>
                                <i class="fas fa-user-check fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= $stats['admins'] ?></h3>
                                    <small>Administrators</small>
                                </div>
                                <i class="fas fa-user-shield fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?= $stats['new_last_30_days'] ?></h3>
                                    <small>New (30 days)</small>
                                </div>
                                <i class="fas fa-user-plus fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="all" <?= $status_filter === 'all' ? 'selected' : '' ?>>All Users</option>
                                <option value="active" <?= $status_filter === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $status_filter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="admin" <?= $status_filter === 'admin' ? 'selected' : '' ?>>Admins</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Username, email, name..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        Users 
                        <span class="badge bg-secondary"><?= $total_count ?> results</span>
                        <?php if ($search || $status_filter !== 'all'): ?>
                            <a href="users.php" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="fas fa-times me-1"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Username</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Role</th>
                                        <th>Joined</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <?php if ($u['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($u['is_admin']): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-shield-alt me-1"></i>Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">User</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small><?= date('M j, Y', strtotime($u['created_at'])) ?></small></td>
                                            <td class="text-end">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <button type="submit" name="action" value="toggle_active" 
                                                            class="btn btn-<?= $u['is_active'] ? 'secondary' : 'success' ?> btn-sm" 
                                                            title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <?php if (!$u['is_admin']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                        <button type="submit" name="action" value="make_admin" 
                                                                class="btn btn-warning btn-sm" title="Make Admin"
                                                                onclick="return confirm('Make this user an admin?')">
                                                            <i class="fas fa-user-shield"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                        <button type="submit" name="action" value="remove_admin" 
                                                                class="btn btn-outline-warning btn-sm" title="Remove Admin"
                                                                onclick="return confirm('Remove admin privileges?')">
                                                            <i class="fas fa-user-minus"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                    <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Delete this user? This action cannot be undone!')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="card-footer">
                                <nav>
                                    <ul class="pagination pagination-sm mb-0 justify-content-center">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Previous</a>
                                            </li>
                                        <?php endif; ?>

                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                                <a class="page-link" href="?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                            </li>
                                        <?php endfor; ?>

                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">Next</a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                                <div class="text-center mt-2">
                                    <small class="text-muted">Page <?= $page ?> of <?= $total_pages ?> (<?= $total_count ?> total users)</small>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                            <h5>No Users Found</h5>
                            <p class="text-muted">
                                <?php if ($search || $status_filter !== 'all'): ?>
                                    Try adjusting your filters or <a href="users.php">view all users</a>
                                <?php else: ?>
                                    No users registered yet
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>