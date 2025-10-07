<?php

namespace APP\Controllers;

use Flight;
use APP\Models\User;
use APP\Models\DeletionRequest;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\SessionUtil;
use App\Utils\TranslationUtil;
use App\Utils\DataExportUtil;
use App\Utils\MailUtil;

/**
 * Class Name: PrivacyController
 *
 * Controller for GDPR-compliant privacy features (Art. 15, 17, 20 GDPR).
 * Handles data export, deletion requests, and user privacy settings.
 *
 * @package App\Controllers
 * @author SecStore GDPR Module
 * @version 1.0
 * @since 2025-10-07
 *
 * Changes:
 * - 1.0 (2025-10-07): Created for GDPR compliance.
 */
class PrivacyController
{
    /**
     * Shows the privacy overview page with GDPR options.
     *
     * @return void
     */
    public function showPrivacyOverview(): void
    {
        $userId = SessionUtil::get('user')['id'] ?? null;
        if ($userId === null) {
            Flight::redirect('/login');
            exit;
        }

        $user = User::findUserById($userId);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        // Check for pending deletion request
        $pendingDeletion = DeletionRequest::findPendingByUserId($userId);

        Flight::latte()->render('privacy/overview.latte', [
            'title' => TranslationUtil::t('privacy.title'),
            'user' => $user,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'pendingDeletion' => $pendingDeletion
        ]);
    }

