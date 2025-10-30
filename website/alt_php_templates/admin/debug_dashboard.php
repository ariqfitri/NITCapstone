<?php
// Create this as /admin/debug_dashboard.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Dashboard</h1>";

// Test 1: Basic PHP
echo "<h2>1. Basic PHP Test</h2>";
echo "<p>✅ PHP Working</p>";

// Test 2: Session
echo "<h2>2. Session Test</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<p>✅ Session started</p>";

// Test 3: Admin Auth
echo "<h2>3. Admin Auth Test</h2>";
try {
    require_once __DIR__ . '/includes/admin_auth.php';
    echo "<p>✅ Admin auth loaded</p>";
    echo "<p>Admin logged in: " . (is_admin_logged_in() ? 'YES' : 'NO') . "</p>";
    
    if (!is_admin_logged_in()) {
        echo "<p>❌ <strong>NOT LOGGED IN</strong> - Go to <a href='login.php'>login page</a></p>";
        echo "<p>Session data: " . print_r($_SESSION, true) . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin auth error: " . $e->getMessage() . "</p>";
}

// Test 4: Database
echo "<h2>4. Database Test</h2>";
try {
    require_once '../config/database.php';
    echo "<p>✅ Database config loaded</p>";
    
    $appDatabase = new Database('kidssmart_app');
    $appDb = $appDatabase->getConnection();
    echo "<p>✅ App database connected</p>";
    
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
    echo "<p>✅ User database connected</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 5: Models
echo "<h2>5. Model Test</h2>";
try {
    require_once '../models/Program.php';
    echo "<p>✅ Program model loaded</p>";
    
    require_once '../models/User.php';
    echo "<p>✅ User model loaded</p>";
    
    require_once '../models/Scraper.php';
    echo "<p>✅ Scraper model loaded</p>";
} catch (Exception $e) {
    echo "<p>❌ Model error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 6: Model instantiation
echo "<h2>6. Model Instantiation Test</h2>";
try {
    $program = new Program($appDb);
    echo "<p>✅ Program model created</p>";
    
    $user = new User($userDb);
    echo "<p>✅ User model created</p>";
    
    $scraper = new Scraper($appDb);
    echo "<p>✅ Scraper model created</p>";
} catch (Exception $e) {
    echo "<p>❌ Model instantiation error: " . $e->getMessage() . "</p>";
    exit;
}

// Test 7: Basic method calls
echo "<h2>7. Method Call Test</h2>";
try {
    $total_users = $user->getTotalUsersCount();
    echo "<p>✅ getTotalUsersCount(): $total_users</p>";
    
    $total_activities = $program->getTotalProgramsCount();
    echo "<p>✅ getTotalProgramsCount(): $total_activities</p>";
    
    $pending = $program->getPendingActivities();
    echo "<p>✅ getPendingActivities(): " . count($pending) . " items</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Method call error: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $e->getTraceAsString() . "</p>";
}

// Test 8: Enhanced methods
echo "<h2>8. Enhanced Method Test</h2>";
try {
    // Test new methods if they exist
    if (method_exists($user, 'getNewUsersToday')) {
        $new_today = $user->getNewUsersToday();
        echo "<p>✅ getNewUsersToday(): $new_today</p>";
    } else {
        echo "<p>⚠️ getNewUsersToday() method not found</p>";
    }
    
    if (method_exists($scraper, 'getFailedScrapers')) {
        $failed = $scraper->getFailedScrapers();
        echo "<p>✅ getFailedScrapers(): " . count($failed) . " items</p>";
    } else {
        echo "<p>⚠️ getFailedScrapers() method not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Enhanced method error: " . $e->getMessage() . "</p>";
}

echo "<h2>9. Test Complete</h2>";
echo "<p>If all tests pass, the issue might be in the dashboard HTML/CSS</p>";
echo "<p><a href='dashboard.php'>Try Dashboard Again</a></p>";
?>