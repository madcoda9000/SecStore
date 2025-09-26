<?php

namespace App\Middleware;

use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\TranslationUtil;
use Exception;
use Flight;

/**
 * Rate Limiter Middleware für SecStore
 * 
 * Features:
 * - Flexible Rate Limits pro Route/IP
 * - Session-basierte Speicherung (keine externe DB nötig)
 * - Sliding Window Algorithmus
 * - Automatische Cleanup alter Einträge
 * - Konfigurierbare Limits
 */
class RateLimiter
{
    private array $limits;
    private string $identifier;

    /**
     * Standard Rate Limits (Requests pro Zeitfenster in Sekunden)
     */
    private array $defaultLimits = [
        // Authentifizierung - sehr restriktiv
        'login' => ['requests' => 5, 'window' => 300], // 5 Versuche in 5 Minuten
        'register' => ['requests' => 3, 'window' => 3600], // 3 Registrierungen pro Stunde
        'forgot-password' => ['requests' => 3, 'window' => 3600], // 3 Password-Resets pro Stunde
        'reset-password' => ['requests' => 5, 'window' => 3600], // 5 Versuche pro Stunde
        '2fa' => ['requests' => 10, 'window' => 300], // 10 2FA Versuche in 5 Minuten       

        // Session Management - moderat restriktiv
        'session-extend' => ['requests' => 20, 'window' => 900], // 20 Verlängerungen in 15 Minuten

        // Admin Bereiche - restriktiv
        'admin' => ['requests' => 50, 'window' => 3600], // 50 Admin-Actions pro Stunde

        // Globales Limit als Fallback
        'global' => ['requests' => 500, 'window' => 3600] // 500 Requests pro Stunde
    ];

    public function __construct(array $customLimits = [])
    {
        // Custom Limits mit Defaults mergen
        $this->limits = array_merge($this->defaultLimits, $customLimits);

        // Eindeutiger Identifier (IP + User Agent Hash für bessere Uniqueness)
        $this->identifier = $this->generateIdentifier();

        // Rate Limit Daten in Session initialisieren
        if (!isset($_SESSION['rate_limits'])) {
            $_SESSION['rate_limits'] = [];
        }
    }

    /**
     * Hauptmethode für Rate Limiting Check
     */
    public function checkLimit(string $limitType = 'global'): bool
    {
        // Cleanup alter Einträge
        $this->cleanup();

        // Limit-Konfiguration holen
        $limit = $this->limits[$limitType] ?? $this->limits['global'];

        // Key für diesen Limit-Type und Identifier
        $key = $limitType . ':' . $this->identifier;

        // Aktuelle Zeit
        $now = time();

        // Request-Historie für diesen Key holen
        $requests = $_SESSION['rate_limits'][$key] ?? [];

        // Requests außerhalb des Zeitfensters entfernen (Sliding Window)
        $windowStart = $now - $limit['window'];
        $requests = array_filter($requests, function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Prüfen ob Limit erreicht
        if (count($requests) >= $limit['requests']) {
            $this->handleLimitExceeded($limitType, $limit, count($requests));
            // HTML Response für Web Requests

            if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'de'])) {
                TranslationUtil::setLang($_GET['lang']);
            }
            TranslationUtil::init();
            $templateVars = [
                'retry_after' => $this->getRetryAfter($limitType),
                'lang' => TranslationUtil::getLang(),
                'title' => TranslationUtil::t('rate_limit.msg1'),
                'rateLimitmsg2' => TranslationUtil::t('rate_limit.msg2'),
                'rateLimitmsg3' => TranslationUtil::t('rate_limit.msg3'),
                'rateLimitmsg4' => TranslationUtil::t('rate_limit.msg4'),
            ];

            //var_dump($templateVars); // Zum Debuggen

            // Latte-Instanz erstellen und konfigurieren
            $latte = new \Latte\Engine;
            $latte->addFunction('trans', fn($s) => TranslationUtil::t($s));
            $latte->setTempDirectory('../cache/');
            $latte->setLoader(new \Latte\Loaders\FileLoader(Flight::get('flight.views.path')));

