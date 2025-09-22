<?php

namespace App\Controllers;

use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\SessionUtil;
use App\Models\User;
use App\Utils\TranslationUtil;
use Exception;
use Flight;

class RateLimitController
{
    /**
     * Rate Limiting Settings anzeigen
     */
    public function showSettings()
    {
        $configFile = "../config.php";
        $config = include $configFile;
        $rateLimitConfig = $config['rateLimiting'] ?? [];

        // Aktuelle Rate Limit Status für alle Typen abrufen
        $currentStatus = $this->getRateLimitStatus();

        // get current user
        $user = User::findUserById(SessionUtil::get("user")["id"]);

        Flight::latte()->render('admin/rate-limit-settings.latte', [
            'config' => $rateLimitConfig,
            'status' => $currentStatus,
            'available_limits' => $this->getAvailableLimitTypes(),
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
            'lang' => Flight::get('lang'),
            'title' => 'Rate Limiting Settings',
            "user" => $user,
        ]);
    }

    /**
     * Rate Limiting Settings updaten
     */
    /*
    public function updateSettings()
    {
        $data = Flight::request()->data;

        try {
            // Config-Datei einlesen
            $configFile = "../config.php";
            $config = include $configFile;

            // Rate Limiting Einstellungen updaten
            $config['rateLimiting']['enabled'] = isset($data->enabled);

            // Limits updaten
            if (isset($data->limits)) {
                foreach ($data->limits as $limitType => $limitData) {
                    $config['rateLimiting']['limits'][$limitType] = [
                        'requests' => (int)$limitData['requests'],
                        'window' => (int)$limitData['window']
                    ];
                }
            }

            // Settings updaten
            if (isset($data->settings)) {
                foreach ($data->settings as $setting => $value) {
                    $config['rateLimiting']['settings'][$setting] =
                        is_numeric($value) ? (int)$value : (bool)$value;
                }
            }

            // Config-Datei schreiben
            $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";

            if (file_put_contents($configFile, $configContent)) {
                LogUtil::logAction(
                    LogType::AUDIT,
                    'RateLimitController',
                    'updateSettings',
                    'Rate limiting settings updated by admin: ' . $_SESSION['user']['username']
                );

                Flight::json(['success' => true, 'message' => 'Rate limiting settings updated successfully']);
            } else {
                throw new \Exception('Failed to write config file');
            }
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'RateLimitController',
                'updateSettings',
                'Failed to update rate limiting settings: ' . $e->getMessage()
            );

            Flight::json(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }
        */

