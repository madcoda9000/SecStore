<?php

namespace App\Utils;

use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Class Name: LdapUtil
 *
 * Hilfsklasse zur implementierung von ldap methoden.
 * GESICHERT GEGEN LDAP INJECTION
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 2.0
 * @since 2025-04-24
 *
 * Änderungen:
 * - 2.0 (2025-10-03): LDAP Injection Schutz implementiert
 * - 1.0 (2025-04-24): Erstellt.
 */
class LdapUtil
{
    private static string $ldapHost = 'ldaps://ldap.example.com';
    private static int $ldapPort = 636;
    private static string $domainPrefix = 'DOMAIN\\';
    private static int $timeout = 5;
    
    // Erlaubte Zeichen für Username (anpassen nach Bedarf)
    private const USERNAME_PATTERN = '/^[a-zA-Z0-9._@-]+$/';
    
    /**
     * Setzt die Konfiguration für den LDAP-Server.
     */
    public static function loadConfig(): void
    {
        $config = include __DIR__ . '/../../config.php';
        
        $host = $config['ldapSettings']['ldapHost'] ?? 'ldaps://ldap.example.com';
        self::$ldapHost = !empty($host) ? $host : 'ldaps://ldap.example.com';
        
        $port = $config['ldapSettings']['ldapPort'] ?? 636;
        $port = (int)$port;
        self::$ldapPort = ($port > 0 && $port <= 65535) ? $port : 636;
        
        // WICHTIG: Auch domainPrefix muss escaped werden!
        $domainPrefix = $config['ldapSettings']['domainPrefix'] ?? '';
        self::$domainPrefix = is_string($domainPrefix) ? self::escapeDn($domainPrefix) : '';
    }

    /**
     * Escaped einen Distinguished Name (DN) für LDAP.
     * 
     * @param string $value Der zu escapende Wert
     * @return string Der escapte Wert
     */
    private static function escapeDn(string $value): string
    {
        // LDAP_ESCAPE_DN = 2 (für DN escaping)
        return ldap_escape($value, '', LDAP_ESCAPE_DN);
    }
    
    /**
     * Escaped einen Filter-Wert für LDAP.
     * 
     * @param string $value Der zu escapende Wert
     * @return string Der escapte Wert
     */
    private static function escapeFilter(string $value): string
    {
        // LDAP_ESCAPE_FILTER = 1 (für Filter escaping)
        return ldap_escape($value, '', LDAP_ESCAPE_FILTER);
    }
    
    /**
     * Validiert einen Username gegen erlaubte Zeichen.
     * 
     * @param string $username Der zu validierende Username
     * @return bool true wenn gültig
     */
    private static function validateUsername(string $username): bool
    {
        // Länge prüfen
        if (strlen($username) < 1 || strlen($username) > 255) {
            return false;
        }
        
        // Pattern prüfen (nur erlaubte Zeichen)
        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            LogUtil::logAction(
                LogType::SECURITY, 
                'LdapUtil', 
                'validateUsername', 
                'REJECTED: Username contains invalid characters: ' . $username, 
                $username
            );
            return false;
        }
        
        return true;
    }

    /**
     * Authentifiziert einen Benutzer über LDAP.
     * GESICHERT GEGEN LDAP INJECTION
     *
     * @param string $username z. B. "jdoe"
     * @param string $password Benutzerpasswort
     * @return bool true, wenn Login erfolgreich
     */
    public static function authenticate(string $username, string $password): bool
    {
        self::loadConfig();

        // 1. EINGABEVALIDIERUNG
        if (empty($username) || empty($password)) {
            return false;
        }
        
        // Whitespace entfernen
        $username = trim($username);
        $password = trim($password);
        
        // Erneut auf leer prüfen
        if (empty($username) || empty($password)) {
            return false;
        }
        
        // 2. USERNAME VALIDIERUNG (erlaubte Zeichen)
        if (!self::validateUsername($username)) {
            LogUtil::logAction(
                LogType::SECURITY, 
                'LdapUtil', 
                'authenticate', 
                'FAILED: Invalid username format detected (possible LDAP injection attempt)', 
                $username
            );
            return false;
        }

        // 3. LDAP VERBINDUNG
        $ldapConnection = @ldap_connect(self::$ldapHost, self::$ldapPort);

        if (!$ldapConnection) {
            LogUtil::logAction(
                LogType::ERROR, 
                'LdapUtil', 
                'authenticate', 
                'FAILED: LDAP connect failed.', 
                $username
            );
            return false;
        }
        
        LogUtil::logAction(
            LogType::AUDIT, 
            'LdapUtil', 
            'authenticate', 
            'SUCCESS: LDAP connect succeeded.', 
            $username
        );

        ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, self::$timeout);

        // 4. USER-DN ZUSAMMENBAUEN MIT ESCAPING
        // WICHTIG: ldap_escape() verwenden!
        $escapedUsername = self::escapeDn($username);
        $bindRdn = self::$domainPrefix . $escapedUsername;
        
        LogUtil::logAction(
            LogType::AUDIT, 
            'LdapUtil', 
            'authenticate', 
            'Attempting bind with DN: ' . $bindRdn, 
            $username
        );

        // 5. AUTHENTIFIZIERUNG
        $bind = @ldap_bind($ldapConnection, $bindRdn, $password);

        if (!$bind) {
            $ldapError = ldap_error($ldapConnection);
            LogUtil::logAction(
                LogType::AUDIT, 
                'LdapUtil', 
                'authenticate', 
                'FAILED: LDAP bind failed. Error: ' . $ldapError, 
                $username
            );
            ldap_unbind($ldapConnection);
            return false;
        }
        
        LogUtil::logAction(
            LogType::AUDIT, 
            'LdapUtil', 
            'authenticate', 
            'SUCCESS: LDAP bind succeeded.', 
            $username
        );

        ldap_unbind($ldapConnection);
        return true;
    }
    
    /**
     * Holt Benutzerinformationen aus LDAP (Beispiel für Filter-Escaping).
     * 
     * @param string $username Der Username
     * @param string $baseDn Die Base DN für die Suche
     * @return array|false Die Benutzerinformationen oder false
     */
    public static function getUserInfo(string $username, string $baseDn)
    {
        self::loadConfig();
        
        // Username validieren
        if (!self::validateUsername($username)) {
            return false;
        }
        
        $ldapConnection = @ldap_connect(self::$ldapHost, self::$ldapPort);
        if (!$ldapConnection) {
            return false;
        }
        
        ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        
        // WICHTIG: Filter-Escaping für LDAP-Suchen!
        $escapedUsername = self::escapeFilter($username);
        $filter = "(uid=" . $escapedUsername . ")";
        
        $search = @ldap_search($ldapConnection, $baseDn, $filter);
        
        if (!$search) {
            ldap_unbind($ldapConnection);
            return false;
        }
        
        $entries = ldap_get_entries($ldapConnection, $search);
        ldap_unbind($ldapConnection);
        
        return $entries;
    }
}