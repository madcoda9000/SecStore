<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Middleware\RateLimiter;

/**
 * RateLimiter Unit Tests
 *
 * Tests rate limiting functionality for SecStore
 * Adapted to work with session-based RateLimiter implementation
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limit session data before each test
        unset($_SESSION['rate_limits']);
        
        // Stelle sicher dass $config global verfügbar ist (für routes.php simulation)
        if (!isset($GLOBALS['config'])) {
            $GLOBALS['config'] = [
                'rateLimiting' => [
                    'enabled' => true,
                    'limits' => []
                ]
            ];
        }
        
        // Mock Flight für Latte-Rendering (damit Tests nicht crashen)
        if (!class_exists('\Flight')) {
            eval('class Flight { 
                public static function get($key) { 
                    if ($key === "flight.views.path") return "../app/views";
                    if ($key === "lang") return "en";
                    return null;
                }
            }');
        }
    }

    protected function tearDown(): void
    {
        // Cleanup nach jedem Test
        unset($_SESSION['rate_limits']);
        parent::tearDown();
    }

    /** @test */
    public function itInitializesWithDefaultLimits(): void
    {
        $rateLimiter = new RateLimiter();
        
        // Session sollte initialisiert sein
        $this->assertArrayHasKey('rate_limits', $_SESSION);
        $this->assertIsArray($_SESSION['rate_limits']);
    }

    /** @test */
    public function itAcceptsCustomLimits(): void
    {
        $customLimits = [
            'test' => ['requests' => 3, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($customLimits);
        
        // Prüfe dass custom limits verwendet werden
        $status = $rateLimiter->getStatus('test');
        $this->assertEquals(3, $status['max_requests']);
        $this->assertEquals(60, $status['window_seconds']);
    }

    /** @test */
    public function itAllowsRequestsWithinLimit(): void
    {
        $limits = [
            'test' => ['requests' => 5, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // Erste 5 Requests sollten durchgehen
        for ($i = 1; $i <= 5; $i++) {
            $result = $rateLimiter->checkLimit('test');
            $this->assertTrue($result, "Request $i should be allowed");
        }
    }

    /** @test */
    public function itTracksRequestCountCorrectly(): void
    {
        $limits = [
            'test' => ['requests' => 10, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // 3 Requests machen
        for ($i = 0; $i < 3; $i++) {
            $rateLimiter->checkLimit('test');
        }
        
        $status = $rateLimiter->getStatus('test');
        
        $this->assertEquals(3, $status['current_requests']);
        $this->assertEquals(7, $status['remaining_requests']);
    }

    /** @test */
    public function itReturnsCorrectStatusInformation(): void
    {
        $limits = [
            'test' => ['requests' => 5, 'window' => 300]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // Mache 2 Requests
        $rateLimiter->checkLimit('test');
        $rateLimiter->checkLimit('test');
        
        $status = $rateLimiter->getStatus('test');
        
        // Prüfe Status-Array Struktur
        $this->assertArrayHasKeys([
            'limit_type',
            'max_requests',
            'window_seconds',
            'current_requests',
            'remaining_requests',
            'window_reset'
        ], $status);
        
        // Prüfe Werte
        $this->assertEquals('test', $status['limit_type']);
        $this->assertEquals(5, $status['max_requests']);
        $this->assertEquals(300, $status['window_seconds']);
        $this->assertEquals(2, $status['current_requests']);
        $this->assertEquals(3, $status['remaining_requests']);
    }

    /** @test */
    public function itUsesGlobalLimitAsFallback(): void
    {
        $rateLimiter = new RateLimiter();
        
        // Request mit unbekanntem Type sollte global limit verwenden
        $status = $rateLimiter->getStatus('unknown_type');
        
        // Global limit hat 500 requests/hour
        $this->assertEquals(500, $status['max_requests']);
    }

    /** @test */
    public function itHasCorrectDefaultLimits(): void
    {
        $rateLimiter = new RateLimiter();
        
        // Prüfe verschiedene default limits
        $loginStatus = $rateLimiter->getStatus('login');
        $this->assertEquals(5, $loginStatus['max_requests']);
        $this->assertEquals(300, $loginStatus['window_seconds']);
        
        $registerStatus = $rateLimiter->getStatus('register');
        $this->assertEquals(3, $registerStatus['max_requests']);
        $this->assertEquals(3600, $registerStatus['window_seconds']);
        
        $adminStatus = $rateLimiter->getStatus('admin');
        $this->assertEquals(50, $adminStatus['max_requests']);
        $this->assertEquals(3600, $adminStatus['window_seconds']);
    }

    /** @test */
    public function itStoresRequestsInSession(): void
    {
        $limits = [
            'test' => ['requests' => 5, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        $rateLimiter->checkLimit('test');
        
        // Session sollte rate_limits key haben
        $this->assertArrayHasKey('rate_limits', $_SESSION);
        $this->assertIsArray($_SESSION['rate_limits']);
        
        // Es sollte einen Key für test: geben
        $found = false;
        foreach ($_SESSION['rate_limits'] as $key => $value) {
            if (strpos($key, 'test:') === 0) {
                $found = true;
                $this->assertIsArray($value);
                $this->assertCount(1, $value); // 1 Request
                break;
            }
        }
        
        $this->assertTrue($found, 'Rate limit key should exist in session');
    }

    /** @test */
    public function itMaintainsSeparateLimitsForDifferentTypes(): void
    {
        $limits = [
            'login' => ['requests' => 2, 'window' => 60],
            'api' => ['requests' => 5, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // Login limit ausschöpfen
        $rateLimiter->checkLimit('login');
        $rateLimiter->checkLimit('login');
        
        // API sollte noch funktionieren
        $this->assertTrue($rateLimiter->checkLimit('api'));
        
        $loginStatus = $rateLimiter->getStatus('login');
        $apiStatus = $rateLimiter->getStatus('api');
        
        $this->assertEquals(2, $loginStatus['current_requests']);
        $this->assertEquals(1, $apiStatus['current_requests']);
    }

    /** @test */
    public function itHandlesZeroRequestsCorrectly(): void
    {
        $limits = [
            'test' => ['requests' => 5, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        $status = $rateLimiter->getStatus('test');
        
        $this->assertEquals(0, $status['current_requests']);
        $this->assertEquals(5, $status['remaining_requests']);
    }

    /** @test */
    public function itCalculatesRemainingRequestsCorrectly(): void
    {
        $limits = [
            'test' => ['requests' => 10, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // Verschiedene Anzahl von Requests testen
        $testCases = [
            0 => 10, // 0 requests = 10 remaining
            3 => 7,  // 3 requests = 7 remaining
            7 => 3,  // 7 requests = 3 remaining
            10 => 0  // 10 requests = 0 remaining
        ];
        
        foreach ($testCases as $requests => $expectedRemaining) {
            // Reset session für neuen Test
            unset($_SESSION['rate_limits']);
            $rateLimiter = new RateLimiter($limits);
            
            for ($i = 0; $i < $requests; $i++) {
                $rateLimiter->checkLimit('test');
            }
            
            $status = $rateLimiter->getStatus('test');
            $this->assertEquals(
                $expectedRemaining,
                $status['remaining_requests'],
                "After $requests requests, should have $expectedRemaining remaining"
            );
        }
    }

    /** @test */
    public function itGeneratesConsistentIdentifierForSameClient(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $_SERVER['HTTP_USER_AGENT'] = 'TestBrowser/1.0';
        
        $rateLimiter1 = new RateLimiter();
        $rateLimiter1->checkLimit('test');
        
        $status1 = $rateLimiter1->getStatus('test');
        
        // Neuer RateLimiter mit gleichen Server-Variablen
        $rateLimiter2 = new RateLimiter();
        $status2 = $rateLimiter2->getStatus('test');
        
        // Sollte gleiche Request-Anzahl sehen (weil gleicher Identifier)
        $this->assertEquals($status1['current_requests'], $status2['current_requests']);
    }

    /** @test */
    public function itHandlesMissingUserAgentGracefully(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        
        $rateLimiter = new RateLimiter();
        $result = $rateLimiter->checkLimit('test');
        
        $this->assertTrue($result);
    }

    /** @test */
    public function itProvidesWindowResetTimestamp(): void
    {
        $limits = [
            'test' => ['requests' => 5, 'window' => 300]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        $rateLimiter->checkLimit('test');
        
        $status = $rateLimiter->getStatus('test');
        
        $this->assertArrayHasKey('window_reset', $status);
        $this->assertIsInt($status['window_reset']);
        // Greater than OR EQUAL (kann manchmal exakt gleich sein)
        $this->assertGreaterThanOrEqual(time(), $status['window_reset']);
        $this->assertLessThan(time() + 301, $status['window_reset']);
    }

    /** @test */
    public function itHandlesConcurrentLimitTypes(): void
    {
        $limits = [
            'type_a' => ['requests' => 3, 'window' => 60],
            'type_b' => ['requests' => 5, 'window' => 60],
            'type_c' => ['requests' => 2, 'window' => 60]
        ];
        
        $rateLimiter = new RateLimiter($limits);
        
        // Requests auf verschiedene Types verteilen
        $rateLimiter->checkLimit('type_a');
        $rateLimiter->checkLimit('type_b');
        $rateLimiter->checkLimit('type_a');
        $rateLimiter->checkLimit('type_c');
        $rateLimiter->checkLimit('type_b');
        
        // Prüfe dass jeder Type separat gezählt wird
        $statusA = $rateLimiter->getStatus('type_a');
        $statusB = $rateLimiter->getStatus('type_b');
        $statusC = $rateLimiter->getStatus('type_c');
        
        $this->assertEquals(2, $statusA['current_requests']);
        $this->assertEquals(2, $statusB['current_requests']);
        $this->assertEquals(1, $statusC['current_requests']);
    }
}
