<?php

namespace App\Utils;

use ORM;

/**
 * Class Name: BruteForceUtil
 *
 * Hilfsklasse zur implementierung Bruteforce Schutz
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class BruteForceUtil
{

    private static $maxAttempts;
    private static $lockTime;
    private static $enabled;
    
    /**
     * Laedt die Konfiguration fuer die Brute-Force-Schutzfunktion.
     * Die Konfiguration wird in der Datei config.php erwartet.
     * Es werden die folgenden Einstellungen erwartet:
     * - enableBruteForce: boolean, ob die Schutzfunktion aktiviert ist
     * - maxAttempts: int, maximale Anzahl von Versuchen
     * - lockTime: int, Sperrzeit in Sekunden
     */
    
    public static function loadConfig()
    {
        $config = include __DIR__ . '/../../config.php'; // Konfigurationsdatei einbinden
        self::$maxAttempts = $config['bruteForceSettings']['maxAttempts'] ?? 5;
        self::$lockTime = $config['bruteForceSettings']['lockTime'] ?? 900;
        self::$enabled = $config['bruteForceSettings']['enableBruteForce'] ?? true;
    }
    
    /**
     * Liefert true, wenn die Brute-Force-Schutzfunktion aktiviert ist.
     * @return bool
     */
    public static function isProtectionEnabled()
    {
        return self::$enabled;
    }

    
    /**
     * Zeichnet einen fehlgeschlagenen Login-Versuch auf.
     * @param string $email Email-Adresse des Benutzers
     */
    public static function recordFailedLogin($email)
    {
        self::loadConfig();
        if (!self::isProtectionEnabled()) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'];

        $failed = ORM::for_table('failed_logins')
            ->where('ip_address', $ip)
            ->where('email', $email)
            ->find_one();

        if ($failed) {
            $failed->attempts += 1;
            $failed->save();
        } else {
            $failed = ORM::for_table('failed_logins')->create();
            $failed->ip_address = $ip;
            $failed->email = $email;
            $failed->attempts = 1;
            $failed->save();
        }
    }

    
    /**
     * Prueft, ob ein Benutzer fuer Login-Versuche gesperrt ist.
     * @param string $email Email-Adresse des Benutzers
     * @return bool true, wenn der Benutzer fuer Login-Versuche gesperrt ist, false sonst
     */
    public static function isLockedOut($email)
    {
        self::loadConfig();
        if (!self::isProtectionEnabled()) {
            return false;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        $failed = ORM::for_table('failed_logins')
            ->where('ip_address', $ip)
            ->where('email', $email)
            ->find_one();

        if ($failed && $failed->attempts >= self::$maxAttempts) {
            $timeSinceLastAttempt = time() - strtotime($failed->last_attempt);
            if ($timeSinceLastAttempt < self::$lockTime) {
                return true;
            } else {
                $failed->delete(); // Reset nach Sperrzeit
                return false;
            }
        }
        return false;
    }

    
    /**
     * Resets failed login attempts for a specific user and IP address.
     *
     * @param string $email The email address of the user whose failed login attempts should be reset.
     *
     * This method will delete all records of failed login attempts associated with the given email
     * and the current IP address from the 'failed_logins' table, effectively resetting the count.
     */
    public static function resetFailedLogins($email)
    {
        self::loadConfig();
        if (!self::isProtectionEnabled()) {
            return;
        }

        $ip = $_SERVER['REMOTE_ADDR'];
        ORM::for_table('failed_logins')
            ->where('ip_address', $ip)
            ->where('email', $email)
            ->delete_many();
    }
}

// Konfiguration einmalig laden
BruteForceUtil::loadConfig();
