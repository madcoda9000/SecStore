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
use App\Utils\BackupCodeUtil;
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
     * It expects 'email', 'username', 'password', 'firstname', 'lastname', 'csrf_token' fields in the POST request.
     *
     * @return void
     */
    public function register()
    {
        // Initialize variables for security logging
        $email = "";
        $user = "";
        $firstname = "";
        $lastname = "";
        $password = "";

        try {
            // Use new comprehensive validation system
            $rules = InputValidator::getRegistrationRules();

            // Add firstname and lastname rules (specific to this registration form)
            $rules['firstname'] = [
                InputValidator::RULE_REQUIRED,
                [InputValidator::RULE_MIN_LENGTH => 1],
                [InputValidator::RULE_MAX_LENGTH => 255]
            ];
            $rules['lastname'] = [
                InputValidator::RULE_REQUIRED,
                [InputValidator::RULE_MIN_LENGTH => 1],
                [InputValidator::RULE_MAX_LENGTH => 255]
            ];

            // Validate all inputs using the new system
            $validated = InputValidator::validateAndSanitize($rules, $_POST);

            // Extract validated data for business logic
            $user = $validated['username'];
            $email = $validated['email'];
            $firstname = $validated['firstname'];
            $lastname = $validated['lastname'];
            $password = password_hash($validated['password'], PASSWORD_DEFAULT);
        } catch (InvalidArgumentException $e) {
            // Log validation failure with user context for security monitoring
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'Validation failed: ' . $e->getMessage(), $user);

            // Render registration form with error message
            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'error' => $e->getMessage(),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'smtpInvalid' => MailUtil::checkConnection()
            ]);
            return;
        }

        // Check if user already exists (business logic validation)
        $userCheck = User::checkIfUserExists($user, $email);

        // Load config for email link generation
        $configFile = "../config.php";
        $config = include $configFile;

        if ($userCheck === "false") {
            // Create new user mit status = 0 (nicht verifiziert)
            $newUser = User::createUser($user, $email, $firstname, $lastname, 0, $password, 'User');

            if ($newUser !== null) {
                // Verifizierungs-Token generieren
                $verificationToken = bin2hex(random_bytes(32));
                $tokenExpires = new \DateTime();
                $tokenExpires->modify('+24 hours'); // Token 24h gültig

                // Token speichern
                User::setVerificationToken($newUser->id, $verificationToken, $tokenExpires);

                // Verifizierungs-Link erstellen
                $verificationLink = $config['application']['appUrl'] . '/verify/' . $verificationToken;

                // Verifizierungs-Email senden
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'SUCCESS: registered new user (pending verification).', $user);

                MailUtil::sendMail($email, "SecStore: Verify your account", "verification", [
                    'name' => $firstname . ' ' . $lastname,
                    'verificationLink' => $verificationLink,
                    'expiresIn' => '24 hours'
                ]);

                Flight::latte()->render('register.latte', [
                    'title' => TranslationUtil::t('register.title'),
                    'message' => TranslationUtil::t('register.msg.successVerification'),
                    'sessionTimeout' => SessionUtil::getSessionTimeout(),
                    'lang' => Flight::get('lang'),
                    'smtpInvalid' => MailUtil::checkConnection()
                ]);
                return;
            } else {
                // Database error during user creation
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'ERROR: could not create new user.', $user);

                Flight::latte()->render('register.latte', [
                    'title' => TranslationUtil::t('register.title'),
                    'error' => TranslationUtil::t('register.msg.errorGeneral'),
                    'sessionTimeout' => SessionUtil::getSessionTimeout(),
                    'lang' => Flight::get('lang'),
                    'smtpInvalid' => MailUtil::checkConnection()
                ]);
                return;
            }
        } else {
            // User already exists (username or email taken)
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'register', 'FAILED: ' . $userCheck, $user);

            Flight::latte()->render('register.latte', [
                'title' => TranslationUtil::t('register.title'),
                'error' => $userCheck,
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'smtpInvalid' => MailUtil::checkConnection()
            ]);
            return;
        }
    }

    /**
     * Verifiziert einen Benutzer-Account anhand des Tokens.
     *
     * @param string $token Der Verifizierungs-Token aus der URL
     * @return void
     */
    public function verify($token)
    {
        // Token validieren und User verifizieren
        $userData = User::verifyUserByToken($token);

        if ($userData !== false) {
            // Erfolg: User wurde verifiziert
            LogUtil::logAction(
                LogType::AUDIT,
                'AuthController',
                'verify',
                'SUCCESS: User verified account.',
                $userData['username']
            );

            Flight::latte()->render('verify.latte', [
                'title' => TranslationUtil::t('verify.title'),
                'success' => true,
                'message' => TranslationUtil::t('verify.msg.success'),
                'username' => $userData['username'],
                'lang' => Flight::get('lang')
            ]);
            return;
        } else {
            // Fehler: Token ungültig oder abgelaufen
            LogUtil::logAction(
                LogType::SECURITY,
                'AuthController',
                'verify',
                'FAILED: Invalid or expired verification token.',
                'unknown'
            );

            Flight::latte()->render('verify.latte', [
                'title' => TranslationUtil::t('verify.title'),
                'success' => false,
                'error' => TranslationUtil::t('verify.msg.error'),
                'lang' => Flight::get('lang')
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
            // Use new comprehensive validation system
            $rules = InputValidator::getLoginRules();
            $validated = InputValidator::validateAndSanitize($rules, $_POST);
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

        // brute force check to avoid login enumeration
        if (BruteForceUtil::isLockedOut($username)) {
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: brute-force lockout.', $username);
            $this->renderLoginError('login.msg.error5', $config);
            return;
        }

        $user = User::findUserByUsername($username);

        if ($user === false) {
            // check dummy hash to mitigate timing attacks
            password_verify($password, '$2y$10$dummyhashxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
            BruteForceUtil::recordFailedLogin($username);
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: username not found.', $username);
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                "application" => $config["application"],
            ]);
            return;
        }

        // check if account is verified
        if ($user->status == 0 && !empty($user->verification_token)) {
            LogUtil::logAction(LogType::SECURITY, 'AuthController', 'login', 'FAILED: Account not verified.', $username);

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                "application" => $config["application"],
            ]);
            return;
        }

        // check for open password reset token
        if ($user !== false && !empty($user->reset_token)) {
            BruteForceUtil::recordFailedLogin($username);
            LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user has an open password reset request.', $user->email);
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'lang' => Flight::get('lang'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                "application" => $config["application"],
            ]);
            return;
        }

        // check if user account is deactivated
        if ($user->status == 0) {
            LogUtil::logAction(LogType::SECURITY, 'AuthController', 'login', 'FAILED: Account deactivated.', $username);

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                "application" => $config["application"],
            ]);
            return;
        }

        // check if we have to authenticate against an ldap server
        $isAuthenticated = false;
        if ($user->ldapEnabled === 1) {
            $isAuthenticated = LdapUtil::authenticate($username, $password);
            if ($isAuthenticated === false) {
                BruteForceUtil::recordFailedLogin($username);
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: ' . $user->username . ': ldap authentication failed.', $user->email);
                Flight::latte()->render('login.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'error' => TranslationUtil::t('login.msg.error1'),
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
                BruteForceUtil::recordFailedLogin($username);
                LogUtil::logAction(LogType::AUDIT, 'AuthController', 'login', 'FAILED: user account deactivated.', $user->email);
                Flight::latte()->render('login.latte', [
                    'title' => TranslationUtil::t('login.title'),
                    'lang' => Flight::get('lang'),
                    'error' => TranslationUtil::t('login.msg.error1'),
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

            // SCHRITT 2: Session für Login vorbereiten (verhindert doppelte Regeneration)
            SessionUtil::prepareForLogin();
            LogUtil::logAction(LogType::SECURITY, 'AuthController', 'login', 'Session prepared for login: ' . $user->username, $user->username);

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
     * Enables 2FA for the user with backup codes.
     *
     * This method generates a new secret and QR code for the user, generates
     * backup codes, and stores them in the database. It also renders the
     * enable_2fa template with the necessary data including backup codes.
     *
     * @param bool $isMfaStartSetup If true, the user is starting the 2FA setup process.
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
        $secret = $tfa->createSecret();
        $qrCodeUrl = $tfa->getQRCodeImageAsDataUri($user->username, $secret);

        // Generate backup codes
        $backupCodes = BackupCodeUtil::generateBackupCodes();
        $hashedCodes = BackupCodeUtil::hashBackupCodes($backupCodes);

        // Save secret and backup codes to database
        User::setMfaToken($secret, $user->id);
        User::setBackupCodes($user->id, $hashedCodes);

        // Log the action
        LogUtil::logAction(
            LogType::AUDIT,
            'AuthController',
            'enable2FA',
            'SUCCESS: Generated 2FA secret and backup codes for user',
            $user->username
        );

        // Pass data to template
        Flight::latte()->render('enable_2fa.latte', [
            'title' => TranslationUtil::t('2fasetup.title'),
            'lang' => Flight::get('lang'),
            'user' => $user,
            'sessionTimeout' => SessionUtil::getSessionTimeout(),
            'qrCodeUrl' => $qrCodeUrl,
            'secret' => $secret,
            'backupCodes' => $backupCodes,
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
     * Verifies the 2FA code or backup code sent by the user.
     *
     * This method expects either a TOTP code or a backup code to be sent via POST.
     * If a TOTP code is valid, it sets the user session and redirects to dashboard.
     * If a backup code is valid, it marks the code as used, sets session, and redirects.
     * If neither is valid, it renders the 2fa_verify template with an error message.
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

        // check if this is a get request
        $request = Flight::request();
        if ($request->method !== 'POST') {
            // GET-Request: only show page
            Flight::latte()->render('2fa_verify.latte', [
                'title' => TranslationUtil::t('2faverify.title'),
                'lang' => Flight::get('lang'),
            ]);
            return;
        }

        $otp = "";
        $isBackupCode = isset($_POST['backup_code']) && $_POST['backup_code'] === '1';

        try {
            if ($isBackupCode) {
                // Validate backup code format
                $rules['backup_code_value'] = [InputValidator::RULE_REQUIRED];
                $validated = InputValidator::validateAndSanitize($rules, $_POST);
                $otp = $validated['backup_code_value'];

                // Verify backup code
                $backupCodesJson = User::getBackupCodes($user->id);
                $codeIndex = BackupCodeUtil::verifyBackupCode($otp, $backupCodesJson);

                if ($codeIndex !== false) {
                    // Mark code as used
                    $updatedCodes = BackupCodeUtil::markCodeAsUsed($backupCodesJson, $codeIndex);
                    User::updateBackupCodes($user->id, $updatedCodes);

                    // Log successful backup code usage
                    LogUtil::logAction(
                        LogType::AUDIT,
                        'AuthController',
                        'verify2FA',
                        'SUCCESS: Backup code used for 2FA verification',
                        $user->username
                    );

                    // Check remaining codes and warn if low
                    $remainingCodes = BackupCodeUtil::countRemainingCodes($updatedCodes);
                    if ($remainingCodes <= 3 && $remainingCodes > 0) {
                        SessionUtil::set('backup_codes_low_warning', $remainingCodes);
                    }

                    SessionUtil::set('user', $user);
                    unset($_SESSION['2fa_user_id']);
                    Flight::redirect('/home');
                } else {
                    throw new InvalidArgumentException(TranslationUtil::t('2faverify.msg.error.backupcode'));
                }
            } else {
                // Standard TOTP verification
                $rules = InputValidator::get2FAVerificationRules();
                $validated = InputValidator::validateAndSanitize($rules, $_POST);
                $otp = $validated['otp'];

                $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
                if ($tfa->verifyCode($user->mfaSecret, $otp)) {
                    LogUtil::logAction(
                        LogType::AUDIT,
                        'AuthController',
                        'verify2FA',
                        'SUCCESS: 2FA verification successful',
                        $user->username
                    );
                    SessionUtil::set('user', $user);
                    unset($_SESSION['2fa_user_id']);
                    Flight::redirect('/home');
                } else {
                    throw new InvalidArgumentException(TranslationUtil::t('2faverify.msg.error1'));
                }
            }
        } catch (InvalidArgumentException $e) {
            LogUtil::logAction(
                LogType::AUDIT,
                'AuthController',
                'verify2FA',
                'FAILED: ' . $e->getMessage(),
                $user->username
            );
            Flight::latte()->render('2fa_verify.latte', [
                'title' => TranslationUtil::t('2faverify.title'),
                'lang' => Flight::get('lang'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'error' => $e->getMessage(),
            ]);
            return;
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
            $validated = InputValidator::validateAndSanitize([
                'email' => [
                    InputValidator::RULE_REQUIRED,
                    InputValidator::RULE_EMAIL,
                    [InputValidator::RULE_MAX_LENGTH => 255]
                ]
            ], $_POST);

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
            $configFile = "../config.php";
            $config = include $configFile;

            $token = bin2hex(random_bytes(50));
            $erg = User::setResetToken($token, $email);

            if ($erg) {
                $name = trim($user->firstname . ' ' . $user->lastname);
                $url = $config['application']['appUrl'] . '/reset-password/' . $token;
                MailUtil::sendMail($user->email, "SecStore: your password reset request", "pwReset", ['name' => $name, 'token' => $token, 'url' => $url]);
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
            $rules['new_password'] = [InputValidator::RULE_REQUIRED];
            $rules['new_password'] = [InputValidator::RULE_PASSWORD_STRONG];
            $rules['token'] = [InputValidator::RULE_REQUIRED];
            $validated = InputValidator::validateAndSanitize($rules, $_POST);
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
