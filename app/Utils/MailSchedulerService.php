<?php

namespace App\Utils;

use Exception;

/**
 * Class Name: MailSchedulerService
 *
 * Service for managing the mail scheduler worker process
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-01-10
 *
 * Changes:
 * - 1.0 (2025-01-10): Initial creation
 */
class MailSchedulerService
{
    private static string $pidFile = __DIR__ . '/../../cache/mail_scheduler.pid';
    private static string $heartbeatFile = __DIR__ . '/../../cache/mail_scheduler_heartbeat.txt';
    private static string $statusFile = __DIR__ . '/../../cache/mail_scheduler_status.json';
    private static string $stopSignalFile = __DIR__ . '/../../cache/mail_scheduler_stop.signal';

    /**
     * Checks if the scheduler worker is running
     *
     * @return bool True if running
     */
    public static function isRunning(): bool
    {
        if (!file_exists(self::$pidFile)) {
            return false;
        }

        $pid = (int) trim((string) file_get_contents(self::$pidFile));
        if ($pid <= 0) {
            return false;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec('tasklist /FI "PID eq ' . $pid . '" /NH 2>NUL');
            return is_string($output) && strpos($output, (string)$pid) !== false;
        }

        // macOS + Linux
        if (function_exists('posix_kill')) {
            if (@posix_kill($pid, 0)) {
                return true;
            }

            $err = posix_get_last_error();
            // EPERM (1) = Operation not permitted, d.h. Prozess existiert, aber kein Zugriff
            if ($err === 1) {
                return true;
            }

            return false;
        }

        // Fallback ohne posix_kill
        $res = trim((string) shell_exec('kill -0 ' . (int)$pid . ' 2>/dev/null && echo 1 || echo 0'));
        if ($res === '1') {
            return true;
        }

        $out = shell_exec('ps -p ' . (int)$pid . ' -o pid= 2>/dev/null');
        return is_string($out) && trim($out) !== '';
    }



    /**
     * Gets the PID of the running scheduler
     *
     * @return int|null PID or null if not running
     */
    public static function getPid(): ?int
    {
        if (!file_exists(self::$pidFile)) {
            return null;
        }

        return (int) file_get_contents(self::$pidFile);
    }

    /**
     * Gets the last heartbeat timestamp
     *
     * @return int|null Unix timestamp or null
     */
    public static function getLastHeartbeat(): ?int
    {
        if (!file_exists(self::$heartbeatFile)) {
            return null;
        }

        return (int) file_get_contents(self::$heartbeatFile);
    }

