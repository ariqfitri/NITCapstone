<?php
// KidsSmart Login - Verification Check Disabled
$page_title = 'Login - KidsSmart';

// Start session ONLY if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth.php (which won't start session again)
require_once 'config/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Initialize variables
$error = '';
$success = '';
$username = '';
$remember_me = false;

// Simple rate limiting
$ip_address = $_SERVER['REMOTE_ADDR'];
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = [];
}

// Clean old attempts (older than 15 minutes)
$current_time = time();
foreach ($_SESSION['login_attempts'] as $ip => $data) {
    if ($current_time - $data['last_attempt'] > 900) {
        unset($_SESSION['login_attempts'][$ip]);
    }
}

// Check if locked out
$max_attempts = 5;
$is_locked_out = false;
$remaining_time = 0;

if (isset($_SESSION['login_attempts'][$ip_address])) {
    $attempts = $_SESSION['login_attempts'][$ip_address];
    if ($attempts['count'] >= $max_attempts) {
        $time_since = $current_time - $attempts['last_attempt'];
        if ($time_since < 900) { // 15 minutes
            $is_locked_out = true;
            $remaining_time = 900 - $time_since;
        } else {
            unset($_SESSION['login_attempts'][$ip_address]);
        }
    }
}

// Simple math CAPTCHA
if (!isset($_SESSION['captcha_num1']) || !isset($_SESSION['captcha_num2'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
    $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    error_log("=== LOGIN ATTEMPT START ===");
    error_log("Username: " . ($_POST['username'] ?? 'empty'));
    error_log("IP: " . $ip_address);
    
    if ($is_locked_out) {
        $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captcha = (int)($_POST['captcha'] ?? 0);
        $remember_me = isset($_POST['remember_me']);
        
        error_log("Captcha: " . $captcha . " (Expected: " . ($_SESSION['captcha_answer'] ?? 'not set') . ")");
        
        // Validate inputs
        if (empty($username)) {
            $error = 'Please enter your username or email.';
            error_log("Error: Empty username");
        } elseif (empty($password)) {
            $error = 'Please enter your password.';
            error_log("Error: Empty password");
        } elseif ($captcha !== ($_SESSION['captcha_answer'] ?? 0)) {
            $error = 'Incorrect math answer. Please try again.';
            error_log("Error: Wrong captcha");
            // Reset CAPTCHA
            $_SESSION['captcha_num1'] = rand(1, 10);
            $_SESSION['captcha_num2'] = rand(1, 10);
            $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
        } else {
            error_log("All validations passed. Attempting database login...");
            
            // Try to authenticate
            try {
                // Check if database files exist
                if (!file_exists('config/database.php')) {
                    throw new Exception("Database config file not found");
                }
                
                require_once 'config/database.php';
                error_log("Database config loaded");
                
                $database = new Database('kidssmart_users');
                $db = $database->getConnection();
                
                if (!$db) {
                    throw new Exception("Database connection failed");
                }
                
                error_log("Database connected successfully");
                
                $query = "SELECT user_id, username, email, password_hash, first_name, last_name, is_active, is_verified 
                          FROM users 
                          WHERE (username = ? OR email = ?) AND is_active = 1 
                          LIMIT 1";
                
                $stmt = $db->prepare($query);
                $stmt->execute([$username, $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                error_log("Database query executed. User found: " . ($user ? "Yes (ID: {$user['user_id']})" : "No"));
                
                if ($user) {
                    error_log("Password verification: " . (password_verify($password, $user['password_hash']) ? "SUCCESS" : "FAILED"));
                    error_log("User verified: " . ($user['is_verified'] ? "Yes" : "No"));
                    error_log("User active: " . ($user['is_active'] ? "Yes" : "No"));
                    
                    if (password_verify($password, $user['password_hash'])) {
                        // VERIFICATION CHECK DISABLED FOR NOW
                        // TODO: Re-enable when email verification system is implemented
                        /*
                        if ($user['is_verified'] == 0) {
                            $error = 'Please verify your email before logging in.';
                            error_log("Login failed: Email not verified");
                        } else {
                        */
                        
                        // SUCCESS! Set session variables
                        $_SESSION['user_logged_in'] = true;
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                        $_SESSION['login_time'] = time();
                        
                        // Note verification status for admin reference
                        $_SESSION['user_verified'] = (bool)$user['is_verified'];
                        
                        error_log("Session variables set successfully");
                        error_log("User verification status: " . ($user['is_verified'] ? "Verified" : "NOT VERIFIED (manual verification needed)"));
                        
                        // Clear failed attempts
                        unset($_SESSION['login_attempts'][$ip_address]);
                        
                        // Clear CAPTCHA
                        unset($_SESSION['captcha_num1'], $_SESSION['captcha_num2'], $_SESSION['captcha_answer']);
                        
                        // Handle remember me
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
                            error_log("Remember me cookie set");
                        }
                        
                        error_log("SUCCESS: Login completed for user ID " . $user['user_id']);
                        
                        // Redirect
                        $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
                        unset($_SESSION['redirect_after_login']);
                        
                        error_log("Redirecting to: " . $redirect);
                        error_log("=== LOGIN ATTEMPT END: SUCCESS ===");
                        
                        header('Location: ' . $redirect);
                        exit;
                        
                        /*
                        }
                        */
                    } else {
                        error_log("Password verification failed");
                    }
                } else {
                    error_log("No user found with username/email: " . $username);
                }
                
                // If we get here, login failed
                if (!isset($_SESSION['login_attempts'][$ip_address])) {
                    $_SESSION['login_attempts'][$ip_address] = ['count' => 0, 'last_attempt' => 0];
                }
                $_SESSION['login_attempts'][$ip_address]['count']++;
                $_SESSION['login_attempts'][$ip_address]['last_attempt'] = time();
                
                $remaining_attempts = $max_attempts - $_SESSION['login_attempts'][$ip_address]['count'];
                if ($remaining_attempts > 0) {
                    $error = "Invalid login credentials. {$remaining_attempts} attempts remaining.";
                } else {
                    $error = "Too many failed attempts. Account locked for 15 minutes.";
                }
                
                // Reset CAPTCHA
                $_SESSION['captcha_num1'] = rand(1, 10);
                $_SESSION['captcha_num2'] = rand(1, 10);
                $_SESSION['captcha_answer'] = $_SESSION['captcha_num1'] + $_SESSION['captcha_num2'];
                
                error_log("Login failed. Attempts: " . $_SESSION['login_attempts'][$ip_address]['count']);
                
            } catch (Exception $e) {
                error_log("Login exception: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $error = 'Login system temporarily unavailable. Please try again.';
            }
            
            error_log("=== LOGIN ATTEMPT END: FAILED ===");
        }
    }
}

// Handle success messages
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = 'Password reset email sent!';
}
if (isset($_GET['verified']) && $_GET['verified'] === 'success') {
    $success = 'Email verified successfully!';
}
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $success = 'Registration successful! Please log in.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border: none;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        .captcha-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
        }
        .btn-primary {
            background: #007bff;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .verification-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container d-flex align-items-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <!-- Back to Home -->
                    <div class="text-center mb-4">
                        <a href="index.php" class="text-white text-decoration-none">
                            <i class="fas fa-arrow-left me-2"></i>Back to Home
                        </a>
                    </div>
                    
                    <div class="card login-card">
                        <div class="card-body p-5">
                            <!-- Header -->
                            <div class="text-center mb-4">
                                <i class="fas fa-child fa-3x text-primary mb-3"></i>
                                <h1 class="h3 text-primary fw-bold">Welcome Back</h1>
                                <p class="text-muted">Sign in to your KidsSmart account</p>
                            </div>

                            <!-- Verification Notice -->
                            <div class="verification-notice">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Note:</strong> Email verification temporarily disabled. 
                                Account verification will be handled manually by admin if needed.
                            </div>

                            <!-- Messages -->
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <!-- Lockout Message -->
                            <?php if ($is_locked_out): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-lock me-2"></i>
                                    Too many failed attempts. Please wait <?= ceil($remaining_time / 60) ?> minute(s).
                                </div>
                            <?php endif; ?>

                            <!-- Login Form -->
                            <?php if (!$is_locked_out): ?>
                                <form method="POST" action="login.php" id="loginForm" novalidate>
                                    <input type="hidden" name="login" value="1">
                                    
                                    <!-- Username -->
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user me-1"></i>Username or Email
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="username" 
                                               name="username" 
                                               value="<?= htmlspecialchars($username) ?>"
                                               required 
                                               autocomplete="username">
                                    </div>

                                    <!-- Password -->
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock me-1"></i>Password
                                        </label>
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   required 
                                                   autocomplete="current-password">
                                            <button class="btn btn-outline-secondary" 
                                                    type="button" 
                                                    onclick="togglePassword()"
                                                    id="toggleBtn">
                                                <i class="fas fa-eye" id="toggleIcon"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- CAPTCHA -->
                                    <div class="mb-3">
                                        <label for="captcha" class="form-label">
                                            <i class="fas fa-shield-alt me-1"></i>Security Check
                                        </label>
                                        <div class="captcha-box">
                                            What is <?= $_SESSION['captcha_num1'] ?> + <?= $_SESSION['captcha_num2'] ?>?
                                        </div>
                                        <input type="number" 
                                               class="form-control" 
                                               id="captcha" 
                                               name="captcha" 
                                               required 
                                               min="0" 
                                               max="20"
                                               autocomplete="off">
                                    </div>

                                    <!-- Remember Me -->
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" 
                                               class="form-check-input" 
                                               id="remember_me" 
                                               name="remember_me"
                                               <?= $remember_me ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="remember_me">
                                            Remember me for 30 days
                                        </label>
                                    </div>

                                    <!-- Submit Button -->
                                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold" id="submitBtn">
                                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Additional Options -->
                            <div class="text-center mt-4">
                                <div class="mb-3">
                                    <a href="forgot-password.php" class="text-decoration-none">
                                        <i class="fas fa-key me-1"></i>Forgot your password?
                                    </a>
                                </div>
                                
                                <hr>
                                
                                <p class="mb-0">
                                    Don't have an account? 
                                    <a href="signup.php" class="text-decoration-none fw-bold">
                                        <i class="fas fa-user-plus me-1"></i>Sign up for free
                                    </a>
                                </p>
                            </div>

                            <!-- Security Info -->
                            <div class="text-center mt-4">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt text-success me-1"></i>Secure SSL connection |
                                    <i class="fas fa-user-shield text-success me-1"></i>Privacy protected
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Help Links -->
                    <div class="text-center mt-4">
                        <small class="text-white-50">
                            Need help? <a href="contact.php" class="text-white">Contact Support</a> |
                            <a href="privacy-policy.php" class="text-white">Privacy Policy</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (password.type === 'password') {
                password.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                password.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Auto-focus first empty field
        window.addEventListener('load', function() {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            if (!username.value) {
                username.focus();
            } else if (!password.value) {
                password.focus();
            }
        });

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing In...';
            
            // Re-enable after 5 seconds in case of issues
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i>Sign In';
            }, 5000);
        });

        <?php if ($is_locked_out && $remaining_time > 0): ?>
        // Countdown timer
        let timeLeft = <?= $remaining_time ?>;
        const timer = setInterval(function() {
            timeLeft--;
            if (timeLeft <= 0) {
                clearInterval(timer);
                location.reload();
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>