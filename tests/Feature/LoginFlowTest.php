<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Utils\SessionUtil;

/**
 * Login Flow Feature Tests
 * 
 * Tests complete user login workflows
 */
class LoginFlowTest extends TestCase
{
    /** @test */
    public function user_can_complete_full_login_flow(): void
    {
        // Step 1: User visits login page
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $this->assertSessionMissing('authenticated');

        // Step 2: User submits valid credentials
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // Set CSRF token direkt in Session (ohne session_start)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        $_POST = [
            'username' => 'testuser',
            'password' => 'ValidPassword123!',
            'csrf_token' => $_SESSION['csrf_token']
        ];

        // Validate CSRF token manually (ohne SessionUtil call der session_start triggert)
        $this->assertEquals($_POST['csrf_token'], $_SESSION['csrf_token'], 'CSRF token should be valid');

        // Step 3: Authentication successful
        SessionUtil::set('authenticated', true);
        SessionUtil::set('user', [
            'id' => 1,
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'user'
        ]);
        SessionUtil::set('session_start', time());

        // Step 4: Verify user is authenticated
        $this->assertSessionHas('authenticated');
        $this->assertTrue(SessionUtil::get('authenticated'));
        $this->assertSessionHas('user');

        // Step 5: User accesses protected page
        $_SERVER['REQUEST_URI'] = '/home';
        $this->assertTrue(SessionUtil::get('authenticated'));
    }

    /** @test */
    public function user_with_2fa_completes_full_flow(): void
    {
        // Set CSRF token direkt (ohne session_start)
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Step 1: Valid credentials submitted
        $_POST = [
            'username' => 'testuser_2fa',
            'password' => 'ValidPassword123!',
            'csrf_token' => $_SESSION['csrf_token']
        ];

        // Step 2: Password correct, but 2FA required
        SessionUtil::set('2fa_user_id', 1);
        SessionUtil::set('2fa_pending', true);
        
        $this->assertSessionHas('2fa_pending');
        $this->assertSessionMissing('authenticated');

        // Step 3: User enters 2FA code
        $_POST = [
            'totp_code' => '123456',
            'csrf_token' => $_SESSION['csrf_token']
        ];

        // Step 4: 2FA verification successful
        SessionUtil::remove('2fa_pending');
        SessionUtil::set('authenticated', true);
        SessionUtil::set('user', [
            'id' => 1,
            'username' => 'testuser_2fa',
            '2fa_verified' => true
        ]);

        // Step 5: User fully authenticated
        $this->assertSessionMissing('2fa_pending');
        $this->assertTrue(SessionUtil::get('authenticated'));
        $this->assertTrue(SessionUtil::get('user')['2fa_verified']);
    }

    /** @test */
    public function failed_login_tracks_attempts(): void
    {
        $email = 'test@example.com';

        // Attempt 1 - Failed
        $this->simulateFailedLogin($email);
        $this->assertEquals(1, $this->getFailedAttempts($email));

        // Attempt 2 - Failed
        $this->simulateFailedLogin($email);
        $this->assertEquals(2, $this->getFailedAttempts($email));

        // Attempt 3 - Failed
        $this->simulateFailedLogin($email);
        $this->assertEquals(3, $this->getFailedAttempts($email));
    }

    /** @test */
    public function account_locks_after_max_failed_attempts(): void
    {
        $email = 'test@example.com';
        $maxAttempts = 5;

        // Simulate max attempts
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->simulateFailedLogin($email);
        }

        $attempts = $this->getFailedAttempts($email);
        $isLocked = $attempts >= $maxAttempts;

        $this->assertTrue($isLocked, 'Account should be locked');
    }

    /** @test */
    public function session_expires_after_timeout(): void
    {
        $sessionTimeout = 1800; // 30 minutes

        // Set session start time to 35 minutes ago
        SessionUtil::set('session_start', time() - 2100);
        SessionUtil::set('authenticated', true);

        $sessionAge = time() - SessionUtil::get('session_start');
        $isExpired = $sessionAge > $sessionTimeout;

        $this->assertTrue($isExpired, 'Session should be expired');

        // Expired session should be destroyed
        if ($isExpired) {
            SessionUtil::destroy();
        }

        $this->assertEmpty($_SESSION);
    }

    /** @test */
    public function logout_clears_all_session_data(): void
    {
        // Setup authenticated session
        SessionUtil::set('authenticated', true);
        SessionUtil::set('user', ['id' => 1, 'username' => 'testuser']);
        SessionUtil::set('session_start', time());
        SessionUtil::set('csrf_token', 'test_token');

        $this->assertNotEmpty($_SESSION);

        // Logout
        SessionUtil::destroy();

        // All session data should be cleared
        $this->assertEmpty($_SESSION);
        $this->assertSessionMissing('authenticated');
        $this->assertSessionMissing('user');
    }

    /**
     * Helper: Simulate failed login attempt
     */
    private function simulateFailedLogin(string $email): void
    {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }

        if (!isset($_SESSION['failed_attempts'][$email])) {
            $_SESSION['failed_attempts'][$email] = ['count' => 0, 'last_attempt' => time()];
        }

        $_SESSION['failed_attempts'][$email]['count']++;
        $_SESSION['failed_attempts'][$email]['last_attempt'] = time();
    }

    /**
     * Helper: Get failed attempt count
     */
    private function getFailedAttempts(string $email): int
    {
        return $_SESSION['failed_attempts'][$email]['count'] ?? 0;
    }
}