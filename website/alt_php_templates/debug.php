<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<h2>All Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Authentication Check:</h2>";
echo "is_logged_in(): " . (function_exists('is_logged_in') ? (is_logged_in() ? 'TRUE' : 'FALSE') : 'Function not found') . "<br>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Session user_data: " . ($_SESSION['user_data'] ? 'SET' : 'NOT SET') . "<br>";

if (isset($_SESSION['user_data'])) {
    echo "<h3>User Data:</h3>";
    echo "<pre>";
    print_r($_SESSION['user_data']);
    echo "</pre>";
}

// Include auth.php to test functions
try {
    require_once 'config/auth.php';
    echo "<h2>After including auth.php:</h2>";
    echo "is_logged_in(): " . (is_logged_in() ? 'TRUE' : 'FALSE') . "<br>";
    echo "get_current_user_id(): " . get_current_user_id() . "<br>";
    echo "get_current_user_data(): " . (get_current_user_data() ? 'HAS DATA' : 'NO DATA') . "<br>";
} catch (Exception $e) {
    echo "Error including auth.php: " . $e->getMessage();
}
?>