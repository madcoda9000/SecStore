<?php

namespace App\Utils;

use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Class Name: LdapUtil
 *
 * Hilfsklasse zur implementierung von ldap methoden.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-04-24
 *
 * Änderungen:
 * - 1.0 (2025-04-24): Erstellt.
 */
class LdapUtil
{
    // Konfiguration (du kannst das auch aus einer .env oder config-Datei laden)
    private static string $ldapHost = 'ldaps://ldap.example.com'; // oder 'ldap://...'
    private static int $ldapPort = 636; // 636 für LDAPS, 389 für unverschlüsselt
    private static string $domainPrefix = 'DOMAIN\\'; // z. B. "CORP\\", kann leer bleiben
    private static int $timeout = 5; // Sekunden Timeout

    /**
     * Setzt die Konfiguration für den LDAP-Server.
     *
     * @param string $host Hostname des LDAP-Servers
     * @param int $port Port des LDAP-Servers
     * @param string $domainPrefix Domain-Präfix für den Benutzernamen
     * @param int $timeout Timeout in Sekunden
     */
    public static function loadConfig()
    {
        $config = include __DIR__ . '/../../config.php'; // Konfigurationsdatei einbinden
        
        // LDAP Host laden (mit Fallback auf Default)
        $host = $config['ldapSettings']['ldapHost'] ?? 'ldaps://ldap.example.com';
        self::$ldapHost = !empty($host) ? $host : 'ldaps://ldap.example.com';
        
        // LDAP Port laden und validieren
        $port = $config['ldapSettings']['ldapPort'] ?? 636;
        $port = (int)$port;
        // Wenn Port ungültig (0 oder außerhalb Range), nutze Default 636
        self::$ldapPort = ($port > 0 && $port <= 65535) ? $port : 636;
        
        // Domain Prefix laden
        $domainPrefix = $config['ldapSettings']['domainPrefix'] ?? '';
        self::$domainPrefix = is_string($domainPrefix) ? $domainPrefix : '';
    }

    /**
     * Authentifiziert einen Benutzer über LDAP.
     *
     * @param string $username z. B. "jdoe"
     * @param string $password Benutzerpasswort
     * @return bool true, wenn Login erfolgreich
     */
    public static function authenticate(string $username, string $password): bool
    {
        self::loadConfig(); // Konfiguration laden

        // Überprüfen, ob die Eingaben leer sind
        if (empty($username) || empty($password)) {
            return false;
        }

        $ldapConnection = @ldap_connect(self::$ldapHost, self::$ldapPort);

        if (!$ldapConnection) {
            LogUtil::logAction(LogType::ERROR, 'LdapUtil', 'authenticate', 'FAILED: ' . $username . ': LDAP connect failed.', $username);
            return false;
        } else {
            LogUtil::logAction(LogType::AUDIT, 'LdapUtil', 'authenticate', 'SUCCESS: ' . $username . ': LDAP connect succeeded.', $username);
        }

        ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldapConnection, LDAP_OPT_NETWORK_TIMEOUT, self::$timeout);

        // User-DN zusammenbauen
        $bindRdn = self::$domainPrefix . $username;

        // Versuchen zu binden (authentifizieren)
        $bind = ldap_bind($ldapConnection, $bindRdn, $password);

        if (!$bind) {
            LogUtil::logAction(LogType::AUDIT, 'LdapUtil', 'authenticate', 'FAILED: ' . $username . ': LDAP bind failed.', $username);
            return false;
        } else {
            LogUtil::logAction(LogType::AUDIT, 'LdapUtil', 'authenticate', 'SUCCESS: ' . $username . ': LDAP bind succeeded.', $username);
        }

        ldap_unbind($ldapConnection);
        return true;
    }

    // Optional: Methode um später Benutzerinformationen zu holen
    // public static function getUserInfo($username) { ... }
}
