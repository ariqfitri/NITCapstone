<?php
/**
 * Admin Role Hierarchy Utilities
 * Helper functions to check admin permissions based on role levels
 */

/**
 * Check if user is logged in as admin
 */
function is_admin_logged_in() {
    return ($_SESSION['admin_logged_in'] ?? false) && ($_SESSION['admin_level'] ?? 0) >= 1;
}

/**
 * Check if user is regular admin (Level 1+)
 */
function is_admin() {
    return ($_SESSION['admin_level'] ?? 0) >= 1;
}

/**
 * Check if user is super admin (Level 2)
 */
function is_super_admin() {
    return ($_SESSION['admin_level'] ?? 0) >= 2;
}

/**
 * Check if user can manage other admin accounts
 */
function can_manage_admins() {
    return ($_SESSION['can_manage_admins'] ?? false) === true;
}

/**
 * Check if user can access system settings
 */
function can_access_system_settings() {
    return ($_SESSION['can_access_system_settings'] ?? false) === true;
}

/**
 * Get admin role name for display
 */
function get_admin_role_name() {
    return $_SESSION['admin_role_name'] ?? 'User';
}

/**
 * Get admin level number
 */
function get_admin_level() {
    return $_SESSION['admin_level'] ?? 0;
}

/**
 * Get admin display name
 */
function get_admin_name() {
    return $_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Unknown';
}

/**
 * Require minimum admin level or redirect
 */
function require_admin_level($required_level = 1) {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    if (get_admin_level() < $required_level) {
        header('Location: dashboard.php?error=insufficient_privileges');
        exit;
    }
}

/**
 * Require specific permission or redirect
 */
function require_permission($permission) {
    if (!is_admin_logged_in()) {
        header('Location: login.php');
        exit;
    }
    
    $allowed = false;
    switch ($permission) {
        case 'manage_activities':
        case 'manage_users':
        case 'view_reports':
        case 'run_scrapers':
        case 'manage_categories':
            $allowed = is_admin(); // Level 1+
            break;
            
        case 'manage_admins':
            $allowed = can_manage_admins(); // Super admin only
            break;
            
        case 'system_settings':
        case 'database_management':
        case 'system_logs':
            $allowed = can_access_system_settings(); // Super admin only
            break;
            
        default:
            $allowed = is_admin(); // Default to regular admin
    }
    
    if (!$allowed) {
        header('Location: dashboard.php?error=insufficient_privileges');
        exit;
    }
}

/**
 * Check if user has specific permission (without redirect)
 */
function has_permission($permission) {
    if (!is_admin_logged_in()) {
        return false;
    }
    
    switch ($permission) {
        case 'manage_activities':
        case 'manage_users':
        case 'view_reports':
        case 'run_scrapers':
        case 'manage_categories':
            return is_admin(); // Level 1+
            
        case 'manage_admins':
            return can_manage_admins(); // Super admin only
            
        case 'system_settings':
        case 'database_management':
        case 'system_logs':
            return can_access_system_settings(); // Super admin only
            
        default:
            return is_admin(); // Default to regular admin
    }
}

/**
 * Get admin permissions array for current user
 */
function get_admin_permissions() {
    if (!is_admin_logged_in()) {
        return [];
    }
    
    $permissions = [
        'logged_in' => true,
        'level' => get_admin_level(),
        'role_name' => get_admin_role_name(),
        'username' => $_SESSION['admin_username'] ?? '',
        'name' => get_admin_name(),
        'permissions' => []
    ];
    
    // Basic admin permissions (Level 1+)
    if (is_admin()) {
        $permissions['permissions'] = array_merge($permissions['permissions'], [
            'manage_activities',
            'manage_users', 
            'view_reports',
            'run_scrapers',
            'manage_categories'
        ]);
    }
    
    // Super admin permissions (Level 2)
    if (can_manage_admins()) {
        $permissions['permissions'][] = 'manage_admins';
    }
    
    if (can_access_system_settings()) {
        $permissions['permissions'] = array_merge($permissions['permissions'], [
            'system_settings',
            'database_management',
            'system_logs'
        ]);
    }
    
    return $permissions;
}

/**
 * Display insufficient privilege message
 */
function show_insufficient_privilege_message() {
    return '<div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Insufficient Privileges:</strong> You do not have permission to access this feature.
                <br><small>Contact a Super Administrator if you need additional permissions.</small>
            </div>';
}

/**
 * Generate role-based navigation menu
 */
function get_admin_navigation() {
    if (!is_admin_logged_in()) {
        return [];
    }
    
    $nav = [];
    
    // Always available for admins
    $nav['Dashboard'] = ['url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt'];
    
    // Level 1+ permissions
    if (is_admin()) {
        $nav['Activities'] = ['url' => 'activities.php', 'icon' => 'fas fa-running'];
        $nav['Categories'] = ['url' => 'categories.php', 'icon' => 'fas fa-tags'];
        $nav['Scrapers'] = ['url' => 'admin_scrapers.php', 'icon' => 'fas fa-spider'];
        $nav['Reports'] = ['url' => 'reports.php', 'icon' => 'fas fa-chart-bar'];
    }
    
    // Level 1+ - Users (but not user management for lower levels)
    if (is_admin()) {
        $nav['Users'] = ['url' => 'users.php', 'icon' => 'fas fa-users'];
    }
    
    // Super admin only features
    if (can_manage_admins()) {
        $nav['Admin Management'] = ['url' => 'admin_management.php', 'icon' => 'fas fa-user-shield'];
    }
    
    if (can_access_system_settings()) {
        $nav['System Logs'] = ['url' => 'logs.php', 'icon' => 'fas fa-clipboard-list'];
        $nav['System Settings'] = ['url' => 'settings.php', 'icon' => 'fas fa-cog'];
    }
    
    return $nav;
}

/**
 * Format admin role badge HTML
 */
function get_role_badge() {
    if (!is_admin_logged_in()) {
        return '<span class="badge bg-secondary">Not Logged In</span>';
    }
    
    $level = get_admin_level();
    $badge_class = match($level) {
        1 => 'bg-primary',
        2 => 'bg-warning text-dark',
        default => 'bg-secondary'
    };
    
    return '<span class="badge ' . $badge_class . '">' . get_admin_role_name() . '</span>';
}
?>