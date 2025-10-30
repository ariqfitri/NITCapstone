<?php
/**
 * Admin Authentication Helper
 * Fixed version to prevent session header warnings
 */

// Only start session if one hasn't been started already AND no output has been sent
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin_login($redirect_to = 'login.php') {
    if (!is_admin_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

function admin_logout() {
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    header("Location: login.php");
    exit;
}

function set_admin_flash_message($message, $type = 'success') {
    $_SESSION['admin_flash_message'] = $message;
    $_SESSION['admin_flash_type'] = $type;
}

function get_admin_flash_message() {
    if (isset($_SESSION['admin_flash_message'])) {
        $message = $_SESSION['admin_flash_message'];
        $type = $_SESSION['admin_flash_type'] ?? 'success';
        unset($_SESSION['admin_flash_message'], $_SESSION['admin_flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>