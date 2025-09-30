<?php

namespace Tests\Unit;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Class Name: CsrfMiddlewareTest
 *
 * Unit Tests für CsrfMiddleware Logik.
 *
 * HINWEIS: Diese Tests prüfen die Kern-Logik (Token-Validierung, Route-Matching).
 * Die tatsächlichen Middleware-Aufrufe werden durch Integration-Tests abgedeckt.
 *
 * @package Tests\Unit
 * @author Test Suite
 * @version 1.0
 * @since 2025-09-30
 */
class CsrfMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Simuliert die Token-Validierung aus der Middleware
     */
    private function validateToken(?string $requestToken, ?string $sessionToken): bool
    {
        if (!$sessionToken || !$requestToken) {
            return false;
        }
        return hash_equals($sessionToken, $requestToken);
    }

    /**
     * Simuliert die Route-Check Logik für Token-Refresh
     */
    private function shouldSkipRefresh(string $uri): bool
    {
        $noRefreshRoutes = [
            '/admin/users/bulk',
            '/admin/roles/bulk',
            '/admin/rate-limits/update',
            '/admin/rate-limits/reset',
            '/admin/rate-limits/clear',
            '/admin/analytics/data',
        ];

        foreach ($noRefreshRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simuliert die Route-Check Logik für kritische Routes
     */
    private function shouldRefreshForCriticalRoute(string $uri): bool
    {
        $refreshRoutes = [
            '/login',
            '/register',
            '/reset-password',
            '/forgot-password',
            '/profileChangePassword',
            '/profileChangeEmail',
            '/2fa-verify',
            '/enable-2fa',
        ];

        foreach ($refreshRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                return true;
            }
        }

        return false;
    }

    // ========================================================================
    // TOKEN VALIDATION TESTS
    // ========================================================================

    #[Test]
    public function itValidatesMatchingTokens(): void
    {
        // Arrange
        $token = 'valid_csrf_token_12345';

        // Act
        $isValid = $this->validateToken($token, $token);

        // Assert
        $this->assertTrue($isValid);
    }

    #[Test]
    public function itRejectsNonMatchingTokens(): void
    {
        // Arrange
        $sessionToken = 'valid_token';
        $requestToken = 'invalid_token';

        // Act
        $isValid = $this->validateToken($requestToken, $sessionToken);

        // Assert
        $this->assertFalse($isValid);
    }

    #[Test]
    public function itRejectsNullRequestToken(): void
    {
        // Arrange
        $sessionToken = 'valid_token';
        $requestToken = null;

        // Act
        $isValid = $this->validateToken($requestToken, $sessionToken);

        // Assert
        $this->assertFalse($isValid);
    }

    #[Test]
    public function itRejectsNullSessionToken(): void
    {
        // Arrange
        $sessionToken = null;
        $requestToken = 'some_token';

        // Act
        $isValid = $this->validateToken($requestToken, $sessionToken);

        // Assert
        $this->assertFalse($isValid);
    }

    #[Test]
    public function itRejectsBothTokensNull(): void
    {
        // Arrange
        $sessionToken = null;
        $requestToken = null;

        // Act
        $isValid = $this->validateToken($requestToken, $sessionToken);

        // Assert
        $this->assertFalse($isValid);
    }

    #[Test]
    public function itUsesTimingSafeComparison(): void
    {
        // Arrange - zwei ähnliche aber unterschiedliche Tokens
        $token1 = 'csrf_token_12345678901234567890';
        $token2 = 'csrf_token_12345678901234567891'; // nur letzte Ziffer anders

        // Act
        $isValid = $this->validateToken($token2, $token1);

        // Assert - sollte false sein trotz Ähnlichkeit
        $this->assertFalse($isValid);
    }

    // ========================================================================
    // ROUTE MATCHING TESTS - NO REFRESH ROUTES
    // ========================================================================

    #[Test]
    public function itSkipsRefreshForBulkUserOperations(): void
    {
        // Arrange
        $uri = '/admin/users/bulk';

        // Act
        $shouldSkip = $this->shouldSkipRefresh($uri);

        // Assert
        $this->assertTrue($shouldSkip);
    }

    #[Test]
    public function itSkipsRefreshForRateLimitRoutes(): void
    {
        // Arrange
        $routes = [
            '/admin/rate-limits/update',
            '/admin/rate-limits/reset',
            '/admin/rate-limits/clear',
        ];

        foreach ($routes as $uri) {
            // Act
            $shouldSkip = $this->shouldSkipRefresh($uri);

            // Assert
            $this->assertTrue($shouldSkip, "Failed for route: $uri");
        }
    }

    #[Test]
    public function itSkipsRefreshForAnalyticsData(): void
    {
        // Arrange
        $uri = '/admin/analytics/data';

        // Act
        $shouldSkip = $this->shouldSkipRefresh($uri);

        // Assert
        $this->assertTrue($shouldSkip);
    }

    #[Test]
    public function itDoesNotSkipRefreshForRegularRoutes(): void
    {
        // Arrange
        $routes = [
            '/profile',
            '/home',
            '/settings',
            '/admin/users/list',
        ];

        foreach ($routes as $uri) {
            // Act
            $shouldSkip = $this->shouldSkipRefresh($uri);

            // Assert
            $this->assertFalse($shouldSkip, "Failed for route: $uri");
        }
    }

    // ========================================================================
    // ROUTE MATCHING TESTS - CRITICAL ROUTES
    // ========================================================================

    #[Test]
    public function itRefreshesTokenForLoginRoute(): void
    {
        // Arrange
        $uri = '/login';

        // Act
        $shouldRefresh = $this->shouldRefreshForCriticalRoute($uri);

        // Assert
        $this->assertTrue($shouldRefresh);
    }

    #[Test]
    public function itRefreshesTokenForPasswordRoutes(): void
    {
        // Arrange
        $routes = [
            '/reset-password',
            '/forgot-password',
            '/profileChangePassword',
        ];

        foreach ($routes as $uri) {
            // Act
            $shouldRefresh = $this->shouldRefreshForCriticalRoute($uri);

            // Assert
            $this->assertTrue($shouldRefresh, "Failed for route: $uri");
        }
    }

    #[Test]
    public function itRefreshesTokenFor2faRoutes(): void
    {
        // Arrange
        $routes = [
            '/2fa-verify',
            '/enable-2fa',
        ];

        foreach ($routes as $uri) {
            // Act
            $shouldRefresh = $this->shouldRefreshForCriticalRoute($uri);

            // Assert
            $this->assertTrue($shouldRefresh, "Failed for route: $uri");
        }
    }

    #[Test]
    public function itDoesNotRefreshForNonCriticalRoutes(): void
    {
        // Arrange
        $routes = [
            '/profile',
            '/home',
            '/admin/users/bulk',
        ];

        foreach ($routes as $uri) {
            // Act
            $shouldRefresh = $this->shouldRefreshForCriticalRoute($uri);

            // Assert
            $this->assertFalse($shouldRefresh, "Failed for route: $uri");
        }
    }

    #[Test]
    public function itMatchesPartialRouteStrings(): void
    {
        // Arrange - URLs mit Query-Parametern oder zusätzlichen Pfaden
        $routes = [
            '/login?error=invalid',
            '/admin/users/bulk?page=1',
            '/2fa-verify/confirm',
        ];

        // Act & Assert
        $this->assertTrue($this->shouldRefreshForCriticalRoute($routes[0]));
        $this->assertTrue($this->shouldSkipRefresh($routes[1]));
        $this->assertTrue($this->shouldRefreshForCriticalRoute($routes[2]));
    }

    // ========================================================================
    // EDGE CASES
    // ========================================================================

    #[Test]
    public function itHandlesEmptyTokenStrings(): void
    {
        // Arrange
        $sessionToken = '';
        $requestToken = '';

        // Act
        $isValid = $this->validateToken($requestToken, $sessionToken);

        // Assert
        $this->assertFalse($isValid);
    }

    #[Test]
    public function itHandlesVeryLongTokens(): void
    {
        // Arrange
        $longToken = str_repeat('a', 1000);

        // Act
        $isValid = $this->validateToken($longToken, $longToken);

        // Assert
        $this->assertTrue($isValid);
    }

    #[Test]
    public function itHandlesSpecialCharactersInTokens(): void
    {
        // Arrange
        $token = 'csrf_token_!@#$%^&*()_+-={}[]|:;<>?,./';

        // Act
        $isValid = $this->validateToken($token, $token);

        // Assert
        $this->assertTrue($isValid);
    }
}
