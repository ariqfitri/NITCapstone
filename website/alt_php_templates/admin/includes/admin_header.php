<?php
// Check if user is logged in and is admin
if (!isset($no_auth_check)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!($_SESSION['admin_logged_in'] ?? false)) {
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Admin Panel' ?> - KidsSmart</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/admin_style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
            <i class="fas fa-child me-2"></i>KidsSmart Admin
        </a>
        
        <div class="navbar-nav ms-auto">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="../dashboard.php" target="_blank">
                    <i class="fas fa-user me-1"></i> User Dashboard
                </a>
            </div>
        </div>
        
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i> Sign out
                </a>
            </div>
        </div>
    </nav>