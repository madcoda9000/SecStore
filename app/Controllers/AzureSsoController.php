<?php

namespace App\Controllers;

use Flight;
use App\Models\User;
use App\Utils\AzureSsoUtil;
use App\Utils\SessionUtil;
use App\Utils\LogUtil;
use App\Utils\TranslationUtil;
use App\Utils\LogType;

/**
 * Controller for Azure SSO / Entra ID authentication
 *
 * Handles BOTH real Azure OAuth2 flow AND Mock Mode for development:
 * - Real Mode: Redirects to Microsoft, handles OAuth2 callback
 * - Mock Mode: Shows local login selection, handles direct login
 *
 * @package App\Controllers
 */
class AzureSsoController
{
    /**
     * Redirect user to Azure login page OR show Mock login
     *
     * In Mock Mode: Shows mock login page directly
     * In Real Mode: Redirects to Microsoft Azure AD
     *
     * @return void
     */
    public function redirectToAzure(): void
    {
        $configFile = '../config.php';
        $config = include $configFile;

        // Check if Azure SSO is enabled
        if (!($config['azureSso']['enabled'] ?? false)) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'redirectToAzure',
                'Azure SSO disabled - redirect attempt blocked'
            );
            Flight::redirect('/login');
            return;
        }

        // *** MOCK MODE: Show Mock-Login page directly ***
        if ($config['azureSso']['mockMode'] === true && $config['application']['enviroment'] === 'development') {
            // Generate state for CSRF protection
            $state = bin2hex(random_bytes(16));
            $_SESSION['oauth2state'] = $state;

            // Get all users with Entra ID enabled
            $users = User::getAllUsers();
            $entraUsers = array_filter($users, function ($user) {
                return $user->entraIdEnabled === 1;
            });

            LogUtil::logAction(
                LogType::AUDIT,
                'AzureSsoController',
                'redirectToAzure',
                'MOCK MODE: Showing mock login page'
            );

            // Show Mock-Login page directly (no redirect!)
            Flight::latte()->render('dev/azure-mock-login.latte', [
                'title' => 'Mock Azure Login',
                'state' => $state,
                'users' => $entraUsers,
            ]);
            return;
        }

        // *** REAL MODE: Redirect to Microsoft Azure AD ***
        $authUrl = AzureSsoUtil::getAuthorizationUrl();

        LogUtil::logAction(
            LogType::AUDIT,
            'AzureSsoController',
            'redirectToAzure',
            'User redirected to Azure login'
        );

        Flight::redirect($authUrl);
    }

    /**
     * Handle Azure SSO callback
     *
     * In Mock Mode: Handles POST from mock login form
     * In Real Mode: Handles GET callback from Microsoft with OAuth2 code
     *
     * @return void
     */
    public function handleCallback(): void
    {
        $configFile = '../config.php';
        $config = include $configFile;

        // Check if Azure SSO is enabled
        if (!($config['azureSso']['enabled'] ?? false)) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleCallback',
                'Azure SSO disabled - callback attempt blocked'
            );
            Flight::redirect('/login');
            return;
        }

        // *** MOCK MODE: Handle Mock Login ***
        if ($config['azureSso']['mockMode'] === true && $config['application']['enviroment'] === 'development') {
            $this->handleMockLogin($config);
            return;
        }

        // *** REAL MODE: Handle OAuth2 Callback ***
        $this->handleRealAzureLogin($config);
    }

    /**
     * Handle Mock Login (Development Only)
     *
     * Processes the mock login form submission:
     * 1. Validates state parameter (CSRF)
     * 2. Finds user by email
     * 3. Checks entraIdEnabled and account status
     * 4. Logs user in directly
     *
     * @param array $config Configuration array
     * @return void
     */
    private function handleMockLogin(array $config): void
    {
        $email = $_POST['mock_email'] ?? null;
        $state = $_POST['state'] ?? null;

        // Validate required parameters
        if (!$email || !$state) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleMockLogin',
                'Missing parameters: email or state'
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.error'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Validate state (CSRF protection)
        if (!isset($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleMockLogin',
                'Invalid state parameter - possible CSRF attack'
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.error'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Clear state from session
        unset($_SESSION['oauth2state']);

        // Find user by email
        $user = User::findUserByEmail($email);

        if ($user === false) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleMockLogin',
                "MOCK MODE: User not found: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.usernotfound'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Check if user has Entra ID enabled
        if ($user->entraIdEnabled !== 1) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleMockLogin',
                "MOCK MODE: Entra ID not enabled for user: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.notenabled'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Check if account is active
        if ($user->status === 0) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleMockLogin',
                "MOCK MODE: Account deactivated: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // SUCCESS - Login user directly
        SessionUtil::set('user', $user);
        SessionUtil::prepareForLogin();

        User::setActiveSessionId($user->id);
        User::updateLastKnownIp($user->id, $_SERVER['REMOTE_ADDR']);

        LogUtil::logAction(
            LogType::AUDIT,
            'AzureSsoController',
            'handleMockLogin',
            "MOCK MODE: Successful login for: {$email}"
        );

        // log debug information in development mode
        if ($config['application']['environment'] === 'development') {
            LogUtil::logAction(
                LogType::SYSTEM,
                'AzureSsoController',
                'handleMockLogin',
                'DEBUG Session: ' . json_encode([
                    'session_id' => session_id(),
                    'user_set' => isset($_SESSION['user']),
                    'user_id' => $_SESSION['user']->id ?? 'not set',
                    'fingerprint' => $_SESSION['security_fingerprint'] ?? 'not set',
                    'last_activity' => $_SESSION['last_activity'] ?? 'not set',
                ])
            );
        }

        Flight::redirect('/home');
    }

    /**
     * Handle Real Azure Login (Production)
     *
     * Processes the OAuth2 callback from Microsoft Azure AD:
     * 1. Validates authorization code and state
     * 2. Exchanges code for access token
     * 3. Retrieves user email from Microsoft Graph
     * 4. Finds user in local database
     * 5. Validates permissions and status
     * 6. Logs user in
     *
     * @param array $config Configuration array
     * @return void
     */
    private function handleRealAzureLogin(array $config): void
    {
        // Get authorization code and state from query parameters
        $code = $_GET['code'] ?? null;
        $state = $_GET['state'] ?? null;

        if (!$code || !$state) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleRealAzureLogin',
                'Missing code or state parameter in callback'
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.error'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Get user email from Azure via OAuth2
        $email = AzureSsoUtil::handleCallback($code, $state);

        if ($email === false) {
            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.error'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Find user by email in local database
        $user = User::findUserByEmail($email);

        if ($user === false) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleRealAzureLogin',
                "User not found for Azure login: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.usernotfound'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Check if user has Entra ID login enabled
        if ($user->entraIdEnabled !== 1) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleRealAzureLogin',
                "Entra ID not enabled for user: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.azure.notenabled'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // Check if user account is active
        if ($user->status === 0) {
            LogUtil::logAction(
                LogType::SECURITY,
                'AzureSsoController',
                'handleRealAzureLogin',
                "Account deactivated for Azure login: {$email}"
            );

            Flight::latte()->render('login.latte', [
                'title' => TranslationUtil::t('login.title'),
                'error' => TranslationUtil::t('login.msg.error1'),
                'sessionTimeout' => SessionUtil::getSessionTimeout(),
                'lang' => Flight::get('lang'),
                'application' => $config['application'],
                'azureSso' => $config['azureSso'],
            ]);
            return;
        }

        // SUCCESS - Login without brute force or 2FA checks
        // Azure/Entra ID handles authentication, so we trust it
        SessionUtil::set('user', $user);
        SessionUtil::prepareForLogin();

        LogUtil::logAction(
            LogType::AUDIT,
            'AzureSsoController',
            'handleRealAzureLogin',
            "Successful Azure SSO login for: {$email}"
        );

        Flight::redirect('/home');
    }
}
