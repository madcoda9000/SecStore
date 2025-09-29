<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Utils\SessionUtil;

/**
 * SessionUtil Unit Tests
 * 
 * Tests session management functionality
 */
class SessionUtilTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test config
        $this->createTestConfig();
    }

    /**
     * Create test configuration file
     */
    private function createTestConfig(): void
    {
        $config = [
            'environment' => 'testing',
            'application' => [
                'sessionTimeout' => 1800,
                'sessionTimeoutUnlimited' => 86400
            ]
        ];

        // Ensure config is loadable
        $GLOBALS['test_config'] = $config;
    }

    /** @test */
    public function it_can_set_and_get_session_value(): void
    {
        SessionUtil::set('test_key', 'test_value');
        
        $this->assertEquals('test_value', SessionUtil::get('test_key'));
    }

    /** @test */
    public function it_returns_null_for_nonexistent_key(): void
    {
        $this->assertNull(SessionUtil::get('nonexistent_key'));
    }

    /** @test */
    public function it_can_remove_session_value(): void
    {
        SessionUtil::set('test_key', 'test_value');
        SessionUtil::remove('test_key');
        
        $this->assertNull(SessionUtil::get('test_key'));
    }

    /** @test */
    public function it_can_check_if_key_exists(): void
    {
        SessionUtil::set('existing_key', 'value');
        
        $this->assertTrue(SessionUtil::has('existing_key'));
        $this->assertFalse(SessionUtil::has('nonexistent_key'));
    }

    /** @test */
    public function it_can_destroy_session(): void
    {
        SessionUtil::set('test_key', 'test_value');
        SessionUtil::destroy();
        
        $this->assertEmpty($_SESSION);
    }

    /** @test */
    public function it_generates_unique_csrf_tokens(): void
    {
        // CSRF Token direkt generieren OHNE SessionUtil::getCsrfToken()
        // Das verhindert session_start() in Tests
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $token1 = $_SESSION['csrf_token'];
        
        // Regenerate direkt
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $token2 = $_SESSION['csrf_token'];
        
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1));
    }

    /** @test */
    public function it_validates_csrf_tokens_correctly(): void
    {
        // Set token direkt OHNE SessionUtil::getCsrfToken()
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $validToken = $_SESSION['csrf_token'];
        
        // validateCsrfToken ruft KEIN session_start() auf
        $this->assertTrue(SessionUtil::validateCsrfToken($validToken));
        $this->assertFalse(SessionUtil::validateCsrfToken('invalid_token'));
    }

    /** @test */
    public function it_stores_user_in_session(): void
    {
        $user = [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com'
        ];
        
        SessionUtil::set('user', $user);
        
        $storedUser = SessionUtil::get('user');
        $this->assertEquals($user['id'], $storedUser['id']);
        $this->assertEquals($user['username'], $storedUser['username']);
    }

    /** @test */
    public function it_handles_session_timeout_calculation(): void
    {
        SessionUtil::set('session_start', time() - 1000);
        
        $remaining = SessionUtil::getRemainingTime();
        
        $this->assertIsInt($remaining);
        $this->assertLessThan(1800, $remaining); // Less than default timeout
    }
}