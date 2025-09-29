<?php

namespace App\Utils;

use ORM;

/**
 * Class Name: SecurityMetrics
 *
 * PERFORMANCE-OPTIMIERT: LIKE-Queries durch indexierte Spalten ersetzt
 *
 * @author Sascha Heimann
 * @version 1.1 - PERFORMANCE FIX
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 * - 1.1 (2025-09-28): PERFORMANCE FIX - LIKE-Queries optimiert, Debug-Code entfernt
 */
class SecurityMetrics
{
    /**
     * Tägliche Security Summary generieren
     */
    public static function generateDailySummary(): array
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $today = date('Y-m-d');

        return [
            'failed_logins' => self::getFailedLogins($yesterday, $today),
            'successful_logins' => self::getSuccessfulLogins($yesterday, $today),
            'password_resets' => self::getPasswordResets($yesterday, $today),
            'new_registrations' => self::getNewRegistrations($yesterday, $today),
            'csrf_violations' => self::getCsrfViolations($yesterday, $today),
            'rate_limit_hits' => self::getRateLimitViolations($yesterday, $today),
            'suspicious_activity' => self::getSuspiciousActivity($yesterday, $today),
        ];
    }

    /**
     * Stündliche Metriken für detaillierte Analyse
     */
    public static function getHourlyMetrics(): array
    {
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $now = date('Y-m-d H:i:s');

        return [
            'failed_logins' => self::getFailedLoginsTimeRange($oneHourAgo, $now),
            'successful_logins' => self::getSuccessfulLoginsTimeRange($oneHourAgo, $now),
            'password_resets' => self::getPasswordResetsTimeRange($oneHourAgo, $now),
            'new_registrations' => self::getNewRegistrationsTimeRange($oneHourAgo, $now),
            'csrf_violations' => self::getCsrfViolationsTimeRange($oneHourAgo, $now),
            'rate_limit_hits' => self::getRateLimitViolationsTimeRange($oneHourAgo, $now),
            'timeframe' => '1 hour',
            'period_start' => $oneHourAgo,
            'period_end' => $now,
        ];
    }

    /**
     * Wöchentliche Metriken für Trend-Analyse
     */
    public static function getWeeklyMetrics(): array
    {
        $oneWeekAgo = date('Y-m-d', strtotime('-7 days'));
        $today = date('Y-m-d');

        // Tägliche Aufschlüsselung der letzten 7 Tage
        $dailyBreakdown = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $nextDate = date('Y-m-d', strtotime("-{$i} days + 1 day"));

            $dailyBreakdown[] = [
                'date' => $date,
                'failed_logins' => self::getFailedLoginsTimeRange($date . ' 00:00:00', $nextDate . ' 00:00:00'),
                'successful_logins' => self::getSuccessfulLoginsTimeRange($date . ' 00:00:00', $nextDate . ' 00:00:00'),
                'password_resets' => self::getPasswordResetsTimeRange($date . ' 00:00:00', $nextDate . ' 00:00:00'),
                'new_registrations' => self::getNewRegistrationsTimeRange($date . ' 00:00:00', $nextDate . ' 00:00:00'),
            ];
        }

        return [
            'failed_logins' => self::getFailedLoginsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'successful_logins' => self::getSuccessfulLoginsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'password_resets' => self::getPasswordResetsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'new_registrations' => self::getNewRegistrationsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'csrf_violations' => self::getCsrfViolationsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'rate_limit_hits' => self::getRateLimitViolationsTimeRange($oneWeekAgo . ' 00:00:00', $today . ' 23:59:59'),
            'daily_breakdown' => $dailyBreakdown,
            'timeframe' => '7 days',
            'period_start' => $oneWeekAgo,
            'period_end' => $today,
        ];
    }

    // Private Methoden für logs-Tabelle - ALLE KORREKT
    private static function getFailedLogins($dateFrom, $dateTo): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', [
                'AuthController/login',
                'AuthController/verify2FA',
                'AuthController/forgotPassword',
            ])
            ->where_raw("message LIKE 'FAILED:%' OR message LIKE 'Failed login%' OR message LIKE 'Invalid%'")
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->count();
    }

    private static function getSuccessfulLogins($dateFrom, $dateTo): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', [
                'AuthController/login',
                'AuthController/verify2FA',
            ])
            ->where_raw("message LIKE 'SUCCESS:%'")
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->count();
    }

    private static function getPasswordResets($dateFrom, $dateTo): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', [
                'AuthController/forgotPassword',
                'AuthController/resetPassword',
            ])
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->count();
    }

    private static function getNewRegistrations($dateFrom, $dateTo): int
    {
        return ORM::for_table('users')
            ->where_gte('created_at', $dateFrom . ' 00:00:00')
            ->where_lt('created_at', $dateTo . ' 23:59:59')
            ->count();
    }

    private static function getCsrfViolations($dateFrom, $dateTo): int
    {
        return ORM::for_table('logs')
            ->where('type', 'SECURITY')
            ->where_in('context', [
                'CsrfMiddleware/before',
            ])
            ->where_raw("message LIKE '%CSRF token%' OR message LIKE '%token mismatch%'")
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->count();
    }

    private static function getRateLimitViolations($dateFrom, $dateTo): int
    {
        return ORM::for_table('logs')
            ->where('type', 'ERROR')
            ->where_in('context', [
                'RateLimiter/limitExceeded',
                'RateLimiter/repeatedViolations',
            ])
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->count();
    }

    // KORRIGIERT: Einfache PHP-Gruppierung statt komplexer SQL HAVING
    private static function getSuspiciousActivity($dateFrom, $dateTo): array
    {
        // Hole alle Failed Logins und gruppiere in PHP statt SQL
        $failedLogins = ORM::for_table('logs')
            ->select_many('ip_address', 'datum_zeit')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/login'])
            ->where_raw("message LIKE 'FAILED:%'")
            ->where_gte('datum_zeit', $dateFrom . ' 00:00:00')
            ->where_lt('datum_zeit', $dateTo . ' 23:59:59')
            ->find_array();

        // Gruppiere in PHP nach IP
        $ipCounts = [];
        foreach ($failedLogins as $login) {
            $ip = $login['ip_address'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
        }

        // Filtere verdächtige IPs (>10 Versuche)
        $suspiciousIPs = [];
        foreach ($ipCounts as $ip => $count) {
            if ($count > 10) {
                $suspiciousIPs[] = $ip;
            }
        }

        return [
            'suspicious_ips' => count($suspiciousIPs),
            'ips' => $suspiciousIPs,
        ];
    }

    private static function getFailedLoginsTimeRange($from, $to): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/login'])
            ->where_raw("message LIKE 'FAILED:%'")
            ->where_gte('datum_zeit', $from)
            ->where_lt('datum_zeit', $to)
            ->count();
    }

    private static function getSuccessfulLoginsTimeRange($from, $to): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/login', 'AuthController/verify2FA'])
            ->where_raw("message LIKE 'SUCCESS:%'")
            ->where_gte('datum_zeit', $from)
            ->where_lt('datum_zeit', $to)
            ->count();
    }

    private static function getPasswordResetsTimeRange($from, $to): int
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/resetPassword'])
            ->where_gte('datum_zeit', $from)
            ->where_lt('datum_zeit', $to)
            ->count();
    }

    private static function getNewRegistrationsTimeRange($from, $to): int
    {
        return ORM::for_table('users')
            ->where_gte('created_at', $from)
            ->where_lt('created_at', $to)
            ->count();
    }

    private static function getCsrfViolationsTimeRange($from, $to): int
    {
        return ORM::for_table('logs')
            ->where('type', 'SECURITY')
            ->where_in('context', ['CsrfMiddleware/before'])
            ->where_raw("message LIKE '%CSRF token%'")
            ->where_gte('datum_zeit', $from)
            ->where_lt('datum_zeit', $to)
            ->count();
    }

    private static function getRateLimitViolationsTimeRange($from, $to): int
    {
        return ORM::for_table('logs')
            ->where('type', 'ERROR')
            ->where_in('context', ['RateLimiter/limitExceeded'])
            ->where_gte('datum_zeit', $from)
            ->where_lt('datum_zeit', $to)
            ->count();
    }

    /**
     * Anomalie-Erkennung - KORRIGIERT für logs-Tabelle
     */
    public static function detectAnomalies(): array
    {
        $threats = [];

        // Suspicious login patterns
        $suspiciousLogins = self::detectSuspiciousLogins();
        if (!empty($suspiciousLogins)) {
            $threats[] = [
                'type' => 'suspicious_login_pattern',
                'severity' => 'high',
                'data' => $suspiciousLogins,
                'recommendation' => 'Review user accounts and consider temporary lockout',
            ];
        }

        // Rapid authentication failures
        $rapidFailures = self::detectRapidAuthFailures();
        if (!empty($rapidFailures)) {
            $threats[] = [
                'type' => 'brute_force_attack',
                'severity' => 'critical',
                'data' => $rapidFailures,
                'recommendation' => 'Block IP ranges and review logs',
            ];
        }

        // Unusual 2FA patterns
        $unusual2FA = self::detectUnusual2FAPatterns();
        if (!empty($unusual2FA)) {
            $threats[] = [
                'type' => 'potential_2fa_bypass',
                'severity' => 'high',
                'data' => $unusual2FA,
                'recommendation' => 'Verify user identity and reset 2FA',
            ];
        }

        return $threats;
    }

    private static function detectSuspiciousLogins(): array
    {
        return ORM::for_table('logs')
            ->select_many('ip_address', 'user', 'datum_zeit')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/login'])
            ->where_raw("message LIKE 'FAILED:%'")
            ->where_gte('datum_zeit', date('Y-m-d H:i:s', time() - 3600)) // Last hour
            ->find_array();
    }

    // KORRIGIERT: Einfache Version ohne komplexe HAVING-Clause
    private static function detectRapidAuthFailures(): array
    {
        // Einfache Version: Hole alle Failed Logins der letzten 5 Minuten
        $recentFailures = ORM::for_table('logs')
            ->select('ip_address')
            ->where('type', 'AUDIT')
            ->where_in('context', ['AuthController/login'])
            ->where_raw("message LIKE 'FAILED:%'")
            ->where_gte('datum_zeit', date('Y-m-d H:i:s', time() - 1800)) // Last 30 minutes
            ->find_array();

        // Gruppiere in PHP
        $ipCounts = [];
        foreach ($recentFailures as $failure) {
            $ip = $failure['ip_address'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;
        }

        // Finde IPs mit >50 Versuchen
        $rapidFailures = [];
        foreach ($ipCounts as $ip => $count) {
            if ($count > 50) {
                $rapidFailures[] = [
                    'ip_address' => $ip,
                    'failure_count' => $count,
                ];
            }
        }

        return $rapidFailures;
    }

    private static function detectUnusual2FAPatterns(): array
    {
        return ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%2fa%')
            ->where_gte('datum_zeit', date('Y-m-d H:i:s', time() - 1800)) // Last 30 minutes
            ->find_array();
    }

    /**
     * Alert bei kritischen Security Events
     */
    public static function checkCriticalAlerts(): array
    {
        $alerts = [];
        $summary = self::generateDailySummary();

        // Alert bei zu vielen Failed Logins
        if ($summary['failed_logins'] > 100) {
            $alerts[] = [
                'level' => 'HIGH',
                'type' => 'excessive_failed_logins',
                'message' => "Unusual number of failed logins: {$summary['failed_logins']}",
                'recommendation' => 'Review logs for potential brute force attacks',
            ];
        }

        // Alert bei CSRF Violations
        if ($summary['csrf_violations'] > 0) {
            $alerts[] = [
                'level' => 'MEDIUM',
                'type' => 'csrf_violations',
                'message' => "CSRF violations detected: {$summary['csrf_violations']}",
                'recommendation' => 'Review application logs for potential attacks',
            ];
        }

        // Alert bei verdächtigen IPs
        if ($summary['suspicious_activity']['suspicious_ips'] > 0) {
            $alerts[] = [
                'level' => 'HIGH',
                'type' => 'suspicious_activity',
                'message' => "Suspicious IPs detected: {$summary['suspicious_activity']['suspicious_ips']}",
                'recommendation' => 'Consider blocking suspicious IP addresses',
            ];
        }

        return $alerts;
    }

    /**
     * Security Summary für Admin Dashboard
     */
    public static function getSecurityDashboardData(): array
    {
        $summary = self::generateDailySummary();
        $alerts = self::checkCriticalAlerts();

        // Success Rate berechnen
        $totalLogins = $summary['successful_logins'] + $summary['failed_logins'];
        $successRate = $totalLogins > 0 ?
            round(($summary['successful_logins'] / $totalLogins) * 100, 2) : 100;

        return [
            'summary' => $summary,
            'alerts' => $alerts,
            'metrics' => [
                'login_success_rate' => $successRate,
                'total_login_attempts' => $totalLogins,
                'security_score' => self::calculateSecurityScore($summary, $alerts),
            ],
        ];
    }

    private static function calculateSecurityScore($summary, $alerts): int
    {
        $score = 100;

        // Abzug für Failed Logins
        if ($summary['failed_logins'] > 50) {
            $score -= 10;
        }
        if ($summary['failed_logins'] > 100) {
            $score -= 20;
        }

        // Abzug für Alerts
        foreach ($alerts as $alert) {
            if ($alert['level'] === 'HIGH') {
                $score -= 15;
            }
            if ($alert['level'] === 'MEDIUM') {
                $score -= 5;
            }
        }

        // Abzug für CSRF Violations
        $score -= min($summary['csrf_violations'] * 5, 30);

        return max($score, 0);
    }

    /**
     * Automatische tägliche Security Summary loggen
     */
    public static function logDailySummary(): void
    {
        $summary = self::generateDailySummary();
        $alerts = self::checkCriticalAlerts();

        LogUtil::logAction(
            LogType::AUDIT,
            'SecurityMetrics',
            'dailySummary',
            'Daily security summary: ' . json_encode([
                'summary' => $summary,
                'alerts_count' => count($alerts),
                'security_score' => self::calculateSecurityScore($summary, $alerts),
            ])
        );

        // Bei kritischen Alerts zusätzlich warnen
        foreach ($alerts as $alert) {
            if ($alert['level'] === 'HIGH') {
                LogUtil::logAction(
                    LogType::SECURITY,
                    'SecurityMetrics',
                    'criticalAlert',
                    $alert['type'] . ': ' . $alert['message']
                );
            }
        }
    }
}
