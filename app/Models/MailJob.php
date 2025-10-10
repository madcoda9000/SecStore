<?php

namespace App\Models;

use ORM;
use Exception;

/**
 * Class Name: MailJob
 *
 * Model for managing mail jobs in the queue
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-01-10
 *
 * Changes:
 * - 1.0 (2025-01-10): Initial creation
 */
class MailJob
{
    /**
     * Creates a new mail job in the queue
     *
     * @param string $recipient Email recipient
     * @param string $subject Email subject
     * @param string $template Template name
     * @param array $templateData Data for template rendering
     * @param int $maxAttempts Maximum retry attempts
     * @return bool|object Returns the created job or false on failure
     */
    public static function createJob(
        string $recipient,
        string $subject,
        string $template,
        array $templateData = [],
        int $maxAttempts = 3
    ) {
        try {
            $job = ORM::for_table('mail_jobs')->create();
            $job->status = 'pending';
            $job->recipient = $recipient;
            $job->subject = $subject;
            $job->template = $template;
            $job->template_data = json_encode($templateData);
            $job->max_attempts = $maxAttempts;
            $job->attempts = 0;
            $job->scheduled_at = date('Y-m-d H:i:s');
            
            if ($job->save()) {
                return $job;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Failed to create mail job: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets all pending jobs that are ready to be processed
     *
     * @param int $limit Maximum number of jobs to return
     * @return array Array of pending job objects
     */
    public static function getPendingJobs(int $limit = 10): array
    {
        try {
            return ORM::for_table('mail_jobs')
                ->where('status', 'pending')
                ->where_raw('scheduled_at <= NOW()')
                ->where_raw('attempts < max_attempts')
                ->order_by_asc('scheduled_at')
                ->limit($limit)
                ->find_many();
        } catch (Exception $e) {
            error_log("Failed to get pending jobs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marks a job as processing
     *
     * @param int $jobId Job ID
     * @return bool Success status
     */
    public static function markAsProcessing(int $jobId): bool
    {
        try {
            $job = ORM::for_table('mail_jobs')->find_one($jobId);
            if (!$job) {
                return false;
            }
            
            $job->status = 'processing';
            $job->started_at = date('Y-m-d H:i:s');
            $job->attempts = $job->attempts + 1;
            
            return $job->save();
        } catch (Exception $e) {
            error_log("Failed to mark job as processing: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marks a job as completed
     *
     * @param int $jobId Job ID
     * @return bool Success status
     */
    public static function markAsCompleted(int $jobId): bool
    {
        try {
            $job = ORM::for_table('mail_jobs')->find_one($jobId);
            if (!$job) {
                return false;
            }
            
            $job->status = 'completed';
            $job->completed_at = date('Y-m-d H:i:s');
            
            return $job->save();
        } catch (Exception $e) {
            error_log("Failed to mark job as completed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Marks a job as failed and schedules retry if attempts remain
     *
     * @param int $jobId Job ID
     * @param string $error Error message
     * @return bool Success status
     */
    public static function markAsFailed(int $jobId, string $error = ''): bool
    {
        try {
            $job = ORM::for_table('mail_jobs')->find_one($jobId);
            if (!$job) {
                return false;
            }
            
            $job->last_error = $error;
            
            // If max attempts reached, mark as failed permanently
            if ($job->attempts >= $job->max_attempts) {
                $job->status = 'failed';
            } else {
                // Schedule retry with exponential backoff
                $job->status = 'pending';
                $backoffSeconds = pow(2, $job->attempts) * 60; // 2^n minutes
                $job->scheduled_at = date('Y-m-d H:i:s', strtotime("+{$backoffSeconds} seconds"));
            }
            
            return $job->save();
        } catch (Exception $e) {
            error_log("Failed to mark job as failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Gets job statistics
     *
     * @return array Statistics array
     */
    public static function getStatistics(): array
    {
        try {
            $pending = ORM::for_table('mail_jobs')->where('status', 'pending')->count();
            $processing = ORM::for_table('mail_jobs')->where('status', 'processing')->count();
            $completed = ORM::for_table('mail_jobs')->where('status', 'completed')->count();
            $failed = ORM::for_table('mail_jobs')->where('status', 'failed')->count();
            
            return [
                'pending' => $pending,
                'processing' => $processing,
                'completed' => $completed,
                'failed' => $failed,
                'total' => $pending + $processing + $completed + $failed
            ];
        } catch (Exception $e) {
            error_log("Failed to get job statistics: " . $e->getMessage());
            return [
                'pending' => 0,
                'processing' => 0,
                'completed' => 0,
                'failed' => 0,
                'total' => 0
            ];
        }
    }

    /**
     * Gets paginated list of jobs with optional status filter
     *
     * @param int $page Page number
     * @param int $pageSize Items per page
     * @param string|null $status Optional status filter
     * @return array Jobs and total count
     */
    public static function getJobsPaged(int $page = 1, int $pageSize = 20, ?string $status = null): array
    {
        try {
            $offset = ($page - 1) * $pageSize;
            
            $query = ORM::for_table('mail_jobs');
            if ($status !== null && $status !== '') {
                $query->where('status', $status);
            }
            
            $totalJobs = $query->count();
            
            $jobs = ORM::for_table('mail_jobs');
            if ($status !== null && $status !== '') {
                $jobs->where('status', $status);
            }
            
            $jobs = $jobs->order_by_desc('created_at')
                ->limit($pageSize)
                ->offset($offset)
                ->find_many();
            
            return [
                'jobs' => $jobs,
                'totalJobs' => $totalJobs,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => ceil($totalJobs / $pageSize)
            ];
        } catch (Exception $e) {
            error_log("Failed to get paginated jobs: " . $e->getMessage());
            return [
                'jobs' => [],
                'totalJobs' => 0,
                'page' => $page,
                'pageSize' => $pageSize,
                'totalPages' => 0
            ];
        }
    }

    /**
     * Deletes a job by ID
     *
     * @param int $jobId Job ID
     * @return bool Success status
     */
    public static function deleteJob(int $jobId): bool
    {
        try {
            $job = ORM::for_table('mail_jobs')->find_one($jobId);
            if (!$job) {
                return false;
            }
            
            // Only allow deletion of pending or failed jobs
            if (!in_array($job->status, ['pending', 'failed'])) {
                return false;
            }
            
            $job->delete();
            return true;
        } catch (Exception $e) {
            error_log("Failed to delete job: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cleans up old completed jobs
     *
     * @param int $daysOld Number of days to keep
     * @return int Number of deleted jobs
     */
    public static function cleanupOldJobs(int $daysOld = 7): int
    {
        try {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysOld} days"));
            
            $jobs = ORM::for_table('mail_jobs')
                ->where('status', 'completed')
                ->where_lt('completed_at', $cutoffDate)
                ->find_many();
            
            $count = 0;
            foreach ($jobs as $job) {
                if ($job->delete()) {
                    $count++;
                }
            }
            
            return $count;
        } catch (Exception $e) {
            error_log("Failed to cleanup old jobs: " . $e->getMessage());
            return 0;
        }
    }
}
