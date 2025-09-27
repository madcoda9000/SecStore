<?php
namespace App\Utils;

use ORM;
use App\Utils\LogUtil;
use App\Utils\LogType;
use Exception;

/**
 * Geo-Location Analysis für Login-Tracking
 * Phase 1: Länder-basierte Analyse und Anomalie-Detection
 */
class GeoLocationAnalyzer 
{
    // Cache für IP-Lookups (Session-basiert)
    private static array $ipCache = [];
    
    // Kostenlose GeoIP API (keine API-Key erforderlich)
    private const GEO_API_URL = 'http://ip-api.com/json/';
    
    /**
     * Geo-Location Daten für Login-Analytics
     */
    public static function getLoginGeoData(int $days = 30): array {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Alle Logins mit IP-Adressen holen
        $logins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%SUCCESS%')
            ->where_gte('datum_zeit', $startDate)
            ->select_many('ip_address', 'user', 'datum_zeit', 'message')
            ->find_array();
        
        $geoData = [
            'countries' => [],
            'cities' => [],
            'suspicious_logins' => [],
            'new_countries' => [],
            'vpn_detections' => [],
            'total_logins' => count($logins),
            'analysis_period' => $days
        ];
        
        foreach ($logins as $login) {
            $ip = $login['ip_address'];
            $user = $login['user'];
            $timestamp = $login['datum_zeit'];
            
            // Skip private/local IPs
            if (self::isPrivateIP($ip)) {
                continue;
            }
            
            // Geo-Lookup
            $geoInfo = self::getGeoInfo($ip);
            if (!$geoInfo) {
                continue;
            }
            
            $country = $geoInfo['country'] ?? 'Unknown';
            $city = $geoInfo['city'] ?? 'Unknown';
            $countryCode = $geoInfo['countryCode'] ?? 'XX';
            
            // Country Statistics
            if (!isset($geoData['countries'][$country])) {
                $geoData['countries'][$country] = [
                    'country' => $country,
                    'country_code' => $countryCode,
                    'login_count' => 0,
                    'users' => [],
                    'first_seen' => $timestamp,
                    'last_seen' => $timestamp,
                    'cities' => []
                ];
            }
            
            $geoData['countries'][$country]['login_count']++;
            $geoData['countries'][$country]['last_seen'] = $timestamp;
            
            // User tracking per country
            if (!in_array($user, $geoData['countries'][$country]['users'])) {
                $geoData['countries'][$country]['users'][] = $user;
            }
            
            // City tracking
            if (!in_array($city, $geoData['countries'][$country]['cities'])) {
                $geoData['countries'][$country]['cities'][] = $city;
            }
            
            // City Statistics
            $cityKey = $country . '_' . $city;
            if (!isset($geoData['cities'][$cityKey])) {
                $geoData['cities'][$cityKey] = [
                    'country' => $country,
                    'city' => $city,
                    'login_count' => 0,
                    'users' => []
                ];
            }
            $geoData['cities'][$cityKey]['login_count']++;
            
            // Suspicious Login Detection
            $suspiciousReasons = self::detectSuspiciousLogin($geoInfo, $user, $timestamp);
            if (!empty($suspiciousReasons)) {
                $geoData['suspicious_logins'][] = [
                    'ip' => $ip,
                    'user' => $user,
                    'timestamp' => $timestamp,
                    'country' => $country,
                    'city' => $city,
                    'reasons' => $suspiciousReasons,
                    'geo_info' => $geoInfo
                ];
            }
            
            // VPN/Proxy Detection (wenn API es unterstützt)
            if (isset($geoInfo['proxy']) && $geoInfo['proxy']) {
                $geoData['vpn_detections'][] = [
                    'ip' => $ip,
                    'user' => $user,
                    'timestamp' => $timestamp,
                    'country' => $country,
                    'isp' => $geoInfo['isp'] ?? 'Unknown'
                ];
            }
        }
        
        // Sort countries by login count
        uasort($geoData['countries'], function($a, $b) {
            return $b['login_count'] <=> $a['login_count'];
        });
        
        // Detect new countries (first time seen)
        $geoData['new_countries'] = self::detectNewCountries($geoData['countries'], $days);
        
        return $geoData;
    }
    
    /**
     * User-spezifische Geo-Analyse
     */
    public static function getUserGeoProfile(string $username, int $days = 90): array {
        $startDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $userLogins = ORM::for_table('logs')
            ->where('type', 'AUDIT')
            ->where_like('context', '%login%')
            ->where_like('message', '%SUCCESS%')
            ->where('user', $username)
            ->where_gte('datum_zeit', $startDate)
            ->select_many('ip_address', 'datum_zeit')
            ->find_array();
        
        $profile = [
            'username' => $username,
            'countries' => [],
            'usual_countries' => [],
            'recent_new_countries' => [],
            'risk_score' => 0,
            'total_logins' => count($userLogins),
            'analysis_period' => $days
        ];
        
        foreach ($userLogins as $login) {
            $ip = $login['ip_address'];
            $timestamp = $login['datum_zeit'];
            
            if (self::isPrivateIP($ip)) continue;
            
            $geoInfo = self::getGeoInfo($ip);
            if (!$geoInfo) continue;
            
            $country = $geoInfo['country'] ?? 'Unknown';
            
            if (!isset($profile['countries'][$country])) {
                $profile['countries'][$country] = [
                    'country' => $country,
                    'login_count' => 0,
                    'first_seen' => $timestamp,
                    'last_seen' => $timestamp,
                    'ips' => []
                ];
            }
            
            $profile['countries'][$country]['login_count']++;
            $profile['countries'][$country]['last_seen'] = $timestamp;
            
            if (!in_array($ip, $profile['countries'][$country]['ips'])) {
                $profile['countries'][$country]['ips'][] = $ip;
            }
        }
        
        // Usual countries (>20% of logins)
        $totalLogins = count($userLogins);
        foreach ($profile['countries'] as $country => $data) {
            $percentage = ($data['login_count'] / $totalLogins) * 100;
            if ($percentage >= 20) {
                $profile['usual_countries'][] = $country;
            }
        }
        
        // Recent new countries (last 7 days)
        $recentDate = date('Y-m-d H:i:s', strtotime('-7 days'));
        foreach ($profile['countries'] as $country => $data) {
            if ($data['first_seen'] >= $recentDate && !in_array($country, $profile['usual_countries'])) {
                $profile['recent_new_countries'][] = [
                    'country' => $country,
                    'first_seen' => $data['first_seen'],
                    'login_count' => $data['login_count']
                ];
            }
        }
        
        // Risk Score Calculation
        $profile['risk_score'] = self::calculateGeoRiskScore($profile);
        
        return $profile;
    }
    
