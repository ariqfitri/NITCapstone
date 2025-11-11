<?php
// Set no_auth_check to prevent redirect loop
$no_auth_check = true;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
try {
    require_once '../config/database.php';
} catch (Exception $e) {
    die("Configuration Error: " . $e->getMessage());
}

// Rate limiting to prevent brute force
$max_attempts = 5;
$lockout_time = 300; // 5 minutes

// Check lockout status
$lockout_key = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
$attempts = $_SESSION[$lockout_key] ?? ['count' => 0, 'last_attempt' => 0];

$is_locked_out = ($attempts['count'] >= $max_attempts) && 
                 (time() - $attempts['last_attempt'] < $lockout_time);

if ($_POST['login'] ?? false) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($is_locked_out) {
        $remaining_time = $lockout_time - (time() - $attempts['last_attempt']);
        $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } elseif (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
        $attempts['count']++;
        $attempts['last_attempt'] = time();
        $_SESSION[$lockout_key] = $attempts;
    } else {
        // Check user in database with role hierarchy
        try {
            $userDatabase = new Database('kidssmart_users');
            $userDb = $userDatabase->getConnection();
            
            // Query for admin user with role information
            $query = "SELECT user_id, username, email, password_hash, first_name, last_name, 
                        is_admin, admin_level, can_manage_admins, can_access_system_settings, is_active 
                     FROM users 
                     WHERE (username = ? OR email = ?) AND is_admin = 1 AND is_active = 1 
                     LIMIT 1";
            $stmt = $userDb->prepare($query);
            $stmt->execute([$username, $username]);
            $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin_user && password_verify($password, $admin_user['password_hash'])) {
                // Clear failed attempts on successful login
                unset($_SESSION[$lockout_key]);
                
                // Set admin session variables with role information
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user_id'] = $admin_user['user_id'];
                $_SESSION['admin_username'] = $admin_user['username'];
                $_SESSION['admin_email'] = $admin_user['email'];
                $_SESSION['admin_name'] = $admin_user['first_name'] . ' ' . $admin_user['last_name'];
                $_SESSION['admin_level'] = $admin_user['admin_level'];
                $_SESSION['can_manage_admins'] = $admin_user['can_manage_admins'];
                $_SESSION['can_access_system_settings'] = $admin_user['can_access_system_settings'];
                $_SESSION['login_time'] = time();
                
                // Set role name for display
                $_SESSION['admin_role_name'] = match($admin_user['admin_level']) {
                    1 => 'Administrator',
                    2 => 'Super Administrator',
                    default => 'Admin'
                };
                
                // Update last login time
                $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
                $update_stmt = $userDb->prepare($update_query);
                $update_stmt->execute([$admin_user['user_id']]);
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid credentials or insufficient privileges";
                $attempts['count']++;
                $attempts['last_attempt'] = time();
                $_SESSION[$lockout_key] = $attempts;
            }
        } catch (Exception $e) {
            $error = "Database connection error. Please try again later.";
            error_log("Admin login database error: " . $e->getMessage());
        }
    }
}

// Redirect if already logged in
if ($_SESSION['admin_logged_in'] ?? false) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.9);
            border: none;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
        }
        .role-info {
            background: rgba(13, 110, 253, 0.1);
            border-left: 4px solid #0d6efd;
            padding: 10px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-child fa-3x text-primary mb-3"></i>
                            <h2 class="text-primary">KidsSmart Admin</h2>
                            <p class="text-muted">Please sign in to continue</p>
                        </div>
                        
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_locked_out): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-lock me-2"></i>
                                Account temporarily locked due to too many failed attempts.
                            </div>
                        <?php endif; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username or Email
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       required autocomplete="username" <?= $is_locked_out ? 'disabled' : '' ?>
                                       placeholder="Enter your username or email">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       required autocomplete="current-password" <?= $is_locked_out ? 'disabled' : '' ?>
                                       placeholder="Enter your password">
                            </div>
                            
                            <?php if (!$is_locked_out): ?>
                                <button type="submit" name="login" value="1" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            <?php endif; ?>
                        </form>

                        <!-- Failed attempt counter -->
                        <?php if ($attempts['count'] > 0 && !$is_locked_out): ?>
                            <div class="mt-3 text-center">
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Failed attempts: <?= $attempts['count'] ?>/<?= $max_attempts ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <!-- Role Information -->
                        <div class="role-info">
                            <h6 class="mb-2">
                                <i class="fas fa-info-circle me-1"></i>Admin Role Levels
                            </h6>
                            <small>
                                <strong>Administrator:</strong> Manage activities, users, reports<br>
                                <strong>Super Admin:</strong> All admin functions + system settings + admin management
                            </small>
                        </div>
                        
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="fas fa-shield-alt me-1"></i>
                                Admin access requires appropriate role privileges
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <small class="text-white">
                        <i class="fas fa-user-shield me-1"></i>
                        Role-based secure admin access
                    </small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>