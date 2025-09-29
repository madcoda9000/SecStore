<?php

namespace Tests;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Mockery;

/**
 * Base Test Case
 * 
 * Provides common functionality for all tests
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
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
            'firstName' => 'Test',
            'lastName' => 'User',
            'role' => 'user',
            'active' => 1,
            'ldapEnabled' => 0,
            'twoFactorEnabled' => 0,
            'twoFactorEnforced' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return (object) array_merge($defaults, $attributes);
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
}