    /**
     * Updates the heartbeat timestamp
     *
     * @return bool Success status
     */
    public static function updateHeartbeat(): bool
    {
        try {
            return file_put_contents(self::$heartbeatFile, time()) !== false;
        } catch (Exception $e) {
            error_log("Failed to update heartbeat: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets scheduler status information
     *
     * @return array Status information
     */
    public static function getStatus(): array
    {
        $isRunning = self::isRunning();
        $lastHeartbeat = self::getLastHeartbeat();
        $pid = self::getPid();

        $status = [
            'running' => $isRunning,
            'pid' => $pid,
            'last_heartbeat' => $lastHeartbeat,
            'last_heartbeat_human' => null,
            'heartbeat_age_seconds' => null,
            'is_healthy' => false
        ];

        if ($lastHeartbeat !== null) {
            $status['last_heartbeat_human'] = date('Y-m-d H:i:s', $lastHeartbeat);
            $status['heartbeat_age_seconds'] = time() - $lastHeartbeat;

            // Healthy if heartbeat is less than 60 seconds old
            $status['is_healthy'] = $isRunning && $status['heartbeat_age_seconds'] < 60;
        }

        // Load additional status from file if exists
        if (file_exists(self::$statusFile)) {
            $fileStatus = json_decode(file_get_contents(self::$statusFile), true);
            if (is_array($fileStatus)) {
                $status = array_merge($status, $fileStatus);
            }
        }

        return $status;
    }

    /**
     * Updates the status file with current worker information
     *
     * @param array $statusData Status data to save
     * @return bool Success status
     */
    public static function updateStatus(array $statusData): bool
    {
        try {
            $currentStatus = [];
            if (file_exists(self::$statusFile)) {
                $currentStatus = json_decode(file_get_contents(self::$statusFile), true) ?? [];
            }

            $mergedStatus = array_merge($currentStatus, $statusData);

            return file_put_contents(
                self::$statusFile,
                json_encode($mergedStatus, JSON_PRETTY_PRINT)
            ) !== false;
        } catch (Exception $e) {
            error_log("Failed to update status: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Starts the scheduler worker process
     *
     * @return array{success:bool, message:string, pid?:int}
     */
    public static function startWorker(): array
    {
        if (self::isRunning()) {
            return ['success' => false, 'message' => 'Scheduler is already running'];
        }

        // Aufräumen
        @unlink(self::$stopSignalFile);
        @unlink(self::$statusFile);

        $workerPath = realpath(__DIR__ . '/../Workers/MailSchedulerWorker.php');
        if ($workerPath === false || !is_file($workerPath)) {
            return ['success' => false, 'message' => 'Worker file not found'];
        }

        try {
            if (PHP_OS_FAMILY === 'Windows') {
                $cmd = 'start /B "' . basename($workerPath) . '" ' .
                    escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($workerPath) . ' > NUL 2>&1';
                pclose(popen($cmd, 'r'));
                // Windows-PID sauber ermitteln ist messy; wir verlassen uns hier auf den Worker, der die PID schreibt.
            } else {
                // macOS + Linux
                $phpBin = PHP_BINARY; // Vermeide PATH-Probleme auf macOS/Homebrew
                $pidFile = self::$pidFile;
                $logFile = __DIR__ . '/../../cache/mail_scheduler_debug.log';

                // Starten, vollständig detachen, PID zurückgeben und selbst ins PID-File schreiben
                // - nohup: überlebt aufrufende Shell
                // - setsid: neue Session (nicht immer nötig, aber sauber)
                // - echo $!: PID des letzten Background-Commands
                // WICHTIG: Fehler in Log-Datei schreiben für Debugging
                $sh = 'nohup ' . escapeshellarg($phpBin) . ' ' . escapeshellarg($workerPath) .
                    ' >> ' . escapeshellarg($logFile) . ' 2>&1 & echo $!';

                // Wichtig: über /bin/sh laufen lassen, sonst bekommst du $! nicht
                $pid = (int) shell_exec($sh);

                if ($pid > 0) {
                    // PID-File atomar schreiben
                    $tmp = $pidFile . '.tmp';
                    file_put_contents($tmp, (string)$pid, LOCK_EX);
                    rename($tmp, $pidFile);
                } else {
                    return ['success' => false, 'message' => 'Failed to obtain worker PID'];
                }
            }

            // Verifizieren mit kleinem Backoff (max ~2s)
            $deadline = microtime(true) + 2.0;
            $started = false;
            do {
                usleep(100_000); // 100ms
                $started = self::isRunning();
            } while (!$started && microtime(true) < $deadline);

            if ($started) {
                $pid = self::getPid(); // liest aus dem PID-File
                
                // Log to file instead of database
                $logDir = __DIR__ . '/../logs/';
                if (!is_dir($logDir)) {
                    mkdir($logDir, 0750, true);
                }
                $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Mail scheduler started successfully (PID " . (int)$pid . ")\n";
                @file_put_contents($logDir . 'mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
                
                return ['success' => true, 'message' => 'Scheduler started successfully', 'pid' => (int)$pid];
            }

            return ['success' => false, 'message' => 'Failed to start scheduler worker'];
        } catch (Exception $e) {
            // Log to file instead of database
            $logDir = __DIR__ . '/../logs/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0750, true);
            }
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Failed to start scheduler: " . $e->getMessage() . "\n";
            @file_put_contents($logDir . 'mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
            
            return ['success' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }


    /**
     * Stops the scheduler worker process
     *
     * @return array Result with success status and message
     */
    public static function stopWorker(): array
    {
        if (!self::isRunning()) {
            return [
                'success' => false,
                'message' => 'Scheduler is not running'
            ];
        }

        try {
            // Create stop signal file
            file_put_contents(self::$stopSignalFile, time());

            // Wait for graceful shutdown (max 10 seconds)
            $maxWait = 10;
            $waited = 0;

            while (self::isRunning() && $waited < $maxWait) {
                sleep(1);
                $waited++;
            }

            // If still running, force kill
            if (self::isRunning()) {
                $pid = self::getPid();

                if (PHP_OS_FAMILY === 'Windows') {
                    exec("taskkill /F /PID {$pid}");
                } else {
                    posix_kill($pid, SIGTERM);
                }

                sleep(1);
            }

            // Clean up PID file only
            // Keep stop signal file so auto-start middleware respects manual stop
            if (file_exists(self::$pidFile)) {
                unlink(self::$pidFile);
            }

            // Log to file instead of database
            $logDir = __DIR__ . '/../logs/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0750, true);
            }
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Mail scheduler stopped successfully (manual stop)\n";
            @file_put_contents($logDir . 'mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);

            return [
                'success' => true,
                'message' => 'Scheduler stopped successfully'
            ];
        } catch (Exception $e) {
            // Log to file instead of database
            $logDir = __DIR__ . '/../logs/';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0750, true);
            }
            $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Failed to stop scheduler: " . $e->getMessage() . "\n";
            @file_put_contents($logDir . 'mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Checks if stop signal exists
     *
     * @return bool True if stop was requested
     */
    public static function shouldStop(): bool
    {
        return file_exists(self::$stopSignalFile);
    }

    /**
     * Writes the PID file
     *
     * @param int $pid Process ID
     * @return bool Success status
     */
    public static function writePidFile(int $pid): bool
    {
        try {
            return file_put_contents(self::$pidFile, $pid) !== false;
        } catch (Exception $e) {
            error_log("Failed to write PID file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Auto-starts the scheduler if not running
     * Called by middleware on HTTP requests
     *
     * @return bool True if started or already running
     */
    public static function autoStart(): bool
    {
        // Don't auto-start if explicitly stopped
        if (file_exists(self::$stopSignalFile)) {
            return false;
        }

        // Don't start if already running
        if (self::isRunning()) {
            return true;
        }

        // Start the worker
        $result = self::startWorker();

        return $result['success'];
    }
}
