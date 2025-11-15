<?php
namespace KidsSmart\Tests\TestHelpers;

use Faker\Factory as FakerFactory;

/**
 * Test Data Factory
 * Generates realistic test data for activities, users, and other entities
 */
class TestDataFactory
{
    private static $faker = null;
    
    /**
     * Get Faker instance
     */
    private static function getFaker()
    {
        if (self::$faker === null) {
            self::$faker = FakerFactory::create('en_AU');
        }
        return self::$faker;
    }
    
    /**
     * Generate test activity data
     */
    public static function createActivityData(array $overrides = []): array
    {
        $faker = self::getFaker();
        
        $categories = ['Sports', 'Arts & Crafts', 'Music', 'Educational', 'Outdoor Adventures', 'Technology'];
        $suburbs = ['Adelaide', 'North Adelaide', 'Glenelg', 'Brighton', 'Norwood', 'Burnside'];
        $ageRanges = ['0-3 years', '4-6 years', '7-12 years', '13-17 years', 'All Ages'];
        
        $defaultData = [
            'title' => $faker->words(3, true) . ' for Kids',
            'description' => $faker->paragraph(3),
            'category' => $faker->randomElement($categories),
            'suburb' => $faker->randomElement($suburbs),
            'postcode' => $faker->numberBetween(5000, 5999),
            'address' => $faker->streetAddress,
            'phone' => $faker->phoneNumber,
            'email' => $faker->email,
            'website' => $faker->url,
            'age_range' => $faker->randomElement($ageRanges),
            'cost' => '$' . $faker->numberBetween(10, 200) . ' per session',
            'schedule' => $faker->randomElement(['Weekdays', 'Weekends', 'Monday-Friday', 'Flexible']),
            'image_url' => $faker->imageUrl(400, 300, 'children'),
            'source_name' => $faker->company,
            'source_url' => $faker->url,
            'is_approved' => 1,
            'scraped_at' => $faker->dateTimeThisYear->format('Y-m-d H:i:s')
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Generate test user data
     */
    public static function createUserData(array $overrides = []): array
    {
        $faker = self::getFaker();
        
        $suburbs = ['Adelaide', 'North Adelaide', 'Glenelg', 'Brighton', 'Norwood', 'Burnside'];
        $ageRanges = ['0-3 years', '4-6 years', '7-12 years', '13-17 years'];
        
        $firstName = $faker->firstName;
        $lastName = $faker->lastName;
        
        $defaultData = [
            'username' => strtolower($firstName . $lastName . $faker->numberBetween(1, 999)),
            'email' => $faker->unique()->email,
            'password_hash' => password_hash('testpass123', PASSWORD_BCRYPT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'suburb' => $faker->randomElement($suburbs),
            'postcode' => $faker->numberBetween(5000, 5999),
            'child_age_range' => $faker->randomElement($ageRanges),
            'preferences' => json_encode(['newsletter' => true, 'notifications' => false]),
            'is_admin' => 0,
            'admin_level' => 0,
            'is_active' => 1
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Generate test admin user data
     */
    public static function createAdminData(array $overrides = []): array
    {
        $adminData = [
            'is_admin' => 1,
            'admin_level' => 1,
            'username' => 'testadmin' . rand(1, 1000),
            'email' => 'admin@test.com',
            'password_hash' => password_hash('admin123', PASSWORD_BCRYPT)
        ];
        
        return self::createUserData(array_merge($adminData, $overrides));
    }
    
    /**
     * Generate test favourite data
     */
    public static function createFavouriteData(int $userId, int $activityId, array $overrides = []): array
    {
        $faker = self::getFaker();
        
        $defaultData = [
            'user_id' => $userId,
            'activity_id' => $activityId,
            'activity_title' => $faker->words(3, true),
            'activity_url' => $faker->url,
            'activity_image' => $faker->imageUrl(300, 200),
            'activity_age_range' => $faker->randomElement(['0-3 years', '4-6 years', '7-12 years']),
            'activity_category' => $faker->randomElement(['Sports', 'Arts & Crafts', 'Music'])
        ];
        
        return array_merge($defaultData, $overrides);
    }
    
    /**
     * Create multiple test activities
     */
    public static function createMultipleActivities(int $count = 5, array $overrides = []): array
    {
        $activities = [];
        for ($i = 0; $i < $count; $i++) {
            $activities[] = self::createActivityData($overrides);
        }
        return $activities;
    }
    
    /**
     * Create multiple test users
     */
    public static function createMultipleUsers(int $count = 3, array $overrides = []): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::createUserData($overrides);
        }
        return $users;
    }
}
