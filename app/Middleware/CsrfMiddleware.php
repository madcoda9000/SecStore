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
 */
class CsrfMiddleware
{
    public function before(array $params): void
    {
        if (Flight::request()->method == 'POST') {

            // DEBUG LOGGING
            if($params != null && $params['debug'] != null && $params['debug'] === true) {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 
                'Session Status: ' . session_status() . 
                ', Session ID: ' . session_id() .
                ', Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'unknown') .
                ', User Agent: ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
            } else {
                LogUtil::logAction(LogType::SECURITY, 'CsrfMiddleware', 'before', 'CSRF validation attempted from: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
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
            SessionUtil::refreshCsrfToken();
        }
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
}