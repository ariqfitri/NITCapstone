<?php
require_once __DIR__ . '/includes/admin_auth.php';

// Simple admin authentication (you might want to use your Flask user system instead)
$admin_username = 'admin';
$admin_password = 'admin123'; // Change this in production!

if ($_POST['login'] ?? false) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === $admin_username && $password === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

// Redirect if already logged in
if (is_admin_logged_in()) {
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
    <link href="../static/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <!-- Hero Background Section -->
    <section class="hero-section d-flex align-items-center min-vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-lg">
                        <div class="card-header bg-primary text-white text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-child fa-3x"></i>
                            </div>
                            <h2 class="card-title mb-0">KidsSmart Admin</h2>
                            <p class="card-text mb-0">Administrative Portal</p>
                        </div>
                        <div class="card-body p-4">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post">
                                <div class="mb-3">
                                    <label for="username" class="form-label">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <input type="text" class="form-control form-control-lg" id="username" name="username" required>
                                </div>
                                <div class="mb-4">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="login" value="1" class="btn btn-primary btn-lg">
                                        <i class="fas fa-sign-in-alt me-2"></i>Login to Admin Panel
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer text-center text-muted">
                            <small>
                                <i class="fas fa-shield-alt me-1"></i>
                                Secure Administrator Access
                            </small>
                        </div>
                    </div>
                    
                    <!-- Back to main site link -->
                    <div class="text-center mt-4">
                        <a href="../index.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Main Site
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>