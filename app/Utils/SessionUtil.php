<?php

namespace App\Utils;

use App\Models\User;

/**
 * Class Name: SessionUtil
 *
 * Erweiterte Hilfs-Klasse zur Verwaltung von Benutzersitzungen.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 2.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 * - 2.0 (2025-09-15): Erweitert um Security-Features und bessere Struktur.
 */
class SessionUtil
{
    /** @var array $config */
    private static $config;

    /** @var array Routes with extended session timeout */
    private static $unlimitedRoutes = [
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/verify-2fa'
    ];

    /**
     * Loads the configuration from the config.php file.
     */
    public static function loadConfig(): void
    {
        if (!self::$config) {
            self::$config = include __DIR__ . '/../../config.php';
        }
    }

    /**
     * Initialize and start session with security settings
     */
    public static function initialize(): void
    {
        self::loadConfig();

        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // Session already started
        }

        $timeout = self::calculateTimeout();

        // Configure session settings
        self::configureSessionSettings($timeout);

        // Start session
        session_start();

        // Initialize session data
        self::initializeSessionData();

        // Validate and handle session security
        self::handleSessionSecurity();
    }

    /**
     * Configure session settings before starting
     */
    private static function configureSessionSettings(int $timeout): void
    {
        // Set garbage collection lifetime
        ini_set('session.gc_maxlifetime', $timeout);
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        // Set cookie parameters
        session_set_cookie_params([
            'lifetime' => $timeout,
            'path' => '/',
            'domain' => '',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }

    /**
     * Calculate appropriate timeout based on current route
     */
    private static function calculateTimeout(): int
    {
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
        $isUnlimited = in_array($currentRoute, self::$unlimitedRoutes);

        return $isUnlimited
            ? (60 * 60 * 24 * 30) // 30 days for auth routes
            : self::getSessionTimeout(); // Normal timeout
    }

    /**
     * Initialize session data and security tokens
     */
    private static function initializeSessionData(): void
    {
        $now = time();

        // Set initial timestamps
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = $now;
        }

        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = $now;
        }

        // Generate CSRF token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Store security fingerprint
        if (!isset($_SESSION['security_fingerprint'])) {
            $_SESSION['security_fingerprint'] = self::generateSecurityFingerprint();
        }
    }

    /**
     * Handle session security validation and maintenance
     */
    private static function handleSessionSecurity(): void
    {
        // Check if session is expired
        if (self::isSessionExpired()) {
            self::destroy();
            return;
        }

        // Validate security fingerprint
        if (!self::validateSecurityFingerprint()) {
            self::destroy();
            return;
        }

        // Regenerate session ID periodically
        if (self::shouldRegenerateId()) {
            self::regenerateId();
        }

        // Update last activity
        $_SESSION['last_activity'] = time();
    }

    /**
     * Check if session has expired
     */
    public static function isSessionExpired(): bool
    {
        if (!isset($_SESSION['last_activity'])) {
            return true;
        }

        $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
        $isUnlimited = in_array($currentRoute, self::$unlimitedRoutes);

        if ($isUnlimited) {
            return false; // No expiration for unlimited routes
        }

        $timeout = self::getSessionTimeout();
        return (time() - $_SESSION['last_activity']) > $timeout;
    }

    /**
     * Generate security fingerprint for session validation
     * WICHTIG: Session-ID wird NICHT in den Fingerprint einbezogen, 
     * da sich diese bei Regeneration ändert!
     */
    private static function generateSecurityFingerprint(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        // Session-ID NICHT einbeziehen - das war das Problem!
        // Session-ID ändert sich bei Regeneration, aber Fingerprint soll gleich bleiben
        return hash('sha256', $userAgent . $acceptLanguage . $remoteAddr);
    }

    /**
     * Validate security fingerprint
     */
    private static function validateSecurityFingerprint(): bool
    {
        if (!isset($_SESSION['security_fingerprint'])) {
            return false;
        }

        $currentFingerprint = self::generateSecurityFingerprint();
        return hash_equals($_SESSION['security_fingerprint'], $currentFingerprint);
    }

    /**
     * Check if session ID should be regenerated
     */
    private static function shouldRegenerateId(): bool
    {
        $lastRegenerated = $_SESSION['last_regenerated'] ?? 0;
        $timeSinceLastRegeneration = time() - $lastRegenerated;

        // Regenerate every 15 minutes instead of 30
        return $timeSinceLastRegeneration > 900; // 15 minutes
    }

    /**
     * Regenerate session ID
     */
    public static function regenerateId(): bool
    {
        $oldSessionId = session_id();

        if (session_regenerate_id(true)) {
            $_SESSION['last_regenerated'] = time();

            // Security Fingerprint MUSS nach Regeneration aktualisiert werden
            $_SESSION['security_fingerprint'] = self::generateSecurityFingerprint();

            LogUtil::logAction(
                LogType::SECURITY,
                'SessionUtil',
                'regenerateId',
                'Session ID regenerated: ' . $oldSessionId . ' -> ' . session_id()
            );

            return true;
        }

        LogUtil::logAction(
            LogType::ERROR,
            'SessionUtil',
            'regenerateId',
            'Failed to regenerate session ID'
        );
        return false;
    }

    /**
     * Regenerate session ID explicitly after login
     */
    public static function regenerateAfterLogin(): bool
    {
        // Explizite Regeneration nach Login
        if (session_regenerate_id(true)) {
            $_SESSION['last_regenerated'] = time();
            $_SESSION['security_fingerprint'] = self::generateSecurityFingerprint();

            // Wichtig: last_activity nach Regeneration setzen
            $_SESSION['last_activity'] = time();

            return true;
        }
        return false;
    }

    /**
     * Get the remaining session time in seconds.
     */
    public static function getRemainingTime(): int
    {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }

        $timeout = self::getSessionTimeout();
        return max(0, ($_SESSION['last_activity'] + $timeout) - time());
    }

    /**
     * Gets the session timeout value.
     */
    public static function getSessionTimeout(): int
    {
        self::loadConfig();
        return self::$config['application']['sessionTimeout'];
    }

    /**
     * Sets a session variable.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a session variable.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a session variable exists.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session variable
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();

            // Delete the session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'domain' => '',
                    'secure' => self::isHttps(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }
        }
    }

    /**
     * Get CSRF token, generate if not exists
     * Verbesserte Version mit automatischer Generierung
     */
    public static function getCsrfToken(): string
    {
        // Session sicherstellen
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
        }

        // Token generieren falls nicht vorhanden
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            self::refreshCsrfToken();
            LogUtil::logAction(
                LogType::SECURITY,
                'SessionUtil',
                'getCsrfToken',
                'Generated new CSRF token'
            );
        }

        return $_SESSION['csrf_token'] ?? '';
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool
    {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Refresh CSRF token
     */
    public static function refreshCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * Check if connection is HTTPS
     */
    private static function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }

    /**
     * Get session info for debugging/monitoring
     */
    public static function getSessionInfo(): array
    {
        return [
            'session_id' => session_id(),
            'created' => $_SESSION['created'] ?? null,
            'last_activity' => $_SESSION['last_activity'] ?? null,
            'last_regenerated' => $_SESSION['last_regenerated'] ?? null,
            'remaining_time' => self::getRemainingTime(),
            'is_expired' => self::isSessionExpired(),
            'fingerprint_valid' => self::validateSecurityFingerprint()
        ];
    }
}
