<?php

namespace App\Middleware;

use App\Utils\MailSchedulerService;
use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Class Name: SchedulerAutoStartMiddleware
 *
 * Middleware that automatically starts the mail scheduler if not running
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-01-10
 *
 * Changes:
 * - 1.0 (2025-01-10): Initial creation
 */
class SchedulerAutoStartMiddleware
{
    /**
     * Checks if scheduler is running and starts it if needed
     *
     * This runs on every request but has minimal overhead:
     * - Only checks PID file existence
     * - Only attempts start if not running
     * - Respects stop signal
     *
     * @return void
     */
    public static function checkAndStart(): void
    {
        // Skip for certain routes (setup, login, static resources)
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        $skipPaths = [
            '/setup',
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/verify',
            '/2fa-verify',
            '/enable-2fa',
            '/css/',
            '/js/',
            '/images/',
            '/favicon.ico'
        ];

        foreach ($skipPaths as $skip) {
            if (strpos($currentPath, $skip) !== false) {
                return;
            }
        }

        // Auto-start if not running
        // This is lightweight - just checks PID file
        try {
            if (!MailSchedulerService::isRunning()) {
                $result = MailSchedulerService::autoStart();
                
                if ($result) {
                    LogUtil::logAction(
                        LogType::MAILSCHEDULER,
                        'SchedulerAutoStartMiddleware',
                        'checkAndStart',
                        'Scheduler auto-started on request to ' . $currentPath
                    );
                }
            }
        } catch (\Exception $e) {
            // Silent fail - don't interrupt request
            // Errors are logged in MailSchedulerService
            error_log("Scheduler auto-start error: " . $e->getMessage());
        }
    }
}
