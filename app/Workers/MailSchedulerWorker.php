<?php

/**
 * Mail Scheduler Worker
 *
 * Background process that processes mail jobs from the queue
 * Runs independently and can be started/stopped via admin interface
 *
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-01-10
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from command line');
}

// Load configuration and dependencies
try {
    require_once __DIR__ . '/../../config.php';
    require_once __DIR__ . '/../../vendor/autoload.php';
    echo "[" . date('Y-m-d H:i:s') . "] Configuration and autoloader loaded successfully\n";
} catch (Exception $e) {
    error_log("Failed to load configuration: " . $e->getMessage());
    die("FATAL: Failed to load configuration: " . $e->getMessage() . "\n");
}

use App\Models\MailJob;
use App\Utils\MailUtil;
use App\Utils\MailSchedulerService;

// Set up database connection
try {
    echo "[" . date('Y-m-d H:i:s') . "] Configuring database connection...\n";
    
    // Configure ORM with DSN
    $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4";
    ORM::configure($dsn);
    ORM::configure('username', $db['user']);
    ORM::configure('password', $db['pass']);
    ORM::configure('driver_options', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
    ORM::configure('error_mode', PDO::ERRMODE_EXCEPTION);
    ORM::configure('logging', false);
    
    // Force ORM to establish connection immediately to verify it works
    $testQuery = ORM::for_table('logs')->limit(1)->find_one();
    
    echo "[" . date('Y-m-d H:i:s') . "] Database configured and tested successfully\n";
} catch (Exception $e) {
    $errorMsg = "Worker DB connection failed: " . $e->getMessage();
    error_log($errorMsg);
    echo "FATAL: " . $errorMsg . "\n";
    exit(1);
}

// Write PID file
$pid = getmypid();
echo "[" . date('Y-m-d H:i:s') . "] Worker PID: {$pid}\n";
MailSchedulerService::writePidFile($pid);
echo "[" . date('Y-m-d H:i:s') . "] PID file written\n";

// Ensure log directory exists
$logDir = __DIR__ . '/../logs/';
if (!is_dir($logDir)) {
    mkdir($logDir, 0750, true);
}

// Initial status
MailSchedulerService::updateStatus([
    'started_at' => date('Y-m-d H:i:s'),
    'jobs_processed' => 0,
    'jobs_failed' => 0,
    'last_run' => null
]);
echo "[" . date('Y-m-d H:i:s') . "] Initial status written\n";

// Log startup to file instead of database to avoid ORM issues
$logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Mail scheduler worker started with PID {$pid}\n";
file_put_contents($logDir . 'mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
echo "[" . date('Y-m-d H:i:s') . "] Startup logged to file\n";

echo "Mail Scheduler Worker started (PID: {$pid})\n";
echo "Press Ctrl+C to stop or use admin interface\n";

$jobsProcessed = 0;
$jobsFailed = 0;
$cycleCount = 0;

echo "[" . date('Y-m-d H:i:s') . "] Entering main worker loop\n";

// Main worker loop
while (true) {
    $cycleCount++;
    echo "[" . date('Y-m-d H:i:s') . "] Cycle #{$cycleCount} started\n";
    
    // Check for stop signal
    if (MailSchedulerService::shouldStop()) {
        echo "Stop signal received, shutting down gracefully...\n";
        
        // Log shutdown to file
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Scheduler stopped gracefully. Processed: {$jobsProcessed}, Failed: {$jobsFailed}\n";
        file_put_contents(__DIR__ . '/../logs/mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
        
        break;
    }

    // Update heartbeat
    MailSchedulerService::updateHeartbeat();

    try {
        // Get pending jobs
        $jobs = MailJob::getPendingJobs(5);

        if (empty($jobs)) {
            // No jobs to process
            echo "[" . date('H:i:s') . "] No pending jobs, waiting...\n";
            sleep(10);
            continue;
        }

        echo "[" . date('H:i:s') . "] Found " . count($jobs) . " job(s) to process\n";

        foreach ($jobs as $job) {
            // Check stop signal before each job
            if (MailSchedulerService::shouldStop()) {
                break 2; // Break out of both foreach and while
            }

            try {
                echo "  Processing job #{$job->id} to {$job->recipient}...\n";

                // Mark as processing
                MailJob::markAsProcessing($job->id);

                // Decode template data
                $templateData = json_decode($job->template_data, true) ?? [];

                // Send email using existing MailUtil
                $success = MailUtil::sendMail(
                    $job->recipient,
                    $job->subject,
                    $job->template,
                    $templateData
                );

                if ($success) {
                    // Mark as completed
                    MailJob::markAsCompleted($job->id);
                    $jobsProcessed++;

                    echo "  ✓ Job #{$job->id} completed successfully\n";

                    // Log success to file
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Successfully sent email to {$job->recipient} using template '{$job->template}'\n";
                    file_put_contents(__DIR__ . '/../logs/mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
                } else {
                    // Mark as failed
                    $errorMsg = "Failed to send email";
                    MailJob::markAsFailed($job->id, $errorMsg);
                    $jobsFailed++;

                    echo "  ✗ Job #{$job->id} failed: {$errorMsg}\n";

                    // Log failure to file
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Failed to send email to {$job->recipient}: {$errorMsg}\n";
                    file_put_contents(__DIR__ . '/../logs/mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
                }
            } catch (Exception $e) {
                // Handle job-specific errors
                $errorMsg = $e->getMessage();
                MailJob::markAsFailed($job->id, $errorMsg);
                $jobsFailed++;

                echo "  ✗ Job #{$job->id} exception: {$errorMsg}\n";

                // Log exception to file
                $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Exception processing job #{$job->id}: {$errorMsg}\n";
                file_put_contents(__DIR__ . '/../logs/mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);
            }

            // Small delay between jobs
            sleep(1);
        }

        // Update status after processing batch
        MailSchedulerService::updateStatus([
            'jobs_processed' => $jobsProcessed,
            'jobs_failed' => $jobsFailed,
            'last_run' => date('Y-m-d H:i:s'),
            'cycle_count' => $cycleCount
        ]);
    } catch (Exception $e) {
        echo "Worker error: " . $e->getMessage() . "\n";
        
        // Log worker error to file
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [MAILSCHEDULER] Worker loop error: " . $e->getMessage() . "\n";
        file_put_contents(__DIR__ . '/../logs/mail_scheduler.log', $logMsg, FILE_APPEND | LOCK_EX);

        // Wait before retrying after error
        sleep(30);
    }

    // Wait before next cycle
    sleep(5);
}

// Cleanup on shutdown
echo "Worker stopped. Processed: {$jobsProcessed}, Failed: {$jobsFailed}\n";

MailSchedulerService::updateStatus([
    'stopped_at' => date('Y-m-d H:i:s'),
    'jobs_processed' => $jobsProcessed,
    'jobs_failed' => $jobsFailed
]);

exit(0);
