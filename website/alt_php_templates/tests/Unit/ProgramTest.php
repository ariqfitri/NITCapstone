<?php
namespace KidsSmart\Tests\Unit;

use PHPUnit\Framework\TestCase;
use KidsSmart\Tests\TestHelpers\DatabaseTestHelper;
use KidsSmart\Tests\TestHelpers\TestDataFactory;

/**
 * Program Model Tests
 * Tests only the most critical functionality
 */
class ProgramTest extends TestCase
{
    private $program;
    private $db;
    
    protected function setUp(): void
    {
        DatabaseTestHelper::setupTestTables();
        $this->db = DatabaseTestHelper::getTestDbConnection();
        require_once PROJECT_ROOT . '/models/Program.php';
        $this->program = new \Program($this->db);
        DatabaseTestHelper::cleanupTestData();
    }
    
    protected function tearDown(): void
    {
        DatabaseTestHelper::cleanupTestData();
    }
    
    /**
     * Test getting featured programs (homepage functionality)
     */
    public function testGetFeaturedPrograms(): void
    {
        // Create test activities
        $activity1 = TestDataFactory::createActivityData(['title' => 'Football', 'is_approved' => 1]);
        $activity2 = TestDataFactory::createActivityData(['title' => 'Art', 'is_approved' => 1]);
        DatabaseTestHelper::insertTestActivity($activity1);
        DatabaseTestHelper::insertTestActivity($activity2);
        
        $featured = $this->program->getFeaturedPrograms(5);
        
        $this->assertIsArray($featured);
        $this->assertCount(2, $featured);
        $this->assertEquals('Art', $featured[0]['title']); // Last inserted first
    }
    
    /**
     * Test search functionality (core feature)
     */
    public function testSearchPrograms(): void
    {
        // Create test activities
        $swimming = TestDataFactory::createActivityData([
            'title' => 'Football Training',
            'category' => 'Sports',
            'suburb' => 'Melbourne',
            'is_approved' => 1
        ]);
        $art = TestDataFactory::createActivityData([
            'title' => 'Art Workshop',
            'category' => 'Arts', 
            'suburb' => 'Footscray',
            'is_approved' => 1
        ]);
        DatabaseTestHelper::insertTestActivity($swimming);
        DatabaseTestHelper::insertTestActivity($art);
        
        // Test search by title
        $results = $this->program->searchPrograms('Football', '', '', 1, 10);
        $this->assertCount(1, $results);
        $this->assertEquals('Football Training', $results[0]['title']);
        
        // Test search by category
        $results = $this->program->searchPrograms('', 'Sports', '', 1, 10);
        $this->assertCount(1, $results);
        
        // Test search by suburb
        $results = $this->program->searchPrograms('', '', 'Melbourne', 1, 10);
        $this->assertCount(1, $results);
    }
    
    /**
     * Test getting individual program (detail page)
     */
    public function testGetProgramById(): void
    {
        $activityData = TestDataFactory::createActivityData([
            'title' => 'Test Activity',
            'is_approved' => 1
        ]);
        $activityId = DatabaseTestHelper::insertTestActivity($activityData);
        
        $program = $this->program->getProgramById($activityId);
        
        $this->assertIsArray($program);
        $this->assertEquals('Test Activity', $program['title']);
        $this->assertEquals($activityId, $program['activity_id']);
        
        // Test non-existent program
        $nonExistent = $this->program->getProgramById(99999);
        $this->assertFalse($nonExistent);
    }
}