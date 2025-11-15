<?php
namespace KidsSmart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KidsSmart\Tests\TestHelpers\DatabaseTestHelper;
use KidsSmart\Tests\TestHelpers\AuthTestHelper;
use KidsSmart\Tests\TestHelpers\TestDataFactory;

/**
 * User Authentication Tests
 * Tests only critical login/registration functionality
 */
class UserTest extends TestCase
{
    private $user;
    private $db;
    
    protected function setUp(): void
    {
        DatabaseTestHelper::setupTestTables();
        $this->db = DatabaseTestHelper::getTestUsersDbConnection();
        require_once PROJECT_ROOT . '/models/User.php';
        $this->user = new \User($this->db);
        DatabaseTestHelper::cleanupTestData();
        AuthTestHelper::logout();
        require_once PROJECT_ROOT . '/config/auth.php';
    }
    
    protected function tearDown(): void
    {
        DatabaseTestHelper::cleanupTestData();
        AuthTestHelper::logout();
    }
    
    /**
     * Test user registration
     */
    public function testUserRegistration(): void
    {
        $userData = TestDataFactory::createUserData([
            'username' => 'testuser',
            'email' => 'test@example.com'
        ]);
        
        // Set user properties
        $this->user->username = $userData['username'];
        $this->user->email = $userData['email'];
        $this->user->password_hash = $userData['password_hash'];
        $this->user->first_name = $userData['first_name'];
        $this->user->last_name = $userData['last_name'];
        
        $result = $this->user->create();
        
        $this->assertTrue($result);
        $this->assertGreaterThan(0, $this->user->user_id);
        
        // Test duplicate prevention
        $this->assertTrue($this->user->usernameExists());
        $this->assertTrue($this->user->emailExists());
    }
    
    /**
     * Test user login
     */
    public function testUserLogin(): void
    {
        $password = 'testpass123';
        $userData = TestDataFactory::createUserData([
            'username' => 'loginuser', 
            'email' => 'login@test.com',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT)
        ]);
        $userId = DatabaseTestHelper::insertTestUser($userData);
        
        // Test successful login
        $this->user->username = 'loginuser';
        $this->user->email = 'loginuser';
        $this->user->password_hash = $password;  // FIXED: Set plain password for login verification
        $loginResult = $this->user->login();
        
        $this->assertTrue($loginResult !== false);
        $this->assertEquals($userId, $this->user->user_id);
        
        // Test failed login
        $this->user = new \User($this->db);
        $this->user->username = 'nonexistent';
        $this->user->email = 'nonexistent';
        $this->user->password_hash = 'wrongpassword';
        $failedLogin = $this->user->login();
        
        $this->assertFalse($failedLogin);
    }
    
    /**
     * Test session management
     */
    public function testSessionManagement(): void
    {
        $userData = TestDataFactory::createUserData();
        $userId = DatabaseTestHelper::insertTestUser($userData);
        
        // Test login session
        AuthTestHelper::loginAsUser($userId, $userData);
        
        $this->assertTrue(is_logged_in());
        $this->assertEquals($userId, get_current_user_id());
        $this->assertIsArray(get_current_user_data());
        
        // Test logout
        AuthTestHelper::logout();
        
        $this->assertFalse(is_logged_in());
        $this->assertEquals(0, get_current_user_id());
        $this->assertNull(get_current_user_data());
    }
    
    /**
     * Test password security
     */
    public function testPasswordSecurity(): void
    {
        $password = 'TestPassword123!';
        
        // Test hashing
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
        
        // Test wrong password
        $this->assertFalse(password_verify('wrongpassword', $hash));
        
        // Test hash algorithm
        $hashInfo = password_get_info($hash);
        $this->assertEquals('bcrypt', $hashInfo['algoName']);
    }
}