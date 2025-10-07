<?php

namespace APP\Models;

use ORM;
use App\Utils\LogType;
use App\Utils\LogUtil;

/**
 * Class Name: DeletionRequest
 *
 * ORM Model for managing GDPR account deletion requests (Art. 17 GDPR).
 * Implements a safe 30-day deletion window with confirmation via email.
 *
 * @package App\Models
 * @author SecStore GDPR Module
 * @version 1.0
 * @since 2025-10-07
 *
 * Changes:
 * - 1.0 (2025-10-07): Created for GDPR compliance.
 */
class DeletionRequest extends ORM
{
    protected static $tableName = 'deletion_requests';

    /**
     * Creates a new deletion request.
     *
     * @param int $userId The user ID
     * @param string $username The username (for logging)
     * @param string $email The user's email
     * @param string $confirmationToken Token for email confirmation
     * @param string $ipAddress Request origin IP
     * @return DeletionRequest|null The created request or null on failure
     */
    public static function createRequest(
        int $userId,
        string $username,
        string $email,
        string $confirmationToken,
        string $ipAddress
    ): ?DeletionRequest {
        try {
            ORM::configure('logging', true);
            
            $request = ORM::for_table(self::$tableName)->create();
            $request->user_id = $userId;
            $request->username = $username;
            $request->email = $email;
            $request->confirmation_token = $confirmationToken;
            $request->status = 'pending';
            $request->ip_address = $ipAddress;
            $request->requested_at = date('Y-m-d H:i:s');
            
            $result = $request->save() ? $request : null;

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(
                    LogType::SQL,
                    'DeletionRequest.php',
                    'createRequest',
                    $lastQuery
                );
            }

            return $result;
        } catch (\PDOException $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'DeletionRequest.php',
                'createRequest',
                'Failed to create deletion request: ' . $e->getMessage()
            );
            return null;
        }
    }

    /**
     * Finds a deletion request by confirmation token.
     *
     * @param string $token The confirmation token
     * @return mixed The request object if found, false otherwise
     */
    public static function findByConfirmationToken(string $token)
    {
        ORM::configure('logging', true);
        
        $request = ORM::for_table(self::$tableName)
            ->where('confirmation_token', $token)
            ->where('status', 'pending')
            ->find_one();

        // Log query
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(
                LogType::SQL,
                'DeletionRequest.php',
                'findByConfirmationToken',
                $lastQuery
            );
        }

        return $request;
    }

    /**
     * Finds a pending deletion request for a user.
     *
     * @param int $userId The user ID
     * @return mixed The request object if found, false otherwise
     */
    public static function findPendingByUserId(int $userId)
    {
        ORM::configure('logging', true);
        
        $request = ORM::for_table(self::$tableName)
            ->where('user_id', $userId)
            ->where_in('status', ['pending', 'confirmed'])
            ->find_one();

        // Log query
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(
                LogType::SQL,
                'DeletionRequest.php',
                'findPendingByUserId',
                $lastQuery
            );
        }

        return $request;
    }

    /**
     * Confirms a deletion request and schedules deletion.
     *
     * @param int $requestId The request ID
     * @param int $daysUntilDeletion Days to wait before actual deletion (default 30)
     * @return bool True if confirmation was successful, false otherwise
     */
    public static function confirmRequest(int $requestId, int $daysUntilDeletion = 30): bool
    {
        try {
            ORM::configure('logging', true);
            
            $request = ORM::for_table(self::$tableName)
                ->where('id', $requestId)
                ->find_one();

            if ($request === false) {
                return false;
            }

            $deletionDate = date('Y-m-d', strtotime("+{$daysUntilDeletion} days"));
            
            $request->status = 'confirmed';
            $request->confirmed_at = date('Y-m-d H:i:s');
            $request->deletion_scheduled_date = $deletionDate;
            
            $result = $request->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(
                    LogType::SQL,
                    'DeletionRequest.php',
                    'confirmRequest',
                    $lastQuery
                );
            }

            return $result;
        } catch (\PDOException $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'DeletionRequest.php',
                'confirmRequest',
                'Failed to confirm request: ' . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Cancels a deletion request.
     *
     * @param int $requestId The request ID
     * @return bool True if cancellation was successful, false otherwise
     */
    public static function cancelRequest(int $requestId): bool
    {
        try {
            ORM::configure('logging', true);
            
            $request = ORM::for_table(self::$tableName)
                ->where('id', $requestId)
                ->find_one();

            if ($request === false) {
                return false;
            }

            $request->status = 'cancelled';
            $result = $request->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(
                    LogType::SQL,
                    'DeletionRequest.php',
                    'cancelRequest',
                    $lastQuery
                );
            }

            return $result;
        } catch (\PDOException $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'DeletionRequest.php',
                'cancelRequest',
                'Failed to cancel request: ' . $e->getMessage()
            );
            return false;
        }
    }

    /**
     * Gets all deletion requests that are due for processing.
     *
     * @return array Array of deletion requests due for deletion
     */
    public static function getDueForDeletion(): array
    {
        ORM::configure('logging', true);
        
        $today = date('Y-m-d');
        
        $requests = ORM::for_table(self::$tableName)
            ->where('status', 'confirmed')
            ->where_lte('deletion_scheduled_date', $today)
            ->find_array();

        // Log query
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(
                LogType::SQL,
                'DeletionRequest.php',
                'getDueForDeletion',
                $lastQuery
            );
        }

        return $requests;
    }

    /**
     * Marks a deletion request as completed.
     *
     * @param int $requestId The request ID
     * @return bool True if marking was successful, false otherwise
     */
    public static function markCompleted(int $requestId): bool
    {
        try {
            ORM::configure('logging', true);
            
            $request = ORM::for_table(self::$tableName)
                ->where('id', $requestId)
                ->find_one();

            if ($request === false) {
                return false;
            }

            $request->status = 'completed';
            $result = $request->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(
                    LogType::SQL,
                    'DeletionRequest.php',
                    'markCompleted',
                    $lastQuery
                );
            }

            return $result;
        } catch (\PDOException $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'DeletionRequest.php',
                'markCompleted',
                'Failed to mark completed: ' . $e->getMessage()
            );
            return false;
        }
    }
}
