<?php
session_start();

function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_data() {
    return $_SESSION['user_data'] ?? null;
}

function redirect_if_logged_in($redirect_to = 'dashboard.php') {
    if (is_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

function redirect_if_not_logged_in($redirect_to = 'login.php') {
    if (!is_logged_in()) {
        header("Location: $redirect_to");
        exit;
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>