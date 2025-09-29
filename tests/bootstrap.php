<?php

/**
 * PHPUnit Test Bootstrap
 * 
 * Sets up the testing environment for SecStore
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Load Composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load test configuration
if (file_exists(BASE_PATH . '/tests/config/test_config.php')) {
    $testConfig = require BASE_PATH . '/tests/config/test_config.php';
} else {
    // Fallback test configuration
    $testConfig = [
        'db' => [
            'host' => 'localhost',
            'name' => 'secstore_test',
            'user' => 'test_user',
            'pass' => 'test_password',
            'charset' => 'utf8mb4'
        ],
        'environment' => 'testing'
    ];
}

// Make test config globally available
$GLOBALS['test_config'] = $testConfig;

// Mock session for testing
if (!isset($_SESSION)) {
    $_SESSION = [];
}

// Mock server variables
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/test';
}
if (!isset($_SERVER['REQUEST_METHOD'])) {
    $_SERVER['REQUEST_METHOD'] = 'GET';
}
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Runner';
}
if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
    $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US';
}

// Create test directories if they don't exist
$testDirs = [
    BASE_PATH . '/tests/reports',
    BASE_PATH . '/tests/fixtures',
    BASE_PATH . '/.phpunit.cache'
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

echo "\n✓ Test environment initialized\n";
echo "✓ PHP Version: " . PHP_VERSION . "\n";
echo "✓ Base Path: " . BASE_PATH . "\n\n";