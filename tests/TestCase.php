<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery;
use ORM;
use PDO;

/**
 * Base Test Case
 *
 * Provides common functionality for all tests including database setup
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected static bool $databaseSetupDone = false;
    protected static ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup database once per test run
        if (!self::$databaseSetupDone) {
            $this->setUpDatabase();
            self::$databaseSetupDone = true;
        }
        
        // Reset session for each test
        $_SESSION = [];
        
        // Reset POST/GET data
        $_POST = [];
        $_GET = [];
        
        // Mock basic server variables
        $this->mockServerVariables();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Setup SQLite in-memory database for testing
     */
    protected function setUpDatabase(): void
    {
        try {
            // Create PDO connection
            self::$pdo = new PDO('sqlite::memory:', null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Configure ORM (Idiorm) to use the test database
            ORM::configure([
                'connection_string' => 'sqlite::memory:',
                'driver_options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]
            ]);
            
            // Set the PDO instance
            ORM::set_db(self::$pdo);

            // Create database schema
            $this->createDatabaseSchema();
            
        } catch (\Exception $e) {
            $this->fail('Failed to setup test database: ' . $e->getMessage());
        }
    }

    /**
     * Create database tables for testing
     */
    protected function createDatabaseSchema(): void
    {
        // Users table
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                firstname VARCHAR(255) DEFAULT '',
                lastname VARCHAR(255) DEFAULT '',
                email VARCHAR(255) NOT NULL UNIQUE,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                status INTEGER NOT NULL DEFAULT 1,
                roles VARCHAR(255) NOT NULL,
                reset_token VARCHAR(255) DEFAULT '',
                reset_token_expires DATETIME DEFAULT NULL,
                mfaStartSetup INTEGER NOT NULL DEFAULT 0,
                mfaEnabled INTEGER NOT NULL DEFAULT 0,
                mfaEnforced INTEGER NOT NULL DEFAULT 0,
                mfaSecret VARCHAR(2500) NOT NULL DEFAULT '',
                mfaBackupCodes TEXT NULL DEFAULT NULL,
                ldapEnabled INTEGER NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                activeSessionId VARCHAR(255) DEFAULT '',
                lastKnownIp VARCHAR(255) DEFAULT '',
                verification_token VARCHAR(255) DEFAULT NULL
            )
        ");

        // Roles table
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                roleName VARCHAR(255) NOT NULL DEFAULT ''
            )
        ");

        // Insert default roles
        self::$pdo->exec("INSERT INTO roles (roleName) VALUES ('Admin')");
        self::$pdo->exec("INSERT INTO roles (roleName) VALUES ('User')");

        // Failed logins table
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS failed_logins (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address VARCHAR(45) NOT NULL,
                email VARCHAR(255) NOT NULL,
                attempts INTEGER DEFAULT 1,
                last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Logs table
        self::$pdo->exec("
            CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                datum_zeit DATETIME DEFAULT CURRENT_TIMESTAMP,
                type VARCHAR(20) NOT NULL CHECK(type IN ('ERROR','AUDIT','REQUEST','SYSTEM','MAIL','SQL','SECURITY')),
                user VARCHAR(255) NOT NULL,
                context TEXT NOT NULL,
                message TEXT NOT NULL,
                ip_address VARCHAR(45) NOT NULL
            )
        ");
    }

    /**
     * Clean up database between tests if needed
     */
    protected function cleanupDatabase(): void
    {
        if (self::$pdo) {
            self::$pdo->exec("DELETE FROM users");
            self::$pdo->exec("DELETE FROM roles WHERE id > 2"); // Keep Admin and User
            self::$pdo->exec("DELETE FROM failed_logins");
            self::$pdo->exec("DELETE FROM logs");
        }
    }

    /**
     * Mock server variables for testing
     */
    protected function mockServerVariables(array $overrides = []): void
    {
        $defaults = [
            'REQUEST_URI' => '/test',
            'REQUEST_METHOD' => 'GET',
            'HTTP_USER_AGENT' => 'PHPUnit',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80
        ];

        $_SERVER = array_merge($defaults, $overrides);
    }

    /**
     * Set POST data for testing
     */
    protected function withPostData(array $data): self
    {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        return $this;
    }

    /**
     * Set session data for testing
     */
    protected function withSession(array $data): self
    {
        $_SESSION = array_merge($_SESSION, $data);
        return $this;
    }

    /**
     * Assert that an array has specific keys
     */
    protected function assertArrayHasKeys(array $keys, array $array, string $message = ''): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message ?: "Array does not contain key: $key");
        }
    }

    /**
     * Create a mock user for testing
     */
    protected function createMockUser(array $attributes = []): object
    {
        $defaults = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'firstname' => 'Test',
            'lastname' => 'User',
            'roles' => 'User',
            'status' => 1,
            'ldapEnabled' => 0,
            'mfaEnabled' => 0,
            'mfaEnforced' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        return (object) array_merge($defaults, $attributes);
    }

    /**
     * Create a real database user for testing
     */
    protected function createDatabaseUser(array $attributes = []): int
    {
        $defaults = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test' . uniqid() . '@example.com',
            'username' => 'testuser' . uniqid(),
            'password' => password_hash('Test1234!', PASSWORD_DEFAULT),
            'status' => 1,
            'roles' => 'User',
            'ldapEnabled' => 0,
            'mfaEnabled' => 0,
            'mfaEnforced' => 0,
        ];

        $userData = array_merge($defaults, $attributes);

        $columns = implode(', ', array_keys($userData));
        $placeholders = ':' . implode(', :', array_keys($userData));

        $stmt = self::$pdo->prepare("
            INSERT INTO users ($columns) 
            VALUES ($placeholders)
        ");

        $stmt->execute($userData);

        return (int) self::$pdo->lastInsertId();
    }

    /**
     * Get test configuration
     */
    protected function getTestConfig(): array
    {
        return $GLOBALS['test_config'] ?? [];
    }

    /**
     * Assert that session contains specific key
     */
    protected function assertSessionHas(string $key, string $message = ''): void
    {
        $this->assertArrayHasKey($key, $_SESSION, $message ?: "Session does not contain key: $key");
    }

    /**
     * Assert that session does not contain specific key
     */
    protected function assertSessionMissing(string $key, string $message = ''): void
    {
        $this->assertArrayNotHasKey($key, $_SESSION, $message ?: "Session should not contain key: $key");
    }

    /**
     * Dump session for debugging
     */
    protected function dumpSession(): void
    {
        echo "\n=== SESSION DATA ===\n";
        print_r($_SESSION);
        echo "===================\n\n";
    }

    /**
     * Dump database contents for debugging
     */
    protected function dumpDatabase(?string $table = null): void
    {
        echo "\n=== DATABASE DUMP ===\n";
        
        if ($table) {
            $tables = [$table];
        } else {
            $tables = ['users', 'roles', 'failed_logins', 'logs'];
        }

        foreach ($tables as $tableName) {
            echo "\n--- Table: $tableName ---\n";
            try {
                $stmt = self::$pdo->query("SELECT * FROM $tableName");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($rows)) {
                    echo "(empty)\n";
                } else {
                    print_r($rows);
                }
            } catch (\Exception $e) {
                echo "Error: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n====================\n\n";
    }
}