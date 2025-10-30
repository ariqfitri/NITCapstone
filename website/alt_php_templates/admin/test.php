<?php
// Create this file as /admin/test.php to check basic functionality

echo "<h1>Admin Test Page</h1>";
echo "<p>PHP is working: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test database connections
echo "<h2>Database Tests</h2>";

try {
    require_once __DIR__ . '/../config/database.php';
    echo "<p>✅ Database config loaded</p>";
    
    $appDatabase = new Database('kidssmart_app');
    $appDb = $appDatabase->getConnection();
    echo "<p>✅ App database connected</p>";
    
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
    echo "<p>✅ User database connected</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test models
echo "<h2>Model Tests</h2>";

try {
    require_once __DIR__ . '/../models/User.php';
    echo "<p>✅ User model loaded</p>";
    
    require_once __DIR__ . '/../models/Program.php';
    echo "<p>✅ Program model loaded</p>";
    
    require_once __DIR__ . '/../models/Scraper.php';
    echo "<p>✅ Scraper model loaded</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Model error: " . $e->getMessage() . "</p>";
}

// Test admin auth
echo "<h2>Admin Auth Tests</h2>";

try {
    require_once __DIR__ . '/includes/admin_auth.php';
    echo "<p>✅ Admin auth loaded</p>";
    echo "<p>Admin logged in: " . (is_admin_logged_in() ? 'Yes' : 'No') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Admin auth error: " . $e->getMessage() . "</p>";
}

echo "<h2>File Structure Check</h2>";
echo "<pre>";
echo "Admin directory contents:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "  " . $file . "\n";
    }
}
echo "</pre>";

echo "<p><a href='login.php'>Go to Login</a></p>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>