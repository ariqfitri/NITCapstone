<?php
// Check session status before starting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data - handles both old and new session formats
 */
function get_current_user_data() {
    // Check if user_data exists in session (new format)
    if (isset($_SESSION['user_data']) && !empty($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }
    
    // Fallback to individual session variables (old format from login.php)
    if (is_logged_in()) {
        return [
            'username' => $_SESSION['username'] ?? '',
            'first_name' => $_SESSION['user_name'] ? explode(' ', $_SESSION['user_name'])[0] : ($_SESSION['username'] ?? ''),
            'last_name' => $_SESSION['user_name'] && strpos($_SESSION['user_name'], ' ') !== false ? 
                          substr($_SESSION['user_name'], strpos($_SESSION['user_name'], ' ') + 1) : '',
            'email' => $_SESSION['user_email'] ?? '',
            'full_name' => $_SESSION['user_name'] ?? ''
        ];
    }
    
    return null;
}

/**
 * Redirect if user is already logged in
 */
function redirect_if_logged_in($redirect_to = 'dashboard.php') {
    if (is_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * Redirect if user is not logged in
 */
function redirect_if_not_logged_in($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is admin
 */
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}
?>