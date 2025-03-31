<?php

namespace APP\Controllers;

use App\Models\User;
use Flight;
use RobThree\Auth\TwoFactorAuth;
use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use App\Utils\SessionUtil;
use App\Utils\BruteForceUtil;
use App\Utils\MailUtil;
use App\Utils\LogType;
use App\Utils\LogUtil;

/**
 * Class Name: AuthController
 *
 * Controller Klasse für Methoden die sich auf Authentifizierung/Registrierung und 2FA beziehen
 *
 * @package App\Controllers
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class AuthController
{

    
    /**
     * Renders the registration form.
     */
    public function showRegister()
    {
        Flight::latte()->render('register.latte', [
            'title' => 'Register'
        ]);
    }


    /**
     * Handles user registration by inserting the new user's data into the database.
     * If successful, renders the registration page with a success message.
     * Otherwise, renders the registration page with an error message.
     * It expects 'email', 'username', and 'password' fields in the POST request.
     */
    public function register()
    {
        
        $email = $_POST['email'];
        $user = $_POST['username'];
        $firstname = $_POST['firstname'];
        $lastname = $_POST['lastname'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $userCheck = User::checkIfUserExists($user, $email);

        if ($userCheck === "false") {
            $newUser = User::createUser($user, $email, $firstname, $lastname, 1, $password, 'User');
            if ($newUser !== null) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'SUCCESS: registered new user.', $user->username);
                MailUtil::sendMail($user->email, "SecStore: Welcome to SecStore", "welcome", ['name' => $user->firstname . ' ' . $user->lastname]);
                Flight::latte()->render('register.latte', [
                    'title' => 'Register',
                    'message' => 'Account created successfully!'
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'ERROR: could not create new user.', $user->username);
                Flight::latte()->render('register.latte', [
                    'title' => 'Register',
                    'error' => 'There was an error creating your account!'
                ]);
                return;
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'FAILED: ', $userCheck);
            Flight::latte()->render('register.latte', [
                'title' => 'Register',
                'error' => $userCheck
            ]);
            return;
        }
    }

    
    
    /**
     * Renders the login page.
     *
     * This method is responsible for rendering the login page and providing the
     * necessary data to the template. It expects no parameters to be passed in.
     */
    public function showLogin()
    {
        // prüfen ob benutzer bereits angemeldet ist aber 2fa noch fehlt
        if (SessionUtil::get('2fa_user_id') !== null) {
            Flight::latte()->render('2fa_verify.latte', [
                'title' => 'Login'
            ]);
            return;
        }

        Flight::latte()->render('login.latte', [
            'title' => 'Login',
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
        ]);
        return;
    }

    
    /**
     * Authenticates a user using their email and password.
     *
     * This function expects the following POST parameters:
     * - email
     * - password
     *
     * If the credentials are valid and the account is active, it sets the session
     * for the user and redirects to the dashboard. If the user has an 'Admin' role,
     * a session flag is also set for admin access. In case of invalid credentials
     * or inactive status, an error message is rendered on the login page.
     */
    public function login()
    {
        $username = $_POST['username'];
        $password = $_POST['password'];

        $user = User::findUserByUsername($username);

        if ($user === false) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: username not found.', $username);
            Flight::latte()->render('login.latte', [
                'title' => 'Login',
                'error' => 'Wrong usernamen or password.',
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
            ]);
            return;
        }

        // Brute-Force-Prüfung
        if (BruteForceUtil::isLockedOut($user->email)) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user account locked out for 15min.', $user->email);
            Flight::latte()->render('login.latte', [
                'title' => 'Login',
                'error' => 'Zu viele fehlgeschlagene Versuche. Bitte warte 15 Minuten.',
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
            ]);
            return;
        }

        // check for open password reset token
        if ($user !== false && !empty($user->reset_token)) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user has an open password reset request.', $user->email);
            Flight::latte()->render('login.latte', [
                'title' => 'Login',
                'error' => 'Open password reset request. Please conatct Administrator.',
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
            ]);
            return;
        }

        if ($user !==false && password_verify($password, $user->password)) {
            if ($user->status === 0) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user account deactivated.', $user->email);
                Flight::latte()->render('login.latte', [
                    'title' => 'Login',
                    'error' => 'Account inactive. Please conatct Administrator.',
                    'sessionTimeout' => SessionUtil::getSessionTimeout(),
                ]);
                return;
            }

            // Login erfolgreich -> fehlgeschlagene Versuche zurücksetzen
            BruteForceUtil::resetFailedLogins($user->email);
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'SUCCESS: user logged in successfully.', $user->email);

      
            // prüfen ob user 2fa setup im profil aktiviert hat
            if ($user->mfaStartSetup === 1) {
                User::disableMfaSetupForUser($user->id);
                SessionUtil::set('2fa_user_id', $user->id); // Temporär speichern
                self::enable2FA(true, $user->id);
                return;
            }

            // Prüfen, ob der Nutzer 2FA aktiviert hat
            if ($user->mfaEnabled === 1) {
                SessionUtil::set('2fa_user_id', $user->id); // Temporär speichern
                Flight::redirect('/2fa-verify');
            } elseif ($user->mfaEnabled === 0 && $user->mfaEnforced === 1) {
                SessionUtil::set('2fa_user_id', $user->id); // Temporär speichern
                self::enable2FA();
            } else {
                // SessionUtil::set('id', $user->id);
                // SessionUtil::set('isAdmin', in_array('Admin', explode(',', $user->roles)) ? 1 : 0);
                // SessionUtil::set('username', $user->username);
                // SessionUtil::set('email', $user->email);
                SessionUtil::set('user', $user);
                Flight::redirect('/home');
            }
        } else {
            // Fehlgeschlagener Loginversuch registrieren
            BruteForceUtil::recordFailedLogin($user->email);
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: wrong credentials.', $user->email);

            Flight::latte()->render('login.latte', [
                'title' => 'Login',
                'error' => 'Falsche Zugangsdaten',
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
            ]);
            return;
        }
    }

    public function enable2FA($isMfaStartSetup = false, $usId = null)
    {

        if ($isMfaStartSetup && $usId) {
            User::disableMfaSetupForUser($usId);
            SessionUtil::set('2fa_user_id', $usId);
        }

        $user = User::findUserById(SessionUtil::get('2fa_user_id'));
        if ($user === false) {
            Flight::redirect('/login');
        }
        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $secret = $tfa->createSecret(); // Geheimen Schlüssel generieren
        $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user->username, $secret); // QR-Code generieren

        // Geheimen Schlüssel in der Datenbank speichern
        User::setMfaToken($secret, $user->id);

        // An das Template übergeben
        Flight::latte()->render('enable_2fa.latte', ['title' => '2FA Einrichten', 'user' => $user, 'qrCodeUrl' => $qrCodeUrl, 'secret' => $secret, 'enforced' => $user->mfaEnforced]);
    }

    /**
     * Renders the 2FA verification page.
     *
     * This method renders the 2FA verification page and provides the necessary
     * data to the template. It expects the user ID to be stored in the session
     * and checks that the user exists and has a valid 2FA secret.
     *
     * If the user comes from the enable-2fa page, it also enables 2FA for the
     * user.
     *
     * @param bool $comesFrom2faEnable If true, the user comes from the
     *     enable-2fa page and 2FA is getting enabled for the user.
     */
    public function show2faVerify($comesFrom2faEnable)
    {
        $userId = $_SESSION['2fa_user_id'] ?? null;
        if (!$userId) {
            Flight::redirect('/login');
        }

        $user = User::findUserById($userId);
        if ($user === false || $user->mfaSecret === '') {
            Flight::redirect('/login');
        }

        // set2fa as enabled if the user comes from enable-2fa
        if ($comesFrom2faEnable && $comesFrom2faEnable === 'true') {
            User::enableMfaForUser($user->id);
        }

        Flight::latte()->render('2fa_verify.latte', ['title' => '2FA-Code eingeben']);
    }

    /**
     * Verifies the 2FA code sent by the user.
     *
     * This method expects the 2FA code to be sent via POST request.
     * If the code is valid, it sets the user session and redirects to the
     * dashboard. If the code is invalid, it renders the 2fa_verify template
     * with an error message.
     */
    public function verify2FA()
    {
        $userId = SessionUtil::get('2fa_user_id');
        if (!$userId) {
            Flight::redirect('/login');
        }

        $user = User::findUserById($userId);
        if ($user === false || $user->mfaSecret === '') {
            Flight::redirect('/login');
        }

        $request = Flight::request();
        $otp = $request->data->otp;

        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        if ($tfa->verifyCode($user->mfaSecret, $otp)) {
            // SessionUtil::set('id', $user->id);
            // SessionUtil::set('isAdmin', in_array('Admin', explode(',', $user->roles)) ? 1 : 0);
            // SessionUtil::set('username', $user->username);
            // SessionUtil::set('email', $user->email);
            SessionUtil::Set('user', $user);
            unset($_SESSION['2fa_user_id']); // Session aufräumen
            Flight::redirect('/home');
        } else {
            Flight::latte()->render('2fa_verify.latte', ['title' => '2FA', 'error' => 'Ungültiger Code']);
        }
    }
    
    /**
     * Renders the forgot password page.
     */
    public function showForgotPassword()
    {
        Flight::latte()->render('forgot_password.latte', [
            'title' => 'Forgot Password'
        ]);
        return;
    }

    /**
     * Handles the forgot password form by generating a reset token.
     * If successful, renders the reset password page with the token.
     * Otherwise, renders the forgot password page with an error message.
     * It expects an 'email' field in the POST request.
     */
    public function forgotPassword()
    {
        $email = $_POST['email'];
        $user = User::findUserByEmail($email);

        if ($user === false) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: user by email not found.', $email);
            Flight::latte()->render('forgot_password.latte', [
                'title' => 'Forgot Password',
                'error' => 'E-Mail nicht gefunden'
            ]);
            return;
        }

        if ($user !==false && empty($user->reset_token)) {
            $token = bin2hex(random_bytes(50));
            $erg = User::setResetToken($token, $email);

            if ($erg) {
                $name = trim($user->firstname . ' ' . $user->lastname);
                MailUtil::sendMail($user->email, "SecStore: your password reset request", "pwReset", ['name' => $name, 'token' => $token]);
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'SUCCESS: requested pw reset.', $email);
                Flight::latte()->render('forgot_password.latte', [
                    'title' => 'Reset Password',
                    'message' => 'Mail send. Please take a look into your inbox.)',
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: token could not be saved.', $email);
                Flight::latte()->render('reset_password.latte', [
                    'title' => 'Reset Password',
                    'error' => 'ERROR: Unable to save token!',
                    'token' => ''
                ]);
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: user has an open reset pw request already.', $email);
            Flight::latte()->render('forgot_password.latte', [
                'title' => 'Forgot Password',
                'error' => 'User already has an open pw reset token!'
            ]);
            return;
        }
    }

    
    /**
     * Renders the reset password page with the given token.
     *
     * @param string $token The reset token to be used for password reset.
     */

    public function showResetPassword($token)
    {
        Flight::latte()->render('reset_password.latte', [
            'title' => 'Reset Password',
            'token' => $token
        ]);
        return;
    }

    /**
     * Resets the password of a user with the given token.
     *
     * If the token is valid, the user's password is updated with the given new password.
     * Otherwise, an error is displayed.
     */
    public function resetPassword()
    {
        $token = $_POST['token'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $user = User::findUserByResetToken($token);
        if ($user !== false) {
            $erg = User::setNewPassword($user->id, $new_password);
            if ($erg) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'SUCCESS: saved new password.', $user->username);
                Flight::latte()->render('login.latte', [
                    'title' => 'Login',
                    'message' => 'PW reset successful. Please login.'
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'FAILED: new pw could not be saved.', $user->username);
                Flight::latte()->render('login.latte', [
                    'title' => 'Login',
                    'message' => 'ERROR: new password could not be saved!'
                ]);
                return;
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'FAILED: invalid reset token.', $user->username);
            Flight::latte()->render('reset_password.latte', [
                'title' => 'Reset Password',
                'error' => 'Ungültiger Token!'
            ]);
            return;
        }
    }
    
    /**
     * Updates the profile of the currently logged in user.
     *
     * It expects a valid user session to exist.
     * It expects the following POST parameters:
     * - email
     * - name
     *
     * If the update is successful, it renders the profile page with a success message.
     * Otherwise, it renders the profile page with an error message.
     */
    public function updateProfile()
    {
        $user_id = SessionUtil::get('user')['id'];
        $email = $_POST['email'];
        $name = $_POST['name'];
        $erg = User::updateUserProfile($name, $email, $user_id);
        if ($erg) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'updateProfile', 'SUCCESS: updated user profile.', SessionUtil::get('username'));
            Flight::latte()->render('profile.latte', [
                'title' => 'User Profile',
                'user' => $_POST,
                'message' => 'Profile updated successfully!'
            ]);
            return;
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'updateProfile', 'FAILED: error saving profile.', SessionUtil::get('username'));
            Flight::latte()->render('profile.latte', ['error' => 'Error updating profile']);
            return;
        }
    }
}
