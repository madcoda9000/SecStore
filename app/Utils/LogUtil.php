<?php

namespace App\Utils;

use ORM;
use App\Utils\SessionUtil;
use App\Utils\LogType;

/**
 * Class Name: LogUtil
 *
 * Hilfsklasse zur implementierung Logging Funktionalitäten
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 * - 1.1 (2025-09-28): SECURITY FIX - File-Permissions korrigiert (0775 → 0750)
 */
class LogUtil
{
    private static $config;

    /**
     * Loads the configuration settings from config.php file.
     *
     * This method is designed to be lazy-loaded, meaning it will only load
     * the configuration the first time it is invoked. Successive calls will
     * return the pre-loaded configuration to avoid redundant file operations.
     */
    public static function loadConfig()
    {
        if (!self::$config) {
            self::$config = include __DIR__ . '/../../config.php';
        }
    }

    /**
     * Writes a log entry into the database.
     *
     * @param LogType $type  The type of the log entry (e.g. 'Error', 'Audit', 'Request', 'System', 'Mail')
     * @param string $file    The file of the log entry (e.g. 'LoginController', 'RegisterController', 'ForgotPasswordController')
     * @param string $method  The method of the log entry (e.g. 'login', 'register', 'forgotPassword')
     * @param string $message  The message of the log entry
     * @param string|null $user  The user that os throwing that event
     */
    public static function logAction($type, $file, $method, $message, $user = null)
    {
        self::loadConfig();
        if (self::$config['logging']['enableSqlLogging'] === true && $type == LogType::SQL) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif (self::$config['logging']['enableMailLogging'] === true && $type == LogType::MAIL) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif (self::$config['logging']['enableSystemLogging'] === true && $type == LogType::SYSTEM) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif (self::$config['logging']['enableAuditLogging'] === true && $type == LogType::AUDIT) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif (self::$config['logging']['enableRequestLogging'] === true && $type == LogType::REQUEST) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif (self::$config['logging']['enableSecurityLogging'] === true && $type == LogType::SECURITY) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif ($type == LogType::ERROR) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        } elseif ($type == LogType::MAILSCHEDULER) {
            $context = "$file/$method";
            self::log($type, $context, $message);
        }
        ORM::configure('logging', false);
    }

    /**
     * Writes a log entry into the database.
     *
     * @param LogType $type  The type of the log entry (e.g. 'Error', 'Audit', 'Request', 'System')
     * @param string $context  The context of the log entry (e.g. 'Login', 'Register', 'Forgot Password')
     * @param string $message  The message of the log entry
     */
    public static function log(LogType $type, string $context, string $message): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // Antwort an den Client senden, aber das Skript läuft weiter
        }

        try {
            // Nutzer ermitteln (Standard: anonymous)
            $user = SessionUtil::get('user')['username'] ?? 'anonymous';

            // IP-Adresse abrufen
            $ipAddress = self::getIpAddress();

            // Log in die Datenbank schreiben
            $logEntry = ORM::for_table('logs')->create();
            $logEntry->type = $type->value;
            $logEntry->user = $user;
            $logEntry->context = $context;
            $logEntry->message = $message;
            $logEntry->datum_zeit = date('Y-m-d H:i:s');
            $logEntry->ip_address = $ipAddress;
            $logEntry->save();
        } catch (\Exception $e) {
            // Fallback-Logging in eine Datei
            self::writeToFile("[ERROR] " . $e->getMessage(), "error.log");
        }
    }

    /**
     * Schreibt eine Lognachricht in eine Datei unter app/logs/.
     *
     * @param string $message Die zu speichernde Nachricht
     * @param string $filename Name der Datei (z. B. 'error.log')
     */
    private static function writeToFile(string $message, string $filename = "error.log"): void
    {
        $logDir = __DIR__ . '/../logs/'; // Pfad zu /app/logs/
        if (!is_dir($logDir)) {
            mkdir($logDir, 0750, true); // Erstellt den Ordner, falls nicht vorhanden
        }

        $filePath = $logDir . $filename;
        $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . PHP_EOL;

        file_put_contents($filePath, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Ruft eine Liste von Log-Einträgen ab, optional gefiltert nach einem Zeitraum und mit Pagination.
     *
     * @param int      $page    Die aktuelle Seite (Standard: 1)
     * @param int      $perPage Anzahl der Einträge pro Seite (Standard: 100)
     * @param int|null $since   Anzahl der Tage, aus denen Logs geladen werden sollen (optional)
     *
     * @return array Liste der Log-Einträge als assoziative Arrays.
     */
    public static function getLogs(int $page = 1, int $perPage = 100, ?int $since = null): array
    {
        $offset = ($page - 1) * $perPage;

        $query = ORM::for_table('logs')
            ->order_by_desc('datum_zeit')
            ->limit($perPage)
            ->offset($offset);

        if ($since !== null) {
            $query->where_raw("datum_zeit >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$since]);
        }

        return $query->find_array();
    }

    /**
     * Gibt die Gesamtanzahl der Log-Einträge zurück, optional gefiltert nach einem Zeitraum.
     *
     * @param int|null $since Anzahl der Tage, aus denen Logs gezählt werden sollen (optional)
     *
     * @return int Gesamtanzahl der Logs
     */
    public static function getTotalLogCount(?int $since = null): int
    {
        $query = ORM::for_table('logs');

        if ($since !== null) {
            $query->where_raw("datum_zeit >= DATE_SUB(NOW(), INTERVAL ? DAY)", [$since]);
        }

        return $query->count();
    }


    /**
     * Deletes a log entry with the given ID.
     *
     * @param int $id The ID of the log entry to be deleted.
     *
     * @return bool True if the log entry was successfully deleted, false otherwise.
     */
    public static function deleteLog($id)
    {
        $log = ORM::for_table('logs')->find_one($id);
        if ($log) {
            $log->delete();
            return true;
        }
        return false;
    }

    /**
     * Retrieves the IP address of the client, taking into account the HTTP_X_FORWARDED_FOR and HTTP_CLIENT_IP headers.
     * If none of those headers are present, falls back to REMOTE_ADDR.
     *
     * @return string The client's IP address
     */
    private static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; // Falls mehrere, nehme die erste
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
}
