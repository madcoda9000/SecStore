<?php

namespace App\Utils;

use Exception;
use League\OAuth2\Client\Provider\GenericProvider;
use App\Utils\LogUtil;
use App\Utils\LogType;

/**
 * Azure SSO / Entra ID Authentication Utility
 *
 * Handles OAuth2 authentication flow with Microsoft Azure AD / Entra ID.
 * Provides methods for generating authorization URLs and processing callbacks.
 *
 * @package App\Utils
 */
class AzureSsoUtil
{
    /**
     * Get Azure OAuth2 Provider instance
     *
     * Creates and configures a GenericProvider with Azure AD endpoints
     * based on configuration from config.php
     *
     * @return GenericProvider Configured OAuth2 provider
     */
    public static function getProvider(): GenericProvider
    {
        $configFile = '../config.php';
        $config = include $configFile;
        $azureConfig = $config['azureSso'];

        // MOCK MODE für Testing
        if ($azureConfig['mockMode'] ?? false) {
            return new GenericProvider([
                'clientId'                => 'mock-client-id',
                'clientSecret'            => 'mock-client-secret',
                'redirectUri'             => $azureConfig['redirectUri'],
                'urlAuthorize'            => rtrim($azureConfig['redirectUri'], '/auth/azure/callback') . '/dev/azure-mock/login',
                'urlAccessToken'          => rtrim($azureConfig['redirectUri'], '/auth/azure/callback') . '/dev/azure-mock/token',
                'urlResourceOwnerDetails' => rtrim($azureConfig['redirectUri'], '/auth/azure/callback') . '/dev/azure-mock/me',
            ]);
        }

        return new GenericProvider([
            'clientId'                => $azureConfig['clientId'],
            'clientSecret'            => $azureConfig['clientSecret'],
            'redirectUri'             => $azureConfig['redirectUri'],
            'urlAuthorize'            => "https://login.microsoftonline.com/{$azureConfig['tenantId']}/oauth2/v2.0/authorize",
            'urlAccessToken'          => "https://login.microsoftonline.com/{$azureConfig['tenantId']}/oauth2/v2.0/token",
            'urlResourceOwnerDetails' => 'https://graph.microsoft.com/v1.0/me',
            'scopes'                  => 'openid profile email User.Read',
        ]);
    }

    /**
     * Generate authorization URL for Azure login
     *
     * Creates the URL to redirect users to Azure AD login page.
     * Stores the OAuth2 state in session for CSRF protection.
     *
     * @return string Authorization URL
     */
    public static function getAuthorizationUrl(): string
    {
        $provider = self::getProvider();
        $authUrl = $provider->getAuthorizationUrl();

        // Store state in session for CSRF protection
        $_SESSION['oauth2state'] = $provider->getState();

        return $authUrl;
    }

    /**
     * Handle Azure SSO callback and retrieve user email
     *
     * Processes the OAuth2 callback from Azure AD, validates the state parameter,
     * exchanges the authorization code for an access token, and retrieves user
     * information from Microsoft Graph API.
     *
     * @param string $code Authorization code from Azure
     * @param string $state State parameter for CSRF validation
     * @return string|false User email on success, false on failure
     */
    public static function handleCallback(string $code, string $state)
    {
        try {
            // MOCK MODE Behandlung
            $configFile = '../config.php';
            $config = include $configFile;
            if ($config['azureSso']['mockMode'] ?? false) {
                // Im Mock-Mode ist die Email direkt in der Session
                if (isset($_SESSION['mock_azure_email'])) {
                    $email = $_SESSION['mock_azure_email'];
                    unset($_SESSION['mock_azure_email']);
                    unset($_SESSION['mock_azure_code']);
                    unset($_SESSION['oauth2state']);

                    LogUtil::logAction(
                        LogType::AUDIT,
                        'AzureSsoUtil',
                        'handleCallback',
                        "MOCK MODE: Login for {$email}"
                    );

                    return strtolower(trim($email));
                }
            }


            // Validate state to prevent CSRF attacks
            if (!isset($_SESSION['oauth2state']) || $state !== $_SESSION['oauth2state']) {
                LogUtil::logAction(
                    LogType::SECURITY,
                    'AzureSsoUtil',
                    'handleCallback',
                    'Invalid state parameter - possible CSRF attack'
                );
                return false;
            }

            // Clear state from session
            unset($_SESSION['oauth2state']);

            $provider = self::getProvider();

            // Exchange authorization code for access token
            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $code
            ]);

            // Get user details from Microsoft Graph API
            $resourceOwner = $provider->getResourceOwner($accessToken);
            $userArray = $resourceOwner->toArray();

            // Return email address (primary identifier)
            // Try 'mail' first (business accounts), fall back to 'userPrincipalName'
            if (isset($userArray['mail']) && !empty($userArray['mail'])) {
                return strtolower(trim($userArray['mail']));
            } elseif (isset($userArray['userPrincipalName'])) {
                return strtolower(trim($userArray['userPrincipalName']));
            }

            LogUtil::logAction(
                LogType::ERROR,
                'AzureSsoUtil',
                'handleCallback',
                'No email found in Azure response'
            );
            return false;
        } catch (Exception $e) {
            LogUtil::logAction(
                LogType::ERROR,
                'AzureSsoUtil',
                'handleCallback',
                'Azure SSO error: ' . $e->getMessage()
            );
            return false;
        }
    }
}
