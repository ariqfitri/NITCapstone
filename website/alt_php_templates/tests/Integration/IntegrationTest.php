<?php
namespace KidsSmart\Tests\Integration;

use PHPUnit\Framework\TestCase;
use KidsSmart\Tests\TestHelpers\DatabaseTestHelper;
use KidsSmart\Tests\TestHelpers\TestDataFactory;

/**
 * Cross-Database Integration Test
 * Tests critical database interactions between activities and users
 */
class MIntegrationTest extends TestCase
{
    private $appDb;
    private $userDb;
    
    protected function setUp(): void
    {
        DatabaseTestHelper::setupTestTables();
        $this->appDb = DatabaseTestHelper::getTestDbConnection();
        $this->userDb = DatabaseTestHelper::getTestUsersDbConnection();
        DatabaseTestHelper::cleanupTestData();
    }
    
    protected function tearDown(): void
    {
        DatabaseTestHelper::cleanupTestData();
    }
    
    /**
     * Test database connections work
     */
    public function testDatabaseConnections(): void
    {
        // Test both databases are accessible
        $this->assertInstanceOf(\PDO::class, $this->appDb);
        $this->assertInstanceOf(\PDO::class, $this->userDb);
        
        // Test we can execute queries
        $appResult = $this->appDb->query("SELECT 1 as test")->fetch();
        $this->assertEquals(1, $appResult['test']);
        
        $userResult = $this->userDb->query("SELECT 1 as test")->fetch();
        $this->assertEquals(1, $userResult['test']);
    }
    
    /**
     * Test user favouriting activities across databases
     */
    public function testUserFavouriteActivity(): void
    {
        require_once PROJECT_ROOT . '/models/Program.php';
        require_once PROJECT_ROOT . '/models/User.php';
        require_once PROJECT_ROOT . '/models/Favourite.php';
        
        // Create user in users database
        $userData = TestDataFactory::createUserData(['username' => 'testuser']);
        $userId = DatabaseTestHelper::insertTestUser($userData);
        
        // Create activity in app database
        $activityData = TestDataFactory::createActivityData(['title' => 'Test Activity']);
        $activityId = DatabaseTestHelper::insertTestActivity($activityData);
        
        // Test models work with appropriate databases
        $program = new \Program($this->appDb);
        $user = new \User($this->userDb);
        $favourite = new \Favourite($this->userDb);
        
        // Verify activity can be retrieved
        $retrievedActivity = $program->getProgramById($activityId);
        $this->assertIsArray($retrievedActivity);
        $this->assertEquals('Test Activity', $retrievedActivity['title']);
        
        // Verify user can be loaded
        $user->user_id = $userId;
        $userLoaded = $user->readOne();
        $this->assertTrue($userLoaded);
        $this->assertEquals('testuser', $user->username);
        
        // Test favouriting (cross-database operation)
        $favourite->user_id = $userId;
        $favourite->activity_id = $activityId;
        
        $this->assertFalse($favourite->isFavourited());
        
        $favouriteCreated = $favourite->create();
        $this->assertTrue($favouriteCreated);
        $this->assertTrue($favourite->isFavourited());
        
        // Test removing favourite
        $favouriteRemoved = $favourite->delete();
        $this->assertTrue($favouriteRemoved);
        $this->assertFalse($favourite->isFavourited());
    }
    
    /**
     * Test data consistency across databases
     */
    public function testDataConsistency(): void
    {
        require_once PROJECT_ROOT . '/models/Favourite.php';
        
        // Create test data
        $userId = DatabaseTestHelper::insertTestUser(TestDataFactory::createUserData());
        $activityId1 = DatabaseTestHelper::insertTestActivity(TestDataFactory::createActivityData(['title' => 'Activity 1']));
        $activityId2 = DatabaseTestHelper::insertTestActivity(TestDataFactory::createActivityData(['title' => 'Activity 2']));
        
        $favourite = new \Favourite($this->userDb);
        
        // Add multiple favourites
        $favourite->user_id = $userId;
        $favourite->activity_id = $activityId1;
        $this->assertTrue($favourite->create());
        
        $favourite->activity_id = $activityId2;
        $this->assertTrue($favourite->create());
        
        // Get user favourites
        $userFavourites = $favourite->getUserFavourites($userId);
        $this->assertCount(2, $userFavourites);
        
        // Verify favourite data contains activity information
        foreach ($userFavourites as $fav) {
            $this->assertArrayHasKey('activity_id', $fav);
            $this->assertArrayHasKey('activity_title', $fav);
            $this->assertEquals($userId, $fav['user_id']);
            $this->assertContains($fav['activity_id'], [$activityId1, $activityId2]);
        }
    }
}