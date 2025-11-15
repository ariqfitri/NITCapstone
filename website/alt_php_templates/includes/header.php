<?php
require_once __DIR__ . '/../config/auth.php';

// Get user data from session for display
$user_display_data = [];
if (is_logged_in()) {
    // Check if user_data exists in session (from newer code)
    if (isset($_SESSION['user_data'])) {
        $user_display_data = $_SESSION['user_data'];
    } else {
        // Fallback to individual session variables (from login.php)
        $user_display_data = [
            'username' => $_SESSION['username'] ?? '',
            'first_name' => $_SESSION['user_name'] ? explode(' ', $_SESSION['user_name'])[0] : ($_SESSION['username'] ?? ''),
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-child"></i> KidsSmart
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-home"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="search.php"><i class="fas fa-search"></i> Find Activities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php"><i class="fas fa-list"></i> Categories</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="about.php"><i class="fas fa-info-circle"></i> About</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" 
                           href="#" 
                           id="userDropdown" 
                           role="button" 
                           data-bs-toggle="dropdown" 
                           aria-expanded="false"
                           style="cursor: pointer;">
                            <i class="fas fa-user"></i> 
                            <?= htmlspecialchars($user_display_data['first_name'] ?? $user_display_data['username'] ?? 'User') ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="favourites.php"><i class="fas fa-heart me-2"></i> Favourites</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="signup.php"><i class="fas fa-user-plus"></i> Sign Up</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>