<?php
namespace App\Middleware;

use Flight;
use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\TranslationUtil;

/**
 * Class Name: IpWhitelistMiddleware
 *
 * Middleware class for checking IP addresses against a whitelist.
 * Supports both individual IPs and CIDR notation.
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-10-03
 *
 * Changes:
 * - 1.0 (2025-10-03): Created.
 */
class IpWhitelistMiddleware
{
    /**
     * Checks if the current IP address is on the whitelist.
     * Blocks access if whitelist is active and IP is not allowed.
     *
     * @return bool True if access is allowed, otherwise halts execution
     */
    public static function checkIpWhitelist(): bool
    {
        $config = include __DIR__ . '/../../config.php';
        
        // If whitelist is disabled, allow access
        if (!($config['security']['enableIpWhitelist'] ?? false)) {
            return true;
        }

        $clientIp = self::getClientIp();
        $whitelist = $config['security']['adminIpWhitelist'] ?? [];

        // Check if IP is on whitelist
        if (self::isIpAllowed($clientIp, $whitelist)) {
            return true;
        }

        // IP not allowed - block access and log
        LogUtil::logAction(
            LogType::SECURITY,
            'IpWhitelistMiddleware',
            'checkIpWhitelist',
            "BLOCKED: Access denied for IP {$clientIp}. Not on whitelist."
        );

        // Show error page with HTTP 403
        Flight::halt(403, self::renderAccessDeniedPage($clientIp));
        return false;
    }

    /**
     * Checks if an IP address is allowed in the whitelist.
     * Supports both individual IPs and CIDR notation.
     *
     * @param string $ip The IP address to check
     * @param array $whitelist Array of allowed IPs/CIDR ranges
     * @return bool True if IP is allowed
     */
    private static function isIpAllowed(string $ip, array $whitelist): bool
    {
        foreach ($whitelist as $allowedEntry) {
            $allowedEntry = trim($allowedEntry);
            
            // Skip empty entries
            if (empty($allowedEntry)) {
                continue;
            }

            // CIDR notation?
            if (strpos($allowedEntry, '/') !== false) {
                if (self::ipInCidr($ip, $allowedEntry)) {
                    return true;
                }
            } else {
                // Individual IP
                if ($ip === $allowedEntry) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if an IP is within a CIDR range.
     *
     * @param string $ip The IP address to check
     * @param string $cidr CIDR notation (e.g., "192.168.1.0/24")
     * @return bool True if IP is in CIDR range
     */
    private static function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        // Validate mask
        if ($mask < 0 || $mask > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - $mask);
        
        $subnetLong &= $maskLong;
        
        return ($ipLong & $maskLong) === $subnetLong;
    }

    /**
     * Gets the client's IP address.
     * Considers proxy headers if present.
     *
     * @return string The client's IP address
     */
    private static function getClientIp(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Renders the access denied error page.
     *
     * @param string $clientIp The blocked IP address
     * @return string HTML content for the error page
     */
    private static function renderAccessDeniedPage(string $clientIp): string
    {
        $title = TranslationUtil::t('security.ip_whitelist.access_denied');
        $message = TranslationUtil::t('security.ip_whitelist.ip_not_allowed');
        $yourIp = TranslationUtil::t('security.ip_whitelist.your_ip');
        $contact = TranslationUtil::t('security.ip_whitelist.contact_admin');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link href="/css/fastbootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: var(--bs-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            max-width: 600px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 3rem;
            text-align: center;
        }
        .error-icon {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        .ip-address {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin: 1.5rem 0;
            font-family: monospace;
            font-size: 1.1rem;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <h1>{$title}</h1>
        <p class="lead">{$message}</p>
        <div class="ip-address">
            <strong>{$yourIp}:</strong> {$clientIp}
        </div>
        <p class="text-muted">{$contact}</p>
    </div>
</body>
</html>
HTML;
    }
}