    public function updateSettings()
    {
        $data = Flight::request()->data;

        try {
            // Config-Datei einlesen
            $configFile = "../config.php";
            $configContent = file_get_contents($configFile);
            $existingConfig = include $configFile;

            // DEBUG: Pattern testen
            $pattern = '/(\$rateLimiting\s*=\s*\[)(.*?)(\];)/s';

            if (preg_match($pattern, $configContent, $matches)) {
                error_log("Pattern FOUND - Match: " . $matches[0]);
            } else {
                error_log("Pattern NOT FOUND in config file");
                // Pattern anpassen für Kommentare
                $pattern = '/(\/\/\s*Rate\s*Limiting.*?\n\$rateLimiting\s*=\s*\[)(.*?)(\];)/s';
                if (preg_match($pattern, $configContent, $matches)) {
                    error_log("Alternative Pattern FOUND");
                } else {
                    error_log("NO Pattern matches found!");
                }
            }

            if ($configContent === false) {
                throw new \Exception('Cannot read config file');
            }

            // Bestehende rateLimiting-Konfiguration als Basis verwenden
            $newConfig = $existingConfig['rateLimiting'] ?? [];

            // Nur die gesendeten Werte aktualisieren

            // Enabled Status aktualisieren
            $newConfig['enabled'] = isset($data->enabled);

            // Limits aktualisieren (nur die gesendeten)
            if (isset($data->limits)) {
                if (!isset($newConfig['limits'])) {
                    $newConfig['limits'] = [];
                }

                foreach ($data->limits as $limitType => $limitData) {
                    $newConfig['limits'][$limitType] = [
                        'requests' => (int)$limitData['requests'],
                        'window' => (int)$limitData['window']
                    ];
                }
            }

            // Settings aktualisieren (nur die gesendeten)
            if (isset($data->settings)) {
                if (!isset($newConfig['settings'])) {
                    $newConfig['settings'] = [];
                }

                foreach ($data->settings as $setting => $value) {
                    $newConfig['settings'][$setting] = is_numeric($value) ? (int)$value : (bool)$value;
                }
            }

            // Pattern für $rateLimiting Array
            $pattern = '/(\$rateLimiting\s*=\s*\[)(.*?)(\];)/s';

            // Neues Array als formatierter PHP-Code mit besserer Formatierung
            $newRateLimitingArray = $this->formatArrayForConfig($newConfig);

            // Neuen Block zusammenbauen
            $replacement = '$rateLimiting = ' . $newRateLimitingArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new \Exception('Failed to replace configuration content');
            }

            // Datei mit neuem Inhalt speichern
            if (file_put_contents($configFile, $newConfigContent)) {
                LogUtil::logAction(
                    LogType::AUDIT,
                    'RateLimitController',
                    'updateSettings',
                    'Rate limiting settings updated by admin: ' . $_SESSION['user']['username']
                );

                Flight::json(['success' => true, 'message' => 'Rate limiting settings updated successfully']);
            } else {
                throw new \Exception('Failed to write config file');
            }
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'RateLimitController',
                'updateSettings',
                'Failed to update rate limiting settings: ' . $e->getMessage()
            );

