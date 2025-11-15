<?php
namespace KidsSmart\Tests\TestHelpers;

/**
 * Authentication Test Helper
 * Manages test user authentication and session handling for testing
 */
class AuthTestHelper
{
    /**
     * Create a test admin user session
     */
    public static function loginAsAdmin(int $userId = 1, string $username = 'testadmin'): void
    {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_level'] = 1;
        $_SESSION['can_manage_admins'] = true;
    }
    
    /**
     * Create a test regular user session
     */
    public static function loginAsUser(int $userId, array $userData = []): void
    {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_data'] = array_merge([
            'user_id' => $userId,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User'
        ], $userData);
    }
    
    /**
     * Logout all test sessions
     */
    public static function logout(): void
    {
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_level']);
        unset($_SESSION['can_manage_admins']);
        unset($_SESSION['user_id']);
        unset($_SESSION['user_logged_in']);
        unset($_SESSION['user_data']);
    }
    
    /**
     * Check if current test session is admin
     */
    public static function isAdminLoggedIn(): bool
    {
        return $_SESSION['admin_logged_in'] ?? false;
    }
    
    /**
     * Check if current test session is regular user
     */
    public static function isUserLoggedIn(): bool
    {
        return $_SESSION['user_logged_in'] ?? false;
    }
    
    /**
     * Get current test user ID
     */
    public static function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Generate test password hash
     */
    public static function generatePasswordHash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