            $latte->render('rate-limit.latte', $templateVars);
            return false;
        }

        // Request hinzufügen
        $requests[] = $now;
        $_SESSION['rate_limits'][$key] = $requests;

        return true;
    }

    /**
     * Eindeutigen Identifier generieren
     */
    private function generateIdentifier(): string
    {
        $ip = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Hash für Privacy und Konsistenz
        return hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * Client IP ermitteln (auch hinter Proxies)
     */
    private function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Rate Limit überschritten - Logging und Response
     */
    private function handleLimitExceeded(string $limitType, array $limit, int $currentCount): void
    {
        $ip = $this->getClientIp();
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';

        LogUtil::logAction(
            LogType::ERROR,
            'RateLimiter',
            'limitExceeded',
            "Rate limit exceeded for {$limitType}. IP: {$ip}, URI: {$uri}, Count: {$currentCount}/{$limit['requests']}"
        );

        // Optional: Bei wiederholten Überschreitungen härtere Maßnahmen
        $this->handleRepeatedViolations($limitType);
    }

    /**
     * Wiederholte Verstöße handhaben
     */
    private function handleRepeatedViolations(string $limitType): void
    {
        $violationKey = 'violations:' . $this->identifier;

        if (!isset($_SESSION['rate_limits'][$violationKey])) {
            $_SESSION['rate_limits'][$violationKey] = [];
        }

        $violations = &$_SESSION['rate_limits'][$violationKey];
        $violations[] = ['type' => $limitType, 'time' => time()];

        // Violations der letzten Stunde zählen
        $recentViolations = array_filter($violations, function ($violation) {
            return ($violation['time'] > (time() - 3600));
        });

        if (count($recentViolations) >= 10) {
            LogUtil::logAction(
                LogType::ERROR,
                'RateLimiter',
                'repeatedViolations',
                "Repeated rate limit violations detected. IP: {$this->getClientIp()}, Count: " . count($recentViolations)
            );

            // Hier könntest du zusätzliche Maßnahmen ergreifen:
            // - Längere Sperrzeiten
            // - IP temporär blocken
            // - Admin-Benachrichtigung
        }
    }

    /**
     * Retry-After Header berechnen
     */
    private function getRetryAfter(string $limitType): int
    {
        $limit = $this->limits[$limitType] ?? $this->limits['global'];

        // Nächster verfügbarer Slot im Zeitfenster
        return $limit['window'];
    }

    /**
     * Alte Einträge aus Session cleanup
     */
    private function cleanup(): void
    {
        if (!isset($_SESSION['rate_limits'])) {
            return;
        }

        $now = time();
        $maxAge = max(array_column($this->limits, 'window')) + 3600; // Längstes Window + 1 Stunde Buffer

        foreach ($_SESSION['rate_limits'] as $key => &$data) {
            if (is_array($data)) {
                // Request Arrays cleanup
                $data = array_filter($data, function ($item) use ($now, $maxAge) {
                    if (is_array($item)) {
                        // Violation entries
                        return isset($item['time']) && ($item['time'] > ($now - $maxAge));
                    }
                    // Timestamp entries
                    return is_numeric($item) && ($item > ($now - $maxAge));
                });

                // Leere Arrays entfernen
                if (empty($data)) {
                    unset($_SESSION['rate_limits'][$key]);
                }
            }
        }
    }

    /**
     * Aktuelle Rate Limit Informationen abrufen (für Debugging/Monitoring)
     */
    public function getStatus(string $limitType = 'global'): array
    {
        $limit = $this->limits[$limitType] ?? $this->limits['global'];
        $key = $limitType . ':' . $this->identifier;
        $requests = $_SESSION['rate_limits'][$key] ?? [];

        $now = time();
        $windowStart = $now - $limit['window'];
        $activeRequests = array_filter($requests, function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        return [
            'limit_type' => $limitType,
            'max_requests' => $limit['requests'],
            'window_seconds' => $limit['window'],
            'current_requests' => count($activeRequests),
            'remaining_requests' => max(0, $limit['requests'] - count($activeRequests)),
            'window_reset' => $windowStart + $limit['window']
        ];
    }
}
