<?php

namespace App\Utils;

use APP\Models\User;
use ORM;

/**
 * Class Name: DataExportUtil
 *
 * Utility class for exporting user data (GDPR Art. 15 - Right of Access).
 * Provides JSON and PDF export functionality for personal data.
 *
 * @package App\Utils
 * @author SecStore GDPR Module
 * @version 1.0
 * @since 2025-10-07
 *
 * Changes:
 * - 1.0 (2025-10-07): Created for GDPR compliance.
 */
class DataExportUtil
{
    /**
     * Collects all personal data for a user.
     *
     * @param int $userId The user ID
     * @return array|null Array containing all user data or null if user not found
     */
    public static function collectUserData(int $userId): ?array
    {
        $user = User::findUserById($userId);
        if ($user === false) {
            return null;
        }

        // Basic user data
        $userData = [
            'user_profile' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'status' => $user->status == 1 ? 'active' : 'inactive',
                'roles' => $user->roles,
                'created_at' => $user->created_at,
                'ldap_enabled' => $user->ldapEnabled == 1,
                'entra_id_enabled' => $user->entraIdEnabled == 1,
            ],
            'security_settings' => [
                'mfa_enabled' => $user->mfaEnabled == 1,
                'mfa_enforced' => $user->mfaEnforced == 1,
                'last_known_ip' => $user->lastKnownIp,
            ],
            'logs' => self::getUserLogs($user->username),
            'failed_login_attempts' => self::getFailedLoginAttempts($user->email),
            'export_metadata' => [
                'export_date' => date('Y-m-d H:i:s'),
                'export_format' => 'JSON',
                'gdpr_article' => 'Article 15 - Right of Access',
            ]
        ];

        return $userData;
    }

    /**
     * Gets all logs related to a specific user.
     *
     * @param string $username The username
     * @return array Array of log entries
     */
    private static function getUserLogs(string $username): array
    {
        ORM::configure('logging', true);
        $logs = ORM::for_table('logs')
            ->where('user', $username)
            ->order_by_desc('datum_zeit')
            ->limit(1000) // Limit to last 1000 entries
            ->find_array();

        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'DataExportUtil.php', 'getUserLogs', $lastQuery);
        }

        return $logs;
    }

    /**
     * Gets failed login attempts for a user's email.
     *
     * @param string $email The user's email
     * @return array Array of failed login attempts
     */
    private static function getFailedLoginAttempts(string $email): array
    {
        ORM::configure('logging', true);
        $attempts = ORM::for_table('failed_logins')
            ->where('email', $email)
            ->order_by_desc('last_attempt')
            ->find_array();

        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'DataExportUtil.php', 'getFailedLoginAttempts', $lastQuery);
        }

        return $attempts;
    }

    /**
     * Exports user data as JSON.
     *
     * @param int $userId The user ID
     * @return string JSON string of user data
     */
    public static function exportAsJson(int $userId): string
    {
        $data = self::collectUserData($userId);
        if ($data === null) {
            return json_encode(['error' => 'User not found'], JSON_PRETTY_PRINT);
        }

        LogUtil::logAction(
            LogType::AUDIT,
            'DataExportUtil.php',
            'exportAsJson',
            "User ID {$userId} exported their data as JSON"
        );

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generates a filename for data export.
     *
     * @param int $userId The user ID
     * @param string $format File format (json, pdf)
     * @return string Filename
     */
    public static function generateFilename(int $userId, string $format = 'json'): string
    {
        $user = User::findUserById($userId);
        $username = $user !== false ? $user->username : "user_{$userId}";
        $date = date('Y-m-d');

        return "gdpr_data_export_{$username}_{$date}.{$format}";
    }

    /**
     * Sends JSON export as download to browser.
     *
     * @param int $userId The user ID
     * @return void
     */
    public static function sendJsonDownload(int $userId): void
    {
        $json = self::exportAsJson($userId);
        $filename = self::generateFilename($userId, 'json');

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');

        echo $json;
        exit;
    }
}
