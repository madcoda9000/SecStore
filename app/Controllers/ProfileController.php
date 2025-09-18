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
use Exception;

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
     * Shows the profile of the currently logged in user.
     */
    public function showProfile()
    {
        $user = SessionUtil::get('user');
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $qrCodeUrl = '';
        if ($user->mfaSecret) {
            $secret = $user->mfaSecret; // Geheimen Schlüssel generieren
            $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user->username, $secret); // QR-Code generieren
        }

        Flight::latte()->render('profile.latte', [
            'title' => 'User Profile',
            'user' => $user,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'qrCodeUrl' => $qrCodeUrl,
        ]);
        return;
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

        // pq requirements check
        $oldPW = $_POST['old_password'] ?? null;
        $newPW = $_POST['new_password'] ?? null;
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
        $new_mail = $_POST['new_email'] ?? null;

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

        User::disableAndReset2FA($user->id);
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
        if (
            !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
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
