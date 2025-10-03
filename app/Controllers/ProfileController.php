<?php

namespace APP\Controllers;

use Flight;
use App\Models\User;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\SessionUtil;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use App\Utils\TranslationUtil;
use App\Utils\InputValidator;
use App\Utils\BackupCodeUtil;
use Exception;
use InvalidArgumentException;

/**
 * Class Name: DashboardController
 *
 * Controller Klasse für Methoden die für das Uuser Profil benötigt werden
 *
 * @package App\Controllers
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class ProfileController
{

    /**
     * Regenerates backup codes for the current user.
     *
     * Generates new backup codes, invalidating all previous codes.
     * Returns the new codes for display to the user.
     *
     * @return void
     */
    public function regenerateBackupCodes()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        // Generate new backup codes
        $backupCodes = BackupCodeUtil::generateBackupCodes();
        $hashedCodes = BackupCodeUtil::hashBackupCodes($backupCodes);

        // Save to database
        $result = User::setBackupCodes($user->id, $hashedCodes);

        // Log action
        LogUtil::logAction(
            LogType::AUDIT,
            'ProfileController',
            'regenerateBackupCodes',
            'SUCCESS: Regenerated backup codes for user',
            $user->username
        );

        if ($result) {
            // Return codes as JSON for AJAX request
            if (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
                strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
            ) {
                Flight::json([
                    'success' => true,
                    'codes' => $backupCodes,
                    'message' => TranslationUtil::t('profile.backupcodes.regenerate.success')
                ]);
                return;
            }

            // For non-AJAX, render profile with codes
            $this->showProfileWithBackupCodes($backupCodes);
        } else {
            $this->handleResponse(false, TranslationUtil::t('profile.backupcodes.regenerate.error'));
        }
    }

    /**
     * Shows the profile page with newly generated backup codes.
     *
     * @param array $newBackupCodes Array of plain-text backup codes to display
     * @return void
     */
    private function showProfileWithBackupCodes(array $newBackupCodes)
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $qrCodeUrl = null;

        if ($user->mfaSecret !== '') {
            $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user->username, $user->mfaSecret);
        }

        $remainingCodes = User::countRemainingBackupCodes($user->id);

        Flight::latte()->render('profile.latte', [
            'title' => TranslationUtil::t('profile.title'),
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'newBackupCodes' => $newBackupCodes,
            'remainingBackupCodes' => $remainingCodes,
            'showBackupCodesModal' => true
        ]);
    }

    /**
     * Gets the count of remaining backup codes for the current user.
     *
     * @return void
     */
    public function getBackupCodesCount()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::json(['success' => false, 'message' => 'User not found']);
            return;
        }

        $remainingCodes = User::countRemainingBackupCodes($user->id);

        Flight::json([
            'success' => true,
            'count' => $remainingCodes
        ]);
    }

    /**
     * Renders the profile page with 2FA and backup codes information.
     *
     * This method renders the profile page and provides the necessary
     * data to the template. It expects the user to be logged in.
     *
     * @return void
     */
    public function showProfile()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $qrCodeUrl = null;

        if ($user->mfaSecret !== '') {
            $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user->username, $user->mfaSecret);
        }

        // Get remaining backup codes count
        $remainingBackupCodes = 0;
        if ($user->mfaEnabled === 1 && $user->mfaSecret !== '') {
            $remainingBackupCodes = User::countRemainingBackupCodes($user->id);
        }

        // Check for low backup codes warning from session
        $backupCodesWarning = null;
        if (SessionUtil::get('backup_codes_low_warning') !== null) {
            $backupCodesWarning = SessionUtil::get('backup_codes_low_warning');
            SessionUtil::remove('backup_codes_low_warning');
        }

        Flight::latte()->render('profile.latte', [
            'title' => TranslationUtil::t('profile.title'),
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'remainingBackupCodes' => $remainingBackupCodes,
            'backupCodesWarning' => $backupCodesWarning
        ]);
    }



    /**
     * Shows the profile of the currently logged in user and handles the password change form.
     * If the form is submitted, it checks if the current password is correct and if the new password is valid.
     * If the new password is valid, it updates the password in the database and logs the change.
     * If not, it renders the profile page again with an error message.
     * @return void
     */
    public function profileChangePassword()
    {
        $user_id = SessionUtil::get('user')['id'];
        $user = User::findUserById($user_id);

        if ($user === false) {
            Flight::redirect('/login'); // Falls kein Nutzer gefunden wurde, zum Login weiterleiten
            return;
        }

        try {
            try {
                $validated = InputValidator::validateAndSanitize(
                    InputValidator::getPasswordChangeRules(),
                    $_POST
                );

                $oldPW = $validated['current_password'];
                $newPW = $validated['new_password'];
                $error = null;

                if (!$oldPW || !$newPW) {
                    $error = 'All fields are required!';
                } elseif (!password_verify($oldPW, $user->password)) {
                    $error = 'Current password is incorrect!';
                } elseif (strlen($newPW) < 14 || !preg_match('/[A-Z]/', $newPW) || !preg_match('/\d/', $newPW)) {
                    $error = 'Password must be at least 14 characters long and contain an uppercase letter and a number.';
                } elseif (password_verify($newPW, $user->password)) {
                    $error = 'New password must be different from the current password!';
                }

                if ($error) {
                    Flight::latte()->render('profile.latte', [
                        'title' => 'Profile',
                        'user' => $user,
                        'error' => $error,
                        'sessionTimeout' => SessionUtil::getRemainingTime(),
                    ]);
                    return;
                }

                // Neues Passwort setzen
                $pwhash = password_hash($newPW, PASSWORD_DEFAULT);
                if (!User::setNewPassword($user_id, $pwhash)) {
                    Flight::latte()->render('profile.latte', [
                        'title' => 'Profile',
                        'user' => $user,
                        'error' => 'Failed to update password!',
                        'sessionTimeout' => SessionUtil::getRemainingTime(),
                    ]);
                    return;
                }

                //log pw change
                LogUtil::logAction(LogType::AUDIT, 'ProfileController', 'profileChangePassword', 'SUCCESS: Password changed.', $user->username);

                Flight::latte()->render('profile.latte', [
                    'title' => 'Profile',
                    'user' => $user,
                    'success' => 'Password changed successfully!',
                    'sessionTimeout' => SessionUtil::getRemainingTime(),
                ]);
            } catch (InvalidArgumentException $e) {
                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            Flight::latte()->render('profile.latte', [
                'title' => 'Profile',
                'user' => $user ?? 'anonymous',
                'error' => $e->getMessage(),
                'sessionTimeout' => SessionUtil::getRemainingTime(),
            ]);
            return;
        }
    }

    /**
     * Handles the email change form submission.
     * Validates the new email address and updates it in the database.
     * If successful, redirects to the logout page.
     * If not, renders the profile page with an error message.
     *
     * @return void
     */
    public function profileChangeEmail()
    {
        $user_id = SessionUtil::get('user')['id'];
        $user = User::findUserById($user_id);

        try {
            try {
                $validated = InputValidator::validateAndSanitize(
                    InputValidator::getEmailChangeRules(),
                    $_POST
                );

                $new_mail = $validated['new_email'];

                if ($user === false) {
                    Flight::redirect('/login'); // Falls kein Nutzer gefunden wurde, zum Login weiterleiten
                    return;
                }

                if (!$new_mail) {
                    Flight::latte()->render('profile.latte', [
                        'title' => 'Profile',
                        'user' => $user,
                        'error' => 'The new email address field must be filled!',
                        'sessionTimeout' => SessionUtil::getRemainingTime(),
                    ]);
                    return;
                }

                if (!filter_var($new_mail, FILTER_VALIDATE_EMAIL)) {
                    Flight::latte()->render('profile.latte', [
                        'title' => 'Profile',
                        'user' => $user,
                        'error' => 'Please enter a valid email address!',
                        'sessionTimeout' => SessionUtil::getRemainingTime(),
                    ]);
                    return;
                }

                $erg = User::changeEmailAddress($user->id, $new_mail);

                if ($erg === false) {
                    Flight::latte()->render('profile.latte', [
                        'title' => 'Profile',
                        'user' => $user,
                        'error' => 'There was an error saving your new email address!',
                        'sessionTimeout' => SessionUtil::getRemainingTime(),
                    ]);
                    return;
                } else {
                    LogUtil::logAction(LogType::AUDIT, 'ProfileController', 'changeEmailAddress', 'SUCCESS: changed email adress to ' . $new_mail, $user->username);
                    Flight::redirect('/logout');
                }
            } catch (InvalidArgumentException $e) {
                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            Flight::latte()->render('profile.latte', [
                'title' => 'Profile',
                'user' => $user ?? 'anonymous',
                'error' => $e->getMessage(),
                'sessionTimeout' => SessionUtil::getRemainingTime(),
            ]);
            return;
        }
    }


    /**
     * Disables MFA for the current user.
     *
     * @return bool True if MFA was successfully disabled, false otherwise.
     */
    public function disable2FA()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        return User::disableMfaForUser($user->id);
    }

    /**
     * Enables MFA for the current user.
     *
     * This method enables MFA for the current user. If the user is not found,
     * the user is redirected to the login page.
     *
     * @return bool True if MFA was enabled successfully, false otherwise.
     */
    public function enable2FA()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
            exit;
        }

        return User::enableMfaForUser($user->id);
    }



    /**
     * Initiates the 2FA setup process for the current user.
     */
    public static function initiate2faSetup()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            return self::handleResponse(false, 'User not found. Please login again.');
        }

        $erg = User::enableMfaSetupForUser($user->id);

        if ($erg) {
            return self::handleResponse(true, '2FA setup initiated successfully.');
        } else {
            return self::handleResponse(false, 'Error initiating 2FA setup. Please contact your Administrator.');
        }
    }

    /**
     * Disables MFA and resets the 2FA secret for the current user.
     *
     * This method disables MFA for the current user and resets the 2FA secret.
     * If the user is not found, the user is redirected to the login page.
     *
     * @return void
     */
    public function disableAndReset2FA()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if ($user === false) {
            Flight::redirect('/login');
        }

        // Disable 2FA and reset secret
        User::disableAndReset2FA($user->id);

        // Clear backup codes
        User::clearBackupCodes($user->id);

        // Log action
        LogUtil::logAction(
            LogType::AUDIT,
            'ProfileController',
            'disableAndReset2FA',
            'SUCCESS: Disabled 2FA and cleared backup codes for user',
            $user->username
        );

        Flight::redirect('/logout');
    }

    /**
     * Logs out the current user by destroying the session and redirecting to the login page.
     */

    public function logout()
    {
        SessionUtil::destroy();
        Flight::redirect('/login');
    }

    /**
     * Helper method to handle the response for AJAX and non-AJAX requests.
     */
    private static function handleResponse(bool $success, ?string $errorMessage = null)
    {
        if (!empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
        ) {
            Flight::json(["success" => $success, "message" => $errorMessage]);
            return;
        }

        // Falls kein AJAX-Request -> Redirect wie bisher
        //if ($success) {
        //    Flight::redirect('/logout');
        //} else {
        //    // Fehlerbehandlung für Nicht-AJAX-Anfragen
        //    throw new Exception($errorMessage ?? 'Error initiating 2FA setup.');
        //}
    }
}
