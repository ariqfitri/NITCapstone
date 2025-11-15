<?php
namespace KidsSmart\Tests\TestHelpers;

use PDO;
use PDOException;

/**
 * Database Test Helper
 * Manages test database connections, setup, and cleanup operations
 */
class DatabaseTestHelper
{
    private static $testDbConnection = null;
    private static $testUsersDbConnection = null;
    
    /**
     * Get test database connection for activities/programs
     */
    public static function getTestDbConnection(): PDO
    {
        if (self::$testDbConnection === null) {
            try {
                $host = '127.0.0.1';
                $dbname = 'kidssmart_test';
                $username = 'app_user';
                $password = 'AppPass123!';
                
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                
                self::$testDbConnection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
            } catch (PDOException $e) {
                throw new \Exception("Test database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$testDbConnection;
    }
    
    /**
     * Get test users database connection
     */
    public static function getTestUsersDbConnection(): PDO
    {
        if (self::$testUsersDbConnection === null) {
            try {
                $host = '127.0.0.1';
                $dbname = 'kidssmart_users_test';
                $username = 'app_user';
                $password = 'AppPass123!';
                
                $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
                
                self::$testUsersDbConnection = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
                
            } catch (PDOException $e) {
                throw new \Exception("Test users database connection failed: " . $e->getMessage());
            }
        }
        
        return self::$testUsersDbConnection;
    }
    
    /**
     * Create test database tables if they don't exist
     */
    public static function setupTestTables(): void
    {
        $db = self::getTestDbConnection();
        $userDb = self::getTestUsersDbConnection();
        
        // Create activities table for testing
        $db->exec("
            CREATE TABLE IF NOT EXISTS activities (
                activity_id INT PRIMARY KEY AUTO_INCREMENT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                category VARCHAR(100),
                suburb VARCHAR(100),
                postcode VARCHAR(10),
                address TEXT,
                phone VARCHAR(50),
                email VARCHAR(100),
                website VARCHAR(255),
                age_range VARCHAR(50),
                cost VARCHAR(100),
                schedule TEXT,
                image_url VARCHAR(500),
                source_name VARCHAR(100),
                source_url VARCHAR(500),
                is_approved TINYINT(1) DEFAULT 1,
                scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create users table for testing
        $userDb->exec("
            CREATE TABLE IF NOT EXISTS users (
                user_id INT PRIMARY KEY AUTO_INCREMENT,
                username VARCHAR(50) UNIQUE NOT NULL,
                email VARCHAR(100) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                suburb VARCHAR(100),
                postcode VARCHAR(10),
                child_age_range VARCHAR(50),
                preferences TEXT,
                is_admin TINYINT(1) DEFAULT 0,
                admin_level INT DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        // Create favourites table for testing
        $userDb->exec("
            CREATE TABLE IF NOT EXISTS favourites (
                favourite_id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                activity_id INT NOT NULL,
                activity_title VARCHAR(255),
                activity_url VARCHAR(500),
                activity_image VARCHAR(500),
                activity_age_range VARCHAR(50),
                activity_category VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_user_activity (user_id, activity_id),
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )
        ");
    }
    
    /**
     * Clean up all test data from tables
     */
    public static function cleanupTestData(): void
    {
        try {
            $db = self::getTestDbConnection();
            $userDb = self::getTestUsersDbConnection();
            
            // Disable foreign key checks for cleanup
            $userDb->exec("SET FOREIGN_KEY_CHECKS = 0");
            
            // Clean up test data
            $userDb->exec("TRUNCATE TABLE favourites");
            $userDb->exec("TRUNCATE TABLE users");
            $db->exec("TRUNCATE TABLE activities");
            
            // Re-enable foreign key checks
            $userDb->exec("SET FOREIGN_KEY_CHECKS = 1");
            
        } catch (PDOException $e) {
            // Ignore cleanup errors in case tables don't exist yet
        }
    }
    
    /**
     * Insert test activity data
     */
    public static function insertTestActivity(array $data): int
    {
        $db = self::getTestDbConnection();
        
        $sql = "INSERT INTO activities (title, description, category, suburb, postcode, age_range, is_approved) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $data['title'] ?? 'Test Activity',
            $data['description'] ?? 'Test Description', 
            $data['category'] ?? 'Test Category',
            $data['suburb'] ?? 'Test Suburb',
            $data['postcode'] ?? '5000',
            $data['age_range'] ?? '5-10 years',
            $data['is_approved'] ?? 1
        ]);
        
        return $db->lastInsertId();
    }
    
    /**
     * Insert test user data
     */
    public static function insertTestUser(array $data): int
    {
        $userDb = self::getTestUsersDbConnection();
        
        $sql = "INSERT INTO users (username, email, password_hash, first_name, last_name, suburb, child_age_range) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $userDb->prepare($sql);
        $stmt->execute([
            $data['username'] ?? 'testuser',
            $data['email'] ?? 'test@example.com',
            $data['password_hash'] ?? password_hash('testpass', PASSWORD_BCRYPT),
            $data['first_name'] ?? 'Test',
            $data['last_name'] ?? 'User',
            $data['suburb'] ?? 'Test Suburb',
            $data['child_age_range'] ?? '5-10 years'
        ]);
        
        return $userDb->lastInsertId();
    }
}