            Flight::json(['success' => false, 'message' => 'Failed to update settings: ' . $e->getMessage()]);
        }
    }

    /**
     * Formatiert Array für Config-Datei mit besserer Lesbarkeit
     */
    private function formatArrayForConfig($config)
    {
        $result = "[\n";

        foreach ($config as $key => $value) {
            if ($key === 'enabled') {
                $result .= "  'enabled' => " . ($value ? 'true' : 'false') . ",\n\n";
            } elseif ($key === 'limits') {
                $result .= "  // Custom Limits (überschreibt Defaults)\n";
                $result .= "  'limits' => [\n";

                // Gruppierung nach Kategorien
                $authLimits = ['login', 'register', 'forgot-password', 'reset-password', '2fa'];

                $result .= "    // Authentifizierung - sehr restriktiv\n";
                foreach ($authLimits as $limitType) {
                    if (isset($value[$limitType])) {
                        $limit = $value[$limitType];
                        $comment = $this->getLimitComment($limitType);
                        $result .= "    '$limitType' => ['requests' => {$limit['requests']}, 'window' => {$limit['window']}], $comment\n";
                    }
                }

                $result .= "\n    // Admin Bereiche - restriktiv\n";
                if (isset($value['admin'])) {
                    $limit = $value['admin'];
                    $result .= "    'admin' => ['requests' => {$limit['requests']}, 'window' => {$limit['window']}], // Admin-Actions\n";
                }

                $result .= "\n    // Globales Limit als Fallback\n";
                if (isset($value['global'])) {
                    $limit = $value['global'];
                    $result .= "    'global' => ['requests' => {$limit['requests']}, 'window' => {$limit['window']}] // Requests pro Stunde\n";
                }

                $result .= "  ],\n";
            } elseif ($key === 'settings') {
                $result .= "  // Erweiterte Einstellungen\n";
                $result .= "  'settings' => [\n";

                foreach ($value as $settingKey => $settingValue) {
                    $comment = $this->getSettingComment($settingKey);
                    $formattedValue = is_bool($settingValue) ? ($settingValue ? 'true' : 'false') : $settingValue;
                    $result .= "    '$settingKey' => $formattedValue, $comment\n";
                }

                $result .= "  ]\n";
            }
        }

        $result .= "]";
        return $result;
    }

    private function getLimitComment($limitType)
    {
        return match ($limitType) {
            'login' => '// Login Versuche',
            'register' => '// Registrierungen pro Stunde',
            'forgot-password' => '// Password-Resets pro Stunde',
            'reset-password' => '// Reset-Versuche pro Stunde',
            '2fa' => '// 2FA Versuche in 5 Minuten',
            default => ''
        };
    }

    private function getSettingComment($setting)
    {
        return match ($setting) {
            'cleanup_interval' => '// Session cleanup Intervall',
            'max_violations_per_hour' => '// Max Violations bevor schärfere Maßnahmen',
            'block_repeat_offenders' => '// Repeat Offenders härter bestrafen',
            'log_violations' => '// Violations loggen',
            'auto_refresh_on_limit' => '// Automatischer Refresh nach Ablauf',
            default => ''
        };
    }

    /**
     * Aktuelle Rate Limit Violations anzeigen
     */
    public function showViolations()
    {
        $violations = $this->getRecentViolations();
        $stats = $this->getViolationStats();

        // get current user
        $user = User::findUserById(SessionUtil::get("user")["id"]);

        Flight::app()->latte()->render('admin/rate-limit-violations.latte', [
            'violations' => $violations,
            'stats' => $stats,
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
            'lang' => Flight::get('lang'),
            'title' => 'Rate Limit Violations',
            "user" => $user,
        ]);
    }

    /**
     * Rate Limit für IP/User zurücksetzen
     */
    public function resetLimit()
    {
        $data = Flight::request()->data;
        $identifier = $data->identifier ?? '';
        $limitType = $data->limit_type ?? '';

        if (empty($identifier)) {
            Flight::json(['success' => false, 'message' => 'Missing identifier']);
            return;
        }

        try {
            // Session-basierte Rate Limits zurücksetzen
            $key = $limitType . ':' . $identifier;

            if (isset($_SESSION['rate_limits'][$key])) {
                unset($_SESSION['rate_limits'][$key]);

                LogUtil::logAction(
                    LogType::AUDIT,
                    'RateLimitController',
                    'resetLimit',
                    "Rate limit reset by admin for identifier: {$identifier}, type: {$limitType}"
                );

                Flight::json(['success' => true, 'message' => 'Rate limit reset successfully']);
            } else {
                Flight::json(['success' => false, 'message' => 'No rate limit found for this identifier']);
            }
        } catch (\Exception $e) {
            Flight::json(['success' => false, 'message' => 'Failed to reset rate limit: ' . $e->getMessage()]);
        }
    }

    /**
     * Live Rate Limit Status für Dashboard
     */
    public function getLiveStatus()
    {
        $status = [
            'enabled' => $this->isRateLimitingEnabled(),
            'active_limits' => $this->getActiveLimits(),
            'recent_violations' => $this->getRecentViolations(10),
            'top_violators' => $this->getTopViolators(),
            'stats' => $this->getViolationStats()
        ];

        Flight::json($status);
    }

    // Private Helper Methods

    private function isRateLimitingEnabled(): bool
    {
        $configFile = "../config.php";
        $config = include $configFile;
        return $config['rateLimiting']['enabled'] ?? true;
    }

    private function getRateLimitStatus(): array
    {
        $status = [];
        $limitTypes = ['login', 'register', '2fa', 'forgot-password', 'reset-password', 'admin', 'global'];

        foreach ($limitTypes as $type) {
            $rateLimiter = new \App\Middleware\RateLimiter();
            $status[$type] = $rateLimiter->getStatus($type);
        }

        return $status;
    }

    private function getAvailableLimitTypes(): array
    {
        return [
            'login' => 'Login Attempts',
            'register' => 'Registration',
            '2fa' => '2FA Verification',
            'forgot-password' => 'Password Reset',
            'reset-password' => 'Password Reset Execution',
            'admin' => 'Admin Actions',
            'global' => 'Global Fallback'
        ];
    }

    private function getRecentViolations(int $limit = 50): array
    {
        // Session-basierte Violations auslesen
        $violations = [];

        if (isset($_SESSION['rate_limits'])) {
            foreach ($_SESSION['rate_limits'] as $key => $data) {
                if (strpos($key, 'violations:') === 0) {
                    $identifier = substr($key, 11); // "violations:" entfernen

                    if (is_array($data)) {
                        foreach ($data as $violation) {
                            if (is_array($violation) && isset($violation['time'])) {
                                $violations[] = [
                                    'identifier' => $identifier,
                                    'type' => $violation['type'] ?? 'unknown',
                                    'time' => $violation['time'],
                                    'timestamp' => date('Y-m-d H:i:s', $violation['time'])
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Nach Zeit sortieren (neueste zuerst)
        usort($violations, function ($a, $b) {
            return $b['time'] - $a['time'];
        });

        return array_slice($violations, 0, $limit);
    }

    private function getViolationStats(): array
    {
        $violations = $this->getRecentViolations(1000);

        $stats = [
            'total_violations' => count($violations),
            'violations_last_hour' => 0,
            'violations_last_24h' => 0,
            'by_type' => [],
            'unique_identifiers' => []
        ];

        $now = time();
        $oneHour = $now - 3600;
        $oneDay = $now - 86400;

        foreach ($violations as $violation) {
            // Zeit-basierte Stats
            if ($violation['time'] > $oneHour) {
                $stats['violations_last_hour']++;
            }
            if ($violation['time'] > $oneDay) {
                $stats['violations_last_24h']++;
            }

            // Type-basierte Stats
            $type = $violation['type'];
            $stats['by_type'][$type] = ($stats['by_type'][$type] ?? 0) + 1;

            // Unique Identifiers
            $stats['unique_identifiers'][$violation['identifier']] =
                ($stats['unique_identifiers'][$violation['identifier']] ?? 0) + 1;
        }

        return $stats;
    }

    private function getActiveLimits(): array
    {
        $active = [];

        if (isset($_SESSION['rate_limits'])) {
            foreach ($_SESSION['rate_limits'] as $key => $data) {
                if (strpos($key, 'violations:') !== 0 && is_array($data) && !empty($data)) {
                    [$type, $identifier] = explode(':', $key, 2);
                    $active[] = [
                        'type' => $type,
                        'identifier' => $identifier,
                        'requests' => count($data),
                        'last_request' => max($data)
                    ];
                }
            }
        }

        return $active;
    }

    private function getTopViolators(): array
    {
        $violations = $this->getRecentViolations(1000);
        $violators = [];

        foreach ($violations as $violation) {
            $id = $violation['identifier'];
            $violators[$id] = ($violators[$id] ?? 0) + 1;
        }

        arsort($violators);

        return array_slice($violators, 0, 10, true);
    }

    /**
     * Alle Rate Limit Violations löschen
     */
    public function clearViolations()
    {
        try {
            // Session-basierte Rate Limits zurücksetzen
            if (isset($_SESSION['rate_limits'])) {
                $clearedCount = count($_SESSION['rate_limits']);
                unset($_SESSION['rate_limits']);

                LogUtil::logAction(
                    LogType::AUDIT,
                    'RateLimitController',
                    'clearViolations',
                    "All rate limit violations cleared by admin. Count: {$clearedCount}"
                );

                Flight::json([
                    'success' => true,
                    'message' => 'All violations cleared successfully',
                    'cleared_count' => $clearedCount
                ]);
            } else {
                Flight::json([
                    'success' => true,
                    'message' => 'No violations to clear',
                    'cleared_count' => 0
                ]);
            }
        } catch (Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'RateLimitController',
                'clearViolations',
                'Failed to clear rate limit violations: ' . $e->getMessage()
            );

            Flight::json([
                'success' => false,
                'message' => 'Failed to clear violations: ' . $e->getMessage()
            ]);
        }
    }
}