    /**
     * Exports user data as JSON file (GDPR Art. 15 - Right of Access).
     *
     * @return void
     */
    public function requestDataExport(): void
    {
        $userId = SessionUtil::get('user')['id'] ?? null;
        if ($userId === null) {
            Flight::json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            // Send JSON download
            DataExportUtil::sendJsonDownload($userId);

            // Log action
            $user = User::findUserById($userId);
            LogUtil::logAction(
                LogType::AUDIT,
                'PrivacyController',
                'requestDataExport',
                'User exported their personal data',
                $user !== false ? $user->username : 'unknown'
            );
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'PrivacyController',
                'requestDataExport',
                'Failed to export data: ' . $e->getMessage()
            );

            Flight::json([
                'success' => false,
                'message' => TranslationUtil::t('privacy.export.error')
            ]);
        }
    }

    /**
     * Initiates account deletion request (GDPR Art. 17 - Right to be Forgotten).
     * Sends confirmation email with token.
     *
     * @return void
     */
    public function requestDeletion(): void
    {
        $userId = SessionUtil::get('user')['id'] ?? null;
        if ($userId === null) {
            Flight::json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        $user = User::findUserById($userId);
        if ($user === false) {
            Flight::json(['success' => false, 'message' => 'User not found']);
            return;
        }

        try {
            // Check if there's already a pending request
            $existingRequest = DeletionRequest::findPendingByUserId($userId);
            if ($existingRequest !== false) {
                Flight::json([
                    'success' => false,
                    'message' => TranslationUtil::t('privacy.deletion.alreadyPending')
                ]);
                return;
            }

            // Generate confirmation token
            $confirmationToken = bin2hex(random_bytes(32));

            // Create deletion request
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $request = DeletionRequest::createRequest(
                $userId,
                $user->username,
                $user->email,
                $confirmationToken,
                $ipAddress
            );

            if ($request === null) {
                throw new \Exception('Failed to create deletion request');
            }

            // Send confirmation email
            $this->sendDeletionConfirmationEmail($user, $confirmationToken);

            // Log action
            LogUtil::logAction(
                LogType::SECURITY,
                'PrivacyController',
                'requestDeletion',
                'Account deletion requested - confirmation email sent',
                $user->username
            );

            Flight::json([
                'success' => true,
                'message' => TranslationUtil::t('privacy.deletion.emailSent')
            ]);
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'PrivacyController',
                'requestDeletion',
                'Failed to request deletion: ' . $e->getMessage()
            );

            Flight::json([
                'success' => false,
                'message' => TranslationUtil::t('privacy.deletion.error')
            ]);
        }
    }

    /**
     * Confirms account deletion via email token.
     *
     * @param string $token Confirmation token from email link
     * @return void
     */
    public function confirmDeletion(string $token): void
    {
        try {
            $request = DeletionRequest::findByConfirmationToken($token);

            if ($request === false) {
                Flight::latte()->render('privacy/deletion-invalid.latte', [
                    'title' => TranslationUtil::t('privacy.deletion.invalidTitle'),
                    'message' => TranslationUtil::t('privacy.deletion.invalidToken')
                ]);
                return;
            }

            // Confirm the request and schedule deletion (30 days default)
            $confirmed = DeletionRequest::confirmRequest($request->id, 30);

            if ($confirmed) {
                // Log action
                LogUtil::logAction(
                    LogType::SECURITY,
                    'PrivacyController',
                    'confirmDeletion',
                    "Account deletion confirmed - scheduled for {$request->deletion_scheduled_date}",
                    $request->username
                );

                // Log user out
                SessionUtil::destroy();

                Flight::latte()->render('privacy/deletion-confirmed.latte', [
                    'title' => TranslationUtil::t('privacy.deletion.confirmedTitle'),
                    'scheduledDate' => $request->deletion_scheduled_date,
                    'message' => TranslationUtil::t('privacy.deletion.confirmed')
                ]);
            } else {
                throw new \Exception('Failed to confirm deletion request');
            }
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'PrivacyController',
                'confirmDeletion',
                'Failed to confirm deletion: ' . $e->getMessage()
            );

            Flight::latte()->render('privacy/deletion-invalid.latte', [
                'title' => TranslationUtil::t('privacy.deletion.errorTitle'),
                'message' => TranslationUtil::t('privacy.deletion.error')
            ]);
        }
    }

    /**
     * Cancels a pending deletion request.
     *
     * @return void
     */
    public function cancelDeletion(): void
    {
        $userId = SessionUtil::get('user')['id'] ?? null;
        if ($userId === null) {
            Flight::json(['success' => false, 'message' => 'Unauthorized']);
            return;
        }

        try {
            $request = DeletionRequest::findPendingByUserId($userId);

            if ($request === false) {
                Flight::json([
                    'success' => false,
                    'message' => TranslationUtil::t('privacy.deletion.notFound')
                ]);
                return;
            }

            $cancelled = DeletionRequest::cancelRequest($request->id);

            if ($cancelled) {
                $user = User::findUserById($userId);
                LogUtil::logAction(
                    LogType::AUDIT,
                    'PrivacyController',
                    'cancelDeletion',
                    'Account deletion request cancelled',
                    $user !== false ? $user->username : 'unknown'
                );

                Flight::json([
                    'success' => true,
                    'message' => TranslationUtil::t('privacy.deletion.cancelled')
                ]);
            } else {
                throw new \Exception('Failed to cancel deletion request');
            }
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'PrivacyController',
                'cancelDeletion',
                'Failed to cancel deletion: ' . $e->getMessage()
            );

            Flight::json([
                'success' => false,
                'message' => TranslationUtil::t('privacy.deletion.error')
            ]);
        }
    }

    /**
     * Sends deletion confirmation email to user.
     *
     * @param mixed $user User object
     * @param string $confirmationToken Token for email link
     * @return void
     */
    private function sendDeletionConfirmationEmail($user, string $confirmationToken): void
    {
        $confirmationLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
            . "://" . $_SERVER['HTTP_HOST']
            . "/privacy/confirm-deletion/" . $confirmationToken;

        $subject = TranslationUtil::t('privacy.deletion.email.subject');
        $body = TranslationUtil::t('privacy.deletion.email.body', [
            'username' => $user->username,
            'confirmationLink' => $confirmationLink
        ]);

        MailUtil::sendMail(
            $user->email,
            $subject,
            $body
        );

        LogUtil::logAction(
            LogType::MAIL,
            'PrivacyController',
            'sendDeletionConfirmationEmail',
            "Deletion confirmation email sent to {$user->email}",
            $user->username
        );
    }

    /**
     * Processes due deletion requests (called by cron/admin).
     * Deletes accounts that are past their scheduled deletion date.
     *
     * @return void
     */
    public static function processDueDeletions(): void
    {
        try {
            $dueRequests = DeletionRequest::getDueForDeletion();

            foreach ($dueRequests as $request) {
                // Perform GDPR-compliant deletion
                $deleted = User::gdprCompleteDelete($request->user_id);

                if ($deleted) {
                    // Mark request as completed
                    DeletionRequest::markCompleted($request->id);

                    LogUtil::logAction(
                        LogType::SECURITY,
                        'PrivacyController',
                        'processDueDeletions',
                        "GDPR deletion completed for user {$request->username} (ID: {$request->user_id})"
                    );
                } else {
                    LogUtil::logAction(
                        LogType::ERROR,
                        'PrivacyController',
                        'processDueDeletions',
                        "Failed to complete GDPR deletion for user {$request->username} (ID: {$request->user_id})"
                    );
                }
            }
        } catch (\Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'PrivacyController',
                'processDueDeletions',
                'Failed to process due deletions: ' . $e->getMessage()
            );
        }
    }
}
