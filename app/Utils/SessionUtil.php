<?php
namespace App\Utils;

/**
 * Class Name: SessionUtil
 *
 * Hilfs-Klasse zur Verwaltung von Benutzersitzungen.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class SessionUtil
{
    /** @var array $config */
    private static $config;

    /**
     * Loads the configuration from the config.php file.
     *
     * This function is lazy and only loads the configuration the first time it is
     * called. Subsequent calls will return the previously loaded configuration.
     */
    public static function loadConfig()
    {
        if (!self::$config) {
            self::$config = include __DIR__ . '/../../config.php';
        }
    }

    /**
     * Get the remaining session time in seconds.
     *
     * @return int Remaining session time.
     */
    public static function getRemainingTime(): int
    {
        self::loadConfig();
        $_SESSION['sessionTimeout'] = self::$config['application']['sessionTimeout'];
        return isset($_SESSION['last_activity']) ? (($_SESSION['last_activity'] + self::$config['application']['sessionTimeout']) - time()) : 0;
    }

    /**
     * Gets the session timeout value.
     *
     * @return int The session timeout in seconds.
     */
    public static function getSessionTimeout(): int
    {
        self::loadConfig();
        return self::$config['application']['sessionTimeout'];
    }

    /**
     * Sets a session variable.
     *
     * @param string $key The name of the session variable.
     * @param mixed $value The value to be stored in the session.
     */

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves a session variable.
     *
     * @param string $key The name of the session variable.
     * @param mixed $default The default value to return if the session variable is not set.
     *
     * @return mixed The value of the session variable, or the default value if it is not set.
     */

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Checks if a session variable exists.
     *
     * @param string $key The name of the session variable.
     *
     * @return bool True if the session variable exists, false if it does not.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Destroys the session.  This will remove all session variables and reset the session ID.
     */
    public static function destroy(): void
    {
        session_destroy();
    }
}
