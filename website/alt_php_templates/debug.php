<?php
require_once 'config/database.php';

echo "<h1>Database Debug Information</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p style='color: green;'>✓ Database connection successful!</p>";
    
    // Check if activities table exists and has data
    $stmt = $db->query("SELECT COUNT(*) as count FROM activities");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total activities in database: <strong>{$result['count']}</strong></p>";
    
    // Show some sample activities
    $stmt = $db->query("SELECT activity_id, title, category, suburb, is_approved FROM activities LIMIT 5");
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($activities) > 0) {
        echo "<h3>Sample Activities:</h3>";
        echo "<ul>";
        foreach ($activities as $activity) {
            $approved = $activity['is_approved'] ? '✓ Approved' : '✗ Not Approved';
            echo "<li>{$activity['title']} - {$activity['category']} in {$activity['suburb']} ($approved)</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: orange;'>No activities found in the database.</p>";
    }
    
    // Check environment variables
    echo "<h3>Environment Variables:</h3>";
    echo "<ul>";
    echo "<li>DB_HOST: " . (getenv('DB_HOST') ?: 'Not set') . "</li>";
    echo "<li>DB_NAME: " . (getenv('DB_NAME') ?: 'Not set') . "</li>";
    echo "<li>DB_USER: " . (getenv('DB_USER') ?: 'Not set') . "</li>";
    echo "<li>DB_PASSWORD: " . (getenv('DB_PASSWORD') ? '***' : 'Not set') . "</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}
?>