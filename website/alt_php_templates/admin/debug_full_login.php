<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Login Debug Test</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    require_once '../config/database.php';
    $userDatabase = new Database('kidssmart_users');
    $userDb = $userDatabase->getConnection();
    echo "✅ Database connection: SUCCESS<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if user exists
echo "<h3>2. User Query Test</h3>";
$username = 'superadmin';
$query = "SELECT user_id, username, email, password_hash, first_name, last_name, 
                is_admin, admin_level, can_manage_admins, can_access_system_settings, is_active 
         FROM users 
         WHERE (username = ? OR email = ?) AND is_admin = 1 AND is_active = 1 
         LIMIT 1";

$stmt = $userDb->prepare($query);
$stmt->execute([$username, $username]);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin_user) {
    echo "✅ User found in database<br>";
    echo "<strong>User Data:</strong><br>";
    echo "- user_id: " . $admin_user['user_id'] . "<br>";
    echo "- username: " . $admin_user['username'] . "<br>";
    echo "- email: " . $admin_user['email'] . "<br>";
    echo "- is_admin: " . $admin_user['is_admin'] . "<br>";
    echo "- admin_level: " . $admin_user['admin_level'] . "<br>";
    echo "- is_active: " . $admin_user['is_active'] . "<br>";
    echo "- password_hash: " . substr($admin_user['password_hash'], 0, 30) . "...<br>";
} else {
    echo "❌ User not found or doesn't meet criteria<br>";
    exit;
}

// Test 3: Password verification
echo "<h3>3. Password Verification Test</h3>";
$test_password = 'KidsSmartAdmin2025!';
$hash_from_db = $admin_user['password_hash'];

echo "<strong>Testing password:</strong> '$test_password'<br>";
echo "<strong>Against hash:</strong> " . substr($hash_from_db, 0, 40) . "...<br>";

$password_result = password_verify($test_password, $hash_from_db);
if ($password_result) {
    echo "✅ Password verification: SUCCESS<br>";
} else {
    echo "❌ Password verification: FAILED<br>";
    
    // Let's test with a fresh hash
    echo "<h4>Testing with fresh hash generation:</h4>";
    $fresh_hash = password_hash($test_password, PASSWORD_BCRYPT);
    echo "Fresh hash: $fresh_hash<br>";
    $fresh_test = password_verify($test_password, $fresh_hash);
    echo "Fresh hash test: " . ($fresh_test ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
}

// Test 4: Check database hash validity
echo "<h3>4. Hash Analysis</h3>";
$hash_info = password_get_info($hash_from_db);
echo "Hash algorithm: " . $hash_info['algoName'] . "<br>";
echo "Hash options: " . json_encode($hash_info['options']) . "<br>";

// Test 5: Manual character check
echo "<h3>5. Character Analysis</h3>";
echo "Password length: " . strlen($test_password) . "<br>";
echo "Password bytes: ";
for ($i = 0; $i < strlen($test_password); $i++) {
    echo ord($test_password[$i]) . " ";
}
echo "<br>";
?>