    /**
     * Geo-Location für eine IP-Adresse
     */
    private static function getGeoInfo(string $ip): ?array {
        // Cache prüfen
        if (isset(self::$ipCache[$ip])) {
            return self::$ipCache[$ip];
        }
        
        try {
            // API Request mit Timeout
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3, // 3 Sekunden Timeout
                    'user_agent' => 'SecStore/1.3.0'
                ]
            ]);
            
            $response = @file_get_contents(self::GEO_API_URL . $ip . '?fields=status,country,countryCode,city,timezone,isp,proxy', false, $context);
            
            if ($response === false) {
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (!$data || $data['status'] !== 'success') {
                return null;
            }
            
            // Cache speichern
            self::$ipCache[$ip] = $data;
            
            return $data;
            
        } catch (Exception $e) {
            LogUtil::logAction(LogType::ERROR, 'GeoLocationAnalyzer', 'getGeoInfo', 
                "Geo-lookup failed for IP {$ip}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verdächtige Login-Patterns erkennen
     */
    private static function detectSuspiciousLogin(array $geoInfo, string $user, string $timestamp): array {
        $reasons = [];
        
        // 1. Ungewöhnliche Länder für diesen User
        $userProfile = self::getUserGeoProfile($user, 90);
        $country = $geoInfo['country'] ?? 'Unknown';
        
        if (!in_array($country, $userProfile['usual_countries']) && count($userProfile['usual_countries']) > 0) {
            $reasons[] = 'login_from_unusual_country';
        }
        
        // 2. Zeitzone-Anomalien
        $timezone = $geoInfo['timezone'] ?? null;
        if ($timezone) {
            $localHour = (int)date('H', strtotime($timestamp));
            $timezoneOffset = self::getTimezoneOffset($timezone);
            $remoteHour = ($localHour + $timezoneOffset) % 24;
            
            // Login um 3-6 AM in Remote-Timezone ist verdächtig
            if ($remoteHour >= 3 && $remoteHour <= 6) {
                $reasons[] = 'unusual_timezone_activity';
            }
        }
        
        // 3. High-Risk Länder (kann konfiguriert werden)
        $highRiskCountries = ['Unknown', 'North Korea', 'Iran']; // Beispiel
        if (in_array($country, $highRiskCountries)) {
            $reasons[] = 'high_risk_country';
        }
        
        // 4. Proxy/VPN Detection
        if (isset($geoInfo['proxy']) && $geoInfo['proxy']) {
            $reasons[] = 'vpn_or_proxy_detected';
        }
        
        return $reasons;
    }
    
    /**
     * Neue Länder in der Analyse-Periode erkennen
     */
    private static function detectNewCountries(array $countries, int $days): array {
        $newCountries = [];
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        foreach ($countries as $country => $data) {
            if ($data['first_seen'] >= $cutoffDate) {
                $newCountries[] = [
                    'country' => $country,
                    'first_seen' => $data['first_seen'],
                    'login_count' => $data['login_count'],
                    'users' => count($data['users'])
                ];
            }
        }
        
        return $newCountries;
    }
    
    /**
     * Geo-Risk Score für User berechnen
     */
    private static function calculateGeoRiskScore(array $profile): int {
        $score = 0;
        
        // Viele verschiedene Länder = höheres Risiko
        $countryCount = count($profile['countries']);
        if ($countryCount > 5) $score += 20;
        elseif ($countryCount > 3) $score += 10;
        
        // Neue Länder in letzten 7 Tagen
        $score += count($profile['recent_new_countries']) * 15;
        
        // Wenige übliche Länder = höheres Risiko
        if (count($profile['usual_countries']) === 0) $score += 30;
        
        return min($score, 100);
    }
    
    /**
     * Private IP-Adressen prüfen
     */
    private static function isPrivateIP(string $ip): bool {
        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
    
    /**
     * Timezone Offset berechnen (vereinfacht)
     */
    private static function getTimezoneOffset(string $timezone): int {
        try {
            $tz = new \DateTimeZone($timezone);
            $utc = new \DateTimeZone('UTC');
            $datetime = new \DateTime('now', $utc);
            return $tz->getOffset($datetime) / 3600; // In Stunden
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Top Login-Länder für Dashboard
     */
    public static function getTopLoginCountries(int $limit = 10, int $days = 30): array {
        $geoData = self::getLoginGeoData($days);
        $topCountries = array_slice($geoData['countries'], 0, $limit, true);
        
        return [
            'countries' => $topCountries,
            'total_countries' => count($geoData['countries']),
            'suspicious_count' => count($geoData['suspicious_logins']),
            'vpn_count' => count($geoData['vpn_detections'])
        ];
    }
}