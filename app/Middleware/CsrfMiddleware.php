<?php
// app/Middleware/CsrfMiddleware.php - VERBESSERTE VERSION

namespace App\Middleware;

use Flight;
use App\Utils\SessionUtil;
use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Class Name: CsrfMiddleware
 *
 * Middlewware Klasse zur Überprüfung ob ein Request ein gültiges CSRF-Token enthält
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 * - 1.1 (2025-09-28): SECURITY FIX - Session-ID Logging entfernt
 */
class CsrfMiddleware
{
    public function before(array $params): void
    {
        if (Flight::request()->method == 'POST') {

            // DEBUG LOGGING
            if ($params != null && $params['debug'] === true && !self::isProduction()) {
                // DEBUG-Modus: Nur unkritische Informationen loggen
                LogUtil::logAction(
                    LogType::SECURITY,
                    'CsrfMiddleware',
                    'before',
                    'Debug CSRF check - Session active: ' . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') .
                        ', Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
                        ', Remote IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
                );
            } else {
                // Production: Minimales sicheres Logging
                LogUtil::logAction(
                    LogType::SECURITY, 
                    'CsrfMiddleware', 
                    'before', 
                    'CSRF validation attempted from: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown')
                );
            }


            // 1. SICHERHEITSPRÜFUNG: Session existiert
            if (session_status() !== PHP_SESSION_ACTIVE) {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 'No active session for CSRF validation', '');
                $this->redirectToLogin('Session expired. Please log in again.');
                return;
            }

            // 2. CSRF TOKEN VALIDIERUNG
            $token = Flight::request()->data->csrf_token ??
                $this->getHeaderToken() ??
                null;

            $sessionToken = SessionUtil::get('csrf_token');

            // 3. DETAILLIERTE FEHLERBEHANDLUNG
            if (!$sessionToken) {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 'No CSRF token in session', '');
                $this->redirectToLogin('Your session has expired. Please log in again.');
                return;
            }

            if (!$token) {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 'No CSRF token provided in request', '');
                Flight::halt(400, 'Missing CSRF token. Please refresh the page and try again.');
                return;
            }

            if (!hash_equals($sessionToken, $token)) {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 'CSRF token mismatch', '');
                Flight::halt(403, 'Invalid CSRF token. Please refresh the page and try again.');
                return;
            }

            // 4. TOKEN ROTATION nach erfolgreicher Validierung
            if ($this->shouldRefreshToken()) {
                SessionUtil::refreshCsrfToken();
            }
        }
    }

    /**
     * Bestimmt ob CSRF Token nach Validierung erneuert werden soll
     * Erneuert NICHT bei Admin-Bulk-Operationen um UX-Probleme zu vermeiden
     */
    private function shouldRefreshToken(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';

        // KEINE Token-Rotation bei diesen Routen (UX-kritisch):
        $noRefreshRoutes = [
            '/admin/users/bulk',           // Bulk User Operations
            '/admin/roles/bulk',           // Falls du das später implementierst
            '/admin/rate-limits/update', // Rate Limit Updates
            '/admin/rate-limits/reset',  // Rate Limit Resets
            '/admin/rate-limits/clear', // Rate Limit Clear All
            '/admin/analytics/data', // Analytics Data Fetching
        ];

        foreach ($noRefreshRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                LogUtil::logAction(
                    LogType::SECURITY,
                    'CsrfMiddleware',
                    'shouldRefreshToken',
                    'Skipping token refresh for route: ' . $uri
                );
                return false;
            }
        }

        // Token-Rotation bei kritischen/einmaligen Operationen:
        $refreshRoutes = [
            '/login',                      // Login
            '/register',                   // Registrierung
            '/reset-password',             // Passwort-Reset
            '/forgot-password',          // Passwort vergessen
            '/profileChangePassword',      // Passwort ändern
            '/profileChangeEmail',         // Email ändern
            '/2fa-verify',                        // 2FA
            '/enable-2fa',                 // 2FA aktivieren
        ];

        foreach ($refreshRoutes as $route) {
            if (strpos($uri, $route) !== false) {
                LogUtil::logAction(
                    LogType::SECURITY,
                    'CsrfMiddleware',
                    'shouldRefreshToken',
                    'Refreshing token for critical route: ' . $uri
                );
                return true;
            }
        }

        // Default: Token.Regenration für bessere Sicherheit. Nur in Ausnahmen no regenerieren
        LogUtil::logAction(
                    LogType::SECURITY,
                    'CsrfMiddleware',
                    'shouldRefreshToken',
                    'Route do not match any filter. Refreshing token for route: ' . $uri);
        return true;
    }

    /**
     * Redirect to login with message
     */
    private function redirectToLogin(string $message): void
    {
        // Session vollständig zerstören
        SessionUtil::destroy();

        // Mit Fehlermeldung zum Login weiterleiten
        Flight::redirect('/login?error=' . urlencode($message));
        exit;
    }

    /**
     * Holt CSRF-Token aus HTTP-Headern
     */
    private function getHeaderToken(): ?string
    {
        $headers = getallheaders();

        return $headers['X-CSRF-Token'] ??
            $headers['X-Csrf-Token'] ??
            $headers['CSRF-Token'] ??
            null;
    }

    /**
     * Prüft ob die Anwendung in der Produktionsumgebung läuft
     */
    private static function isProduction(): bool
    {
        $config = include __DIR__ . '/../../config.php';
        return ($config['environment'] ?? 'production') === 'production';
    }
}
