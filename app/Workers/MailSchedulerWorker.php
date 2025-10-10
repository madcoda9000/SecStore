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
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Models\MailJob;
use App\Utils\MailUtil;
use App\Utils\MailSchedulerService;
use App\Utils\LogUtil;
use App\Utils\LogType;

// Set up database connection
try {
    ORM::configure("mysql:host={$db['host']};dbname={$db['name']}", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    ORM::configure('username', $db['user']);
    ORM::configure('password', $db['pass']);
    ORM::configure('driver_options', [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4']);
} catch (Exception $e) {
    error_log("Worker DB connection failed: " . $e->getMessage());
    exit(1);
}

// Write PID file
$pid = getmypid();
MailSchedulerService::writePidFile($pid);

// Initial status
MailSchedulerService::updateStatus([
    'started_at' => date('Y-m-d H:i:s'),
    'jobs_processed' => 0,
    'jobs_failed' => 0,
    'last_run' => null
]);

LogUtil::logAction(
    LogType::MAILSCHEDULER,
    'MailSchedulerWorker',
    'startup',
    "Mail scheduler worker started with PID {$pid}"
);

echo "Mail Scheduler Worker started (PID: {$pid})\n";
echo "Press Ctrl+C to stop or use admin interface\n";

$jobsProcessed = 0;
$jobsFailed = 0;
$cycleCount = 0;

// Main worker loop
while (true) {
    $cycleCount++;
    
    // Check for stop signal
    if (MailSchedulerService::shouldStop()) {
        echo "Stop signal received, shutting down gracefully...\n";
        
        LogUtil::logAction(
            LogType::MAILSCHEDULER,
            'MailSchedulerWorker',
            'shutdown',
            "Scheduler stopped gracefully. Processed: {$jobsProcessed}, Failed: {$jobsFailed}"
        );
        
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

                    LogUtil::logAction(
                        LogType::MAILSCHEDULER,
                        'MailSchedulerWorker',
                        'processJob',
                        "Successfully sent email to {$job->recipient} using template '{$job->template}'"
                    );
                } else {
                    // Mark as failed
                    $errorMsg = "Failed to send email";
                    MailJob::markAsFailed($job->id, $errorMsg);
                    $jobsFailed++;

                    echo "  ✗ Job #{$job->id} failed: {$errorMsg}\n";

                    LogUtil::logAction(
                        LogType::MAILSCHEDULER,
                        'MailSchedulerWorker',
                        'processJob',
                        "Failed to send email to {$job->recipient}: {$errorMsg}"
                    );
                }
            } catch (Exception $e) {
                // Handle job-specific errors
                $errorMsg = $e->getMessage();
                MailJob::markAsFailed($job->id, $errorMsg);
                $jobsFailed++;

                echo "  ✗ Job #{$job->id} exception: {$errorMsg}\n";

                LogUtil::logAction(
                    LogType::MAILSCHEDULER,
                    'MailSchedulerWorker',
                    'processJob',
                    "Exception processing job #{$job->id}: {$errorMsg}"
                );
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
        
        LogUtil::logAction(
            LogType::MAILSCHEDULER,
            'MailSchedulerWorker',
            'error',
            "Worker loop error: " . $e->getMessage()
        );

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
