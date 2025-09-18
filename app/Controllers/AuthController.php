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
use App\Utils\LdapUtil;
use App\Utils\TranslationUtil;
use App\Utils\InputValidator;
use InvalidArgumentException;

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
        // Explizit sicherstellen, dass Session aktiv ist
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // CSRF Token explizit generieren
        $csrfToken = SessionUtil::getCsrfToken();

        // Debug-Info loggen
        LogUtil::logAction(
            LogType::SECURITY,
            'AuthController',
            'showRegister',
            'Session ID: ' . session_id() .
                ', CSRF Token generated: ' . (!empty($csrfToken) ? 'YES' : 'NO')
        );

        $connOkay = MailUtil::checkConnection();
        if (!$connOkay) {
            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('register.msg.val7'),
                'smtpInvalid' => true
            ]);
            return;
        } else {
            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'lang' => Flight::get('lang'),
                'smtpInvalid' => false
            ]);
            return;
        }
    }

    /**
     * Handles user registration by inserting the new user's data into the database.
     * If successful, renders the registration page with a success message.
     * Otherwise, renders the registration page with an error message.
     * It expects 'email', 'username', and 'password' fields in the POST request.
     */
    public function register()
    {

        $email = "";
        $user = "";
        $firstname = "";
        $lastname = "";;
        $password = "";

        try {
            $validated = InputValidator::validateRegistration($_POST['username'], $_POST['password'], $_POST['email'], $_POST['firstname'], $_POST['lastname']);
            $user = $validated['username'];
            $password = password_hash($validated['password'], PASSWORD_DEFAULT);
            $email = $validated['email'];
            $firstname = $validated['firstName'];
            $lastname = $validated['lastName'];
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', $e->getMessage(), $user);
            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'error' => $e->getMessage(),
                'lang' => Flight::get('lang'),
            ]);
            return;
        }

        $userCheck = User::checkIfUserExists($user, $email);

        if ($userCheck === "false") {
            $newUser = User::createUser($user, $email, $firstname, $lastname, 1, $password, 'User');
            if ($newUser !== null) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'SUCCESS: registered new user.', $user);
                MailUtil::sendMail($email, "SecStore: Welcome to SecStore", "welcome", ['name' => $firstname . ' ' . $lastname]);
                Flight::latte()->render('register.latte', [
                    'title' => TranslationUtil::t('register.title'),
                    'message' => TranslationUtil::t('register.msg.success'),
                    'lang' => Flight::get('lang'),
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'ERROR: could not create new user.', $user);
                Flight::latte()->render('register.latte', [
                    'title' => TranslationUtil::t('register.title'),
                    'error' => TranslationUtil::t('register.msg,errorGeneral'),
                    'lang' => Flight::get('lang'),
                ]);
                return;
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'FAILED: ', $userCheck);
            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'error' => $userCheck,
                'lang' => Flight::get('lang'),
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

        // Explizit sicherstellen, dass Session aktiv ist
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // CSRF Token explizit generieren
        $csrfToken = SessionUtil::getCsrfToken();

        // Debug-Info loggen
        LogUtil::logAction(
            LogType::SECURITY,
            'AuthController',
            'showLogin',
            'Session ID: ' . session_id() .
                ', CSRF Token generated: ' . (!empty($csrfToken) ? 'YES' : 'NO')
        );

        // prüfen ob benutzer bereits angemeldet ist aber 2fa noch fehlt
        if (SessionUtil::get('2fa_user_id') !== null) {
            Flight::latte()->render('2fa_verify.latte', [
                'title' => TranslationUtil::t('2fa_verify.title'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
            ]);
            return;
        }

        $configFile = "../config.php";
        $config = include $configFile;

        Flight::latte()->render('login.latte', [
            "application" => $config["application"],
            'title' => TranslationUtil::t('login.title'),
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
            'lang' => Flight::get('lang'),
        ]);
        return;
    }

    /**
     * Renders the login page with an error message.
     *
     * This method is responsible for rendering the login page with an error
     * message and providing the necessary data to the template. It expects a
     * message key to be passed in, which will be used to retrieve the
     * appropriate error message from the translation utility.
     *
     * @param string $messageKey The key for the error message to be displayed.
     */
    private function renderLoginError($messageKey, $config)
    {
        Flight::latte()->render('login.latte', [
            'title' => TranslationUtil::t('login.title'),
            'error' => TranslationUtil::t($messageKey),
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
            'lang' => Flight::get('lang'),
            "application" => $config["application"],
        ]);
    }



    /**
     * Handles the login process for a user.
     *
     * This method performs the following steps:
     * 1. Retrieves the username and password from the POST request.
     * 2. Attempts to find the user by their username.
     * 3. Checks for various conditions that may prevent login:
     *    - If the username is not found.
     *    - If the user account is locked due to brute-force attempts.
     *    - If the user has an open password reset request.
     * 4. Authenticates the user:
     *    - If LDAP is enabled for the user, authentication is performed against the LDAP server.
     *    - Otherwise, the password is verified against the stored hash.
     * 5. Handles successful authentication:
     *    - Ensures the user account is active.
     *    - Resets failed login attempts.
     *    - Logs the successful login.
     *    - Checks if the user has enabled or is required to enable 2FA (Two-Factor Authentication).
     *      - If 2FA setup is in progress, it redirects to the setup process.
     *      - If 2FA is enabled, it redirects to the 2FA verification process.
     *      - If 2FA is enforced but not enabled, it redirects to enable 2FA.
     *      - Otherwise, it sets the user session and redirects to the home page.
     * 6. Handles failed authentication:
     *    - Records the failed login attempt.
     *    - Logs the failed login attempt.
     *    - Renders the login page with an appropriate error message.
     *
     * Logging:
     * - Logs various actions such as failed login attempts, account lockouts, and successful logins.
     *
     * Error Handling:
     * - Provides user-friendly error messages for different failure scenarios.
     *
     * Security:
     * - Implements brute-force protection.
     * - Supports LDAP authentication.
     * - Enforces 2FA if required.
     *
     * @return void
     */
    public function login()
    {

        $configFile = "../config.php";
        $config = include $configFile;

        try {
            $validated = InputValidator::validateLogin($_POST['username'], $_POST['password']);
            $username = $validated['username'];
            $password = $validated['password'];
            // ... rest der Login-Logik
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', $e->getMessage(), "UNKNOWN_USER");
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => $e->getMessage(),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                "application" => $config["application"],
            ]);
            return;
        }

        $user = User::findUserByUsername($username);

        if ($user === false) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: username not found.', $username);
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error6'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                "application" => $config["application"],
            ]);
            return;
        }

        // Brute-Force-Prüfung
        if (BruteForceUtil::isLockedOut($user->email)) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user account locked out for 15min.', $user->email);
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('login.msg.error5'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                "application" => $config["application"],
            ]);
            return;
        }

        // check for open password reset token
        if ($user !== false && !empty($user->reset_token)) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user has an open password reset request.', $user->email);
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('login.msg.error4'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                "application" => $config["application"],
            ]);
            return;
        }

        // check if we have to authenticate against an ldap server
        $isAuthenticated = false;
        if ($user->ldapEnabled === 1) {
            $isAuthenticated = LdapUtil::authenticate($username, $password);
            if ($isAuthenticated === false) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: ' . $user->username . ': ldap authentication failed.', $user->email);
                Flight::latte()->render('login.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'error' => TranslationUtil::t('login.msg.error3'),
                    'sessionTimeout' => SessionUtil::getSessionTimeout(),
                    "application" => $config["application"],
                ]);
                return;
            }
        } else {
            $isAuthenticated = password_verify($password, $user->password);
        }

        if (!$user || !$isAuthenticated) {
            BruteForceUtil::recordFailedLogin($username); // Use username, not email
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'Failed login attempt', $username);
            $this->renderLoginError('login.msg.error1', $config); // GLEICHE Fehlermeldung
            return;
        }


        if ($user !== false && $isAuthenticated === true) {
            // prüfen ob user account aktiv ist
            if ($user->status === 0) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user account deactivated.', $user->email);
                Flight::latte()->render('login.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'error' => TranslationUtil::t('login.msg.error2'),
                    'sessionTimeout' => SessionUtil::getSessionTimeout(),
                    "application" => $config["application"],
                ]);
                return;
            }

            // TOKEN INVALIDIEREN
            if (!empty($user->reset_token)) {
                $user->reset_token = '';
                $user->reset_token_expires = null;
                $user->save();
                LogUtil::logAction(LogType::SECURITY, 'AuthController', 'login', 'Reset token invalidated after login', $user->username);
            }

            // Login erfolgreich -> fehlgeschlagene Versuche zurücksetzen
            BruteForceUtil::resetFailedLogins($user->email);

            // SCHRITT 1: User-Daten in Session setzen (BEVOR Session regeneriert wird)
            SessionUtil::set('user', $user);

            // SCHRITT 2: Session regenerieren für Security (Session Fixation Prevention)
            if (session_regenerate_id(true)) {
                SessionUtil::set('last_regenerated', time());
                LogUtil::logAction(LogType::SECURITY, 'AuthController', 'login', 'Session regenerated after successful login', $user->username);
            } else {
                LogUtil::logAction(LogType::ERROR, 'AuthController', 'login', 'Failed to regenerate session ID', $user->username);
            }

            // SCHRITT 3: Neue Session-ID in Datenbank speichern (NACH Regeneration)
            User::setActiveSessionId($user->id);
            User::updateLastKnownIp($user->id, $_SERVER['REMOTE_ADDR']);

            // Login erfolgreich loggen
            if ($user->ldapEnabled === 1) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'SUCCESS: ' . $user->username . ': LDAP-Login successful.', $user->username);
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'SUCCESS: ' . $user->username . ': DB-Login successful.', $user->username);
            }

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
                SessionUtil::set('user', $user);
                Flight::redirect('/home');
            }
        } else {
            // Fehlgeschlagener Loginversuch registrieren
            BruteForceUtil::recordFailedLogin($user->email);
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: wrong credentials.', $user->email);

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                "application" => $config["application"],
            ]);
            return;
        }
    }

    /**
     * Enables 2FA for the user.
     *
     * This method generates a new secret and QR code for the user, and stores
     * the secret in the database. It also renders the enable_2fa template with
     * the necessary data.
     *
     * @param bool $isMfaStartSetup If true, the user is starting the 2FA setup
     *     process.
     * @param int|null $usId The user ID to be used for enabling 2FA.
     */
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
        Flight::latte()->render('enable_2fa.latte', [
            'title' => TranslationUtil::t('2fasetup.title'),
            'lang' => Flight::get('lang'),
            'user' => $user,
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
            'enforced' => $user->mfaEnforced
        ]);
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

        Flight::latte()->render('2fa_verify.latte', [
            'title' => TranslationUtil::t('2faverify.title'),
            'lang' => Flight::get('lang'),
        ]);
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
        $otp = "";

        try {
            $validated = InputValidator::validateOtp($_POST['otp']);
            $otp = $validated['otp'];
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'verify2FA', $e->getMessage(), $user->username);
            Flight::latte()->render('2fa_verify.latte', [
                'title' => TranslationUtil::t('2faverify.title'),
                'lang' => Flight::get('lang'),
                'error' => $e->getMessage(),
            ]);
            return;
        }

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
            Flight::latte()->render('2fa_verify.latte', [
                'title' => TranslationUtil::t('2faverify.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('2faverify.msg.error1'),
            ]);
        }
    }

    /**
     * Renders the forgot password page.
     */
    public function showForgotPassword()
    {
        // Explizit sicherstellen, dass Session aktiv ist
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // CSRF Token explizit generieren
        $csrfToken = SessionUtil::getCsrfToken();

        // Debug-Info loggen
        LogUtil::logAction(
            LogType::SECURITY,
            'AuthController',
            'showFogotPassword',
            'Session ID: ' . session_id() .
                ', CSRF Token generated: ' . (!empty($csrfToken) ? 'YES' : 'NO')
        );

        $connOkay = MailUtil::checkConnection();

        if (!$connOkay) {
            Flight::latte()->render('forgot_password.latte', [
                'title' => TranslationUtil::t('forgot.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('register.msg.val7'),
                'error' => TranslationUtil::t('register.msg.val7'),
                'smtpInvalid' => true
            ]);
            return;
        } else {
            Flight::latte()->render('forgot_password.latte', [
                'title' => TranslationUtil::t('forgot.title'),
                'lang' => Flight::get('lang'),
                'smtpInvalid' => false
            ]);
            return;
        }
    }

    /**
     * Handles the forgot password form by generating a reset token.
     * If successful, renders the reset password page with the token.
     * Otherwise, renders the forgot password page with an error message.
     * It expects an 'email' field in the POST request.
     */
    public function forgotPassword()
    {
        $email = "";

        try {
            $validated = InputValidator::validateEmail($_POST['email']);
            $email = $validated['email'];
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', $e->getMessage(), "UNKNOWN_USER");
            Flight::latte()->render('forgot_password.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $user = User::findUserByEmail($email);

        if ($user === false) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: user by email not found.', $email);
            Flight::latte()->render('forgot_password.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('forgot.msg.errorEmail'),
            ]);
            return;
        }

        if ($user !== false && empty($user->reset_token)) {
            $token = bin2hex(random_bytes(50));
            $erg = User::setResetToken($token, $email);

            if ($erg) {
                $name = trim($user->firstname . ' ' . $user->lastname);
                MailUtil::sendMail($user->email, "SecStore: your password reset request", "pwReset", ['name' => $name, 'token' => $token]);
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'SUCCESS: requested pw reset.', $email);
                Flight::latte()->render('forgot_password.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'message' => TranslationUtil::t('forgot.msg.success'),
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: token could not be saved.', $email);
                Flight::latte()->render('reset_password.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'error' => TranslationUtil::t('forgot.msg.errorGeneral'),
                    'token' => ''
                ]);
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'forgotPassword', 'FAILED: user has an open reset pw request already.', $email);
            Flight::latte()->render('forgot_password.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('forgot.msg.errorOpenRequest'),
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
        // Explizit sicherstellen, dass Session aktiv ist
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // CSRF Token explizit generieren
        $csrfToken = SessionUtil::getCsrfToken();

        // Debug-Info loggen
        LogUtil::logAction(
            LogType::SECURITY,
            'AuthController',
            'showResetPassword',
            'Session ID: ' . session_id() .
                ', CSRF Token generated: ' . (!empty($csrfToken) ? 'YES' : 'NO')
        );

        Flight::latte()->render('reset_password.latte', [
            'title' => TranslationUtil::t('reset.title'),
            'lang' => Flight::get('lang'),
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
        if (!isset($_POST['token']) || $_POST['new_password'] === '') {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'FAILED: token or new password not set.', '');
            Flight::latte()->render('reset_password.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('reset.msg.errorGeneral'),
                'token' => 'dfhrtfgjkfgkjifg'
            ]);
            return;
        }

        $token = "";
        $new_password = "";

        try {
            $validated = InputValidator::validateResetPassword($_POST['token'], $_POST['new_password']);
            $token = $validated['token'];
            $new_password = password_hash($validated['newPassword'], PASSWORD_DEFAULT);
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', $e->getMessage(), "UNKNOWN_USER");
            Flight::latte()->render('reset_password.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => $e->getMessage(),
                'token' => $token
            ]);
            return;
        }

        $user = User::findUserByResetToken($token);

        if ($user !== false) {
            $erg = User::setNewPassword($user->id, $new_password);
            if ($erg) {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'SUCCESS: saved new password.', $user->username);
                Flight::latte()->render('login.latte', [
                    'title' => 'Login',
                    'message' => TranslationUtil::t('reset.msg.success'),
                    'lang' => Flight::get('lang'),
                ]);
                return;
            } else {
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'FAILED: new pw could not be saved.', $user->username);
                Flight::latte()->render('login.latte', [
                    'title' => 'Login',
                    'lang' => Flight::get('lang'),
                    'message' => TranslationUtil::t('reset.msg.errorGeneral'),
                    'token' => $token
                ]);
                return;
            }
        } else {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'resetPassword', 'FAILED: invalid reset token.');
            Flight::latte()->render('reset_password.latte', [
                'title' => 'Reset Password',
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('reset.msg.errorToken'),
                'token' => $token
            ]);
            return;
        }
    }
}
