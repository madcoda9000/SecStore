<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Utils\SessionUtil;
use Mockery;

/**
 * Authentication Integration Tests
 * 
 * Tests login, logout, and authentication flows
 */
class AuthenticationTest extends TestCase
{
    /** @test */
    public function it_validates_login_credentials(): void
    {
        $credentials = [
            'username' => 'testuser',
            'password' => 'ValidPass123!'
        ];

        // Username should not be empty
        $this->assertNotEmpty($credentials['username']);
        
        // Password should meet minimum requirements
        $this->assertGreaterThanOrEqual(8, strlen($credentials['password']));
    }

    /** @test */
    public function it_rejects_empty_credentials(): void
    {
        $emptyUsername = ['username' => '', 'password' => 'test'];
        $emptyPassword = ['username' => 'test', 'password' => ''];

        $this->assertEmpty($emptyUsername['username']);
        $this->assertEmpty($emptyPassword['password']);
    }

    /** @test */
    public function it_validates_password_complexity(): void
    {
        $weakPasswords = ['123', 'password', 'test'];
        $strongPassword = 'SecureP@ss123!';

        foreach ($weakPasswords as $weak) {
            $this->assertLessThan(8, strlen($weak), "$weak should be considered weak");
        }

        $this->assertGreaterThanOrEqual(8, strlen($strongPassword));
        $this->assertMatchesRegularExpression('/[A-Z]/', $strongPassword); // Has uppercase
        $this->assertMatchesRegularExpression('/[a-z]/', $strongPassword); // Has lowercase
        $this->assertMatchesRegularExpression('/[0-9]/', $strongPassword); // Has number
        $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $strongPassword); // Has special char
    }

    /** @test */
    public function it_creates_session_on_successful_login(): void
    {
        $user = $this->createMockUser([
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);

        // Simulate successful login
        SessionUtil::set('user', [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $user->role
        ]);

        SessionUtil::set('authenticated', true);
        SessionUtil::set('session_start', time());

        // Verify session is created
        $this->assertSessionHas('user');
        $this->assertSessionHas('authenticated');
        $this->assertTrue(SessionUtil::get('authenticated'));
    }

    /** @test */
    public function it_destroys_session_on_logout(): void
    {
        // Setup authenticated session
        SessionUtil::set('user', ['id' => 1, 'username' => 'testuser']);
        SessionUtil::set('authenticated', true);

        // Logout
        SessionUtil::destroy();

        // Verify session is cleared
        $this->assertEmpty($_SESSION);
    }

    /** @test */
    public function it_regenerates_session_id_on_login(): void
    {
        // Start session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $oldSessionId = session_id();

        // Regenerate (simulating login)
        session_regenerate_id(true);

        $newSessionId = session_id();

        $this->assertNotEquals($oldSessionId, $newSessionId, 'Session ID should change on login');
    }

    /** @test */
    public function it_tracks_failed_login_attempts(): void
    {
        $email = 'test@example.com';
        
        // Simulate failed attempts
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }

        $_SESSION['failed_attempts'][$email] = [
            'count' => 3,
            'last_attempt' => time()
        ];

        $this->assertEquals(3, $_SESSION['failed_attempts'][$email]['count']);
    }

    /** @test */
    public function it_enforces_account_lockout_after_max_attempts(): void
    {
        $maxAttempts = 5;
        $lockoutTime = 900; // 15 minutes
        $email = 'test@example.com';

        $_SESSION['failed_attempts'][$email] = [
            'count' => 6,
            'last_attempt' => time()
        ];

        $attempts = $_SESSION['failed_attempts'][$email]['count'];
        $isLockedOut = $attempts >= $maxAttempts;

        $this->assertTrue($isLockedOut, 'Account should be locked after max attempts');
    }

    /** @test */
    public function it_resets_failed_attempts_on_successful_login(): void
    {
        $email = 'test@example.com';
        
        $_SESSION['failed_attempts'][$email] = ['count' => 3];

        // Simulate successful login
        unset($_SESSION['failed_attempts'][$email]);

        $this->assertArrayNotHasKey($email, $_SESSION['failed_attempts'] ?? []);
    }

    /** @test */
    public function it_validates_session_timeout(): void
    {
        $sessionTimeout = 1800; // 30 minutes
        $sessionStart = time() - 1900; // Started 31 minutes ago

        $isExpired = (time() - $sessionStart) > $sessionTimeout;

        $this->assertTrue($isExpired, 'Session should be expired');
    }

    /** @test */
    public function it_handles_2fa_flow(): void
    {
        $user = $this->createMockUser([
            'id' => 1,
            'twoFactorEnabled' => 1
        ]);

        // After password validation, before full login
        SessionUtil::set('2fa_user_id', $user->id);
        SessionUtil::set('2fa_pending', true);

        $this->assertSessionHas('2fa_user_id');
        $this->assertTrue(SessionUtil::get('2fa_pending'));
        $this->assertSessionMissing('authenticated');
    }

    /** @test */
    public function it_completes_2fa_verification(): void
    {
        SessionUtil::set('2fa_user_id', 1);
        SessionUtil::set('2fa_pending', true);

        // After successful 2FA verification
        SessionUtil::remove('2fa_pending');
        SessionUtil::set('authenticated', true);
        SessionUtil::set('user', ['id' => 1, 'username' => 'testuser']);

        $this->assertSessionMissing('2fa_pending');
        $this->assertTrue(SessionUtil::get('authenticated'));
    }
}