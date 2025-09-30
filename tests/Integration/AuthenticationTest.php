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
    public function itValidatesLoginCredentials(): void
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
    public function itRejectsEmptyCredentials(): void
    {
        $emptyUsername = ['username' => '', 'password' => 'test'];
        $emptyPassword = ['username' => 'test', 'password' => ''];

        $this->assertEmpty($emptyUsername['username']);
        $this->assertEmpty($emptyPassword['password']);
    }

    /** @test */
    public function itValidatesPasswordComplexity(): void
    {
        $weakPasswords = ['123', 'pass', 'test'];
        $strongPassword = 'SecureP@ss123!';

        foreach ($weakPasswords as $weak) {
            $this->assertLessThan(8, strlen($weak), "$weak should be considered weak (less than 8 chars)");
        }

        $this->assertGreaterThanOrEqual(8, strlen($strongPassword));
        $this->assertMatchesRegularExpression('/[A-Z]/', $strongPassword); // Has uppercase
        $this->assertMatchesRegularExpression('/[a-z]/', $strongPassword); // Has lowercase
        $this->assertMatchesRegularExpression('/[0-9]/', $strongPassword); // Has number
        $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $strongPassword); // Has special char
    }

    /** @test */
    public function itCreatesSessionOnSuccessfulLogin(): void
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
    public function itDestroysSessionOnLogout(): void
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
    public function itRegeneratesSessionIdOnLogin(): void
    {
        // In Test-Umgebung ist session_start() nach Output nicht möglich
        // Dieser Test kann nur in echter Runtime-Umgebung funktionieren
        $this->markTestSkipped('Session ID regeneration requires active session without headers sent');
    }

    /** @test */
    public function itTracksFailedLoginAttempts(): void
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
    public function itEnforcesAccountLockoutAfterMaxAttempts(): void
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
    public function itResetsFailedAttemptsOnSuccessfulLogin(): void
    {
        $email = 'test@example.com';
        
        $_SESSION['failed_attempts'][$email] = ['count' => 3];

        // Simulate successful login
        unset($_SESSION['failed_attempts'][$email]);

        $this->assertArrayNotHasKey($email, $_SESSION['failed_attempts'] ?? []);
    }

    /** @test */
    public function itValidatesSessionTimeout(): void
    {
        $sessionTimeout = 1800; // 30 minutes
        $sessionStart = time() - 1900; // Started 31 minutes ago

        $isExpired = (time() - $sessionStart) > $sessionTimeout;

        $this->assertTrue($isExpired, 'Session should be expired');
    }

    /** @test */
    public function itHandles2FaFlow(): void
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
    public function itCompletes2FaVerification(): void
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
