<?php
/**
 * PHPUnit Bootstrap File
 * Initializes test environment and sets up autoloading for KidsSmart tests
 */

// Start output buffering to prevent header issues during testing
ob_start();

// Set error reporting for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define testing constants
define('TESTING_MODE', true);
define('TEST_ROOT', __DIR__);
define('PROJECT_ROOT', dirname(__DIR__));

// Include Composer autoloader
require_once PROJECT_ROOT . '/vendor/autoload.php';

// Set up test-specific session handling
if (!session_id()) {
    ini_set('session.use_cookies', 0);
    ini_set('session.use_only_cookies', 0);
    ini_set('session.cache_limiter', '');
    session_start();
}

// Load test configuration and helper functions
require_once __DIR__ . '/TestHelpers/DatabaseTestHelper.php';
require_once __DIR__ . '/TestHelpers/AuthTestHelper.php';
require_once __DIR__ . '/TestHelpers/TestDataFactory.php';

// Include project configuration files for testing
require_once PROJECT_ROOT . '/config/database.php';
require_once PROJECT_ROOT . '/config/auth.php';

// Clean up any existing test data before starting tests
KidsSmart\Tests\TestHelpers\DatabaseTestHelper::cleanupTestData();

echo "PHPUnit Test Bootstrap Loaded Successfully\n";
