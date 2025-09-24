<?php

namespace App\Controllers;

use Flight;
use Exception;
use PDO;
use PDOException;
use App\Utils\SessionUtil;
use App\Utils\TranslationUtil;
use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\MailUtil;

/**
 * Setup Controller für die initiale Anwendungsinstallation
 */
class SetupController
{
    private $configFile;

    public function __construct()
    {
        $this->configFile = "../config.php";
    }

    /**
     * Hauptmethode für Setup-Prozess
     */
    public function runSetup($skipMail = false)
    {
        try {

            // Schritt 1: Prüfen ob config.php existiert
            if (!$this->checkConfigExists()) {
                return $this->renderSetupStep('config_missing', [
                    'title' => TranslationUtil::t('setup.controller.missingConfig'),
                    'step' => 1,
                    'error' => TranslationUtil::t('setup.controller.missingConfigDesc')
                ]);
            }

            // Schritt 2: Prüfen ob config.php beschreibbar ist
            if (!$this->checkConfigWritable()) {
                return $this->renderSetupStep('config_not_writable', [
                    'title' => TranslationUtil::t('setup.controller.configNotWritable'),
                    'step' => 2,
                    'error' => TranslationUtil::t('setup.controller.configNotWritableDesc')
                ]);
            }

            // Konfiguration laden
            $config = include $this->configFile;

            // Schritt 3: Datenbankonfiguration prüfen
            if ($this->needsDatabaseConfig($config)) {
                return $this->handleDatabaseConfig($config);
            }

            if ($skipMail === true) {
                $this->setSkippedMailConfig();
                return $this->completeSetup();
            } else {
                // Schritt 4: SMTP-Konfiguration prüfen
                if ($this->needsMailConfig($config)) {
                    return $this->handleMailConfig($config);
                }
            }

            // Setup abgeschlossen - weiterleiten zu Login
            return $this->completeSetup();
        } catch (Exception $e) {
            LogUtil::logAction(LogType::ERROR, 'SetupController', 'runSetup', $e->getMessage());
            return $this->renderSetupStep('error', [
                'title' => TranslationUtil::t('setup.error.title'),
                'error' => TranslationUtil::t('setup.error.global') . $e->getMessage()
            ]);
        }
    }

    /**
     * Setzt das Setup-Flag in der config.php (für überspringen der Mail-Konfiguration)
     */
    private function setSkippedMailConfig()
    {
        // Standard-Mail-Konfiguration die zeigt, dass Setup übersprungen wurde
        $skippedConfig = [
            'host' => 'ANY_MAIL_HOST',        // ← Nicht mehr "YOUR_SMTP_SERVER"!
            'username' => 'YOUR_SMTP_USERNAME',
            'password' => 'YOUR_SMTP_PASSWORD',
            'encryption' => 'tls',
            'port' => 587,
            'fromEmail' => 'noreply@localhost',
            'fromName' => 'SecStore System',
            'enableWelcomeMail' => true
        ];

        // Konfiguration lesen
        $configContent = file_get_contents($this->configFile);
        if ($configContent === false) {
            throw new Exception(TranslationUtil::t('setup.config.err.notReadable'));
        }

        // Mail-Array aktualisieren (gleiche Logik wie updateMailConfig)
        $pattern = '/(\$mail\s*=\s*\[)(.*?)(\];)/s';

        $newMailArray = var_export($skippedConfig, true);
        $newMailArray = preg_replace("/^array \(/", "[", $newMailArray);
        $newMailArray = preg_replace('/\)$/', "]", $newMailArray);
        $newMailArray = preg_replace('/=> \n\s+/', "=> ", $newMailArray);

        $replacement = '$mail = ' . $newMailArray . ";";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);

        if ($newConfigContent === null) {
            throw new Exception(TranslationUtil::t('setup.config.err.notReplaceable'));
        }

        file_put_contents($this->configFile, $newConfigContent);

        LogUtil::logAction(
            LogType::SYSTEM,
            'SetupController',
            'setSkippedMailConfig',
            'Mail configuration set to ANY_MAIL_HOST (setup skipped)'
        );
    }

    /**
     * Prüft ob config.php existiert
     */
    private function checkConfigExists()
    {
        return file_exists($this->configFile);
    }

    /**
     * Prüft ob config.php beschreibbar ist
     */
    private function checkConfigWritable()
    {
        return is_writable($this->configFile);
    }

    /**
     * Prüft ob Datenbankonfiguration benötigt wird
     */
    private function needsDatabaseConfig($config)
    {
        return isset($config['db']['host']) && $config['db']['host'] === 'YOUR_DB_SERVER_NAME';
    }

    /**
     * Prüft ob SMTP-Konfiguration benötigt wird
     */
    private function needsMailConfig($config)
    {
        return isset($config['mail']['host']) && $config['mail']['host'] === 'YOUR_SMTP_SERVER';
    }

    /**
     * Behandelt Datenbankonfiguration
     */
    private function handleDatabaseConfig($config)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_step']) && $_POST['setup_step'] === 'database') {
            return $this->processDatabaseConfig($_POST);
        }

        return $this->renderSetupStep('database_config', [
            'title' => TranslationUtil::t('setup.database.title'),
            'step' => 3
        ]);
    }

    /**
     * Verarbeitet Datenbankonfiguration
     */
    private function processDatabaseConfig($formData)
    {
        try {
            // CSRF Token validieren
            if (
                !isset($_SESSION['csrf_token']) || !isset($formData['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $formData['csrf_token'])
            ) {

                $errorMsg = 'CSRF Token Validierung fehlgeschlagen.';
                if (!isset($_SESSION['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missing');
                }
                if (!isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missingFormToken');
                }
                if (isset($_SESSION['csrf_token']) && isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missmatch');
                }

                throw new Exception($errorMsg);
            }

            // Validierung
            $requiredFields = ['db_host', 'db_user', 'db_pass', 'db_name'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    throw new Exception("Feld {$field} " . TranslationUtil::t('setup.error.missingField'));
                }
            }

            // Datenbankverbindung testen
            $testConnection = $this->testDatabaseConnection($formData);
            if (!$testConnection['success']) {
                return $this->renderSetupStep('database_config', [
                    'title' => 'Setup - ' . TranslationUtil::t('setup.database.title'),
                    'step' => 3,
                    'error' => TranslationUtil::t('setup.error.dbConnection') . ($testConnection['error'] ?? 'Unknown Error!'),
                    'form_data' => $formData
                ]);
            }

            // Konfiguration speichern
            $this->updateDatabaseConfig($formData);

            // DatabaseSetup ausführen
            $this->runDatabaseSetup($formData);

            // Erfolgsmeldung anzeigen
            return $this->renderSetupStep('database_success', [
                'title' => TranslationUtil::t('setup.succ.msg1'),
                'step' => 3,
                'success' => TranslationUtil::t('setup.succ.msg2')
            ]);
        } catch (Exception $e) {
            return $this->renderSetupStep('database_config', [
                'title' => 'Setup - ' . TranslationUtil::t('setup.database.title'),
                'step' => 3,
                'error' => $e->getMessage(),
                'form_data' => $formData ?? []
            ]);
        }
    }

    /**
     * Testet Datenbankverbindung
     */
    private function testDatabaseConnection($formData)
    {
        try {
            $pdo = new PDO("mysql:host={$formData['db_host']}", $formData['db_user'], $formData['db_pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5 // 5 Sekunden Timeout
            ]);
            $pdo = null; // Verbindung schließen
            return ['success' => true];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'error' => TranslationUtil::t('setup.error.global') . $e->getMessage()];
        }
    }

    /**
     * Aktualisiert Datenbankonfiguration in config.php
     */
    private function updateDatabaseConfig($formData)
    {
        $newConfig = [
            'host' => $formData['db_host'],
            'user' => $formData['db_user'],
            'pass' => $formData['db_pass'],
            'name' => $formData['db_name']
        ];

        // Konfiguration lesen
        $configContent = file_get_contents($this->configFile);
        if ($configContent === false) {
            throw new Exception(TranslationUtil::t('setup.config.err.notReadable'));
        }

        // Aktuelles $db-Array suchen und ersetzen (wie in AdminController)
        $pattern = '/(\$db\s*=\s*\[)(.*?)(\];)/s';

        $newDbArray = var_export($newConfig, true);
        $newDbArray = preg_replace("/^array \(/", "[", $newDbArray);
        $newDbArray = preg_replace('/\)$/', "]", $newDbArray);
        $newDbArray = preg_replace('/=> \n\s+/', "=> ", $newDbArray);

        $replacement = '$db = ' . $newDbArray . ";";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);

        if ($newConfigContent === null) {
            throw new Exception('Fehler beim Ersetzen der Konfiguration.');
        }

        file_put_contents($this->configFile, $newConfigContent);
    }

    /**
     * Führt DatabaseSetup aus
     */
    private function runDatabaseSetup($formData)
    {
        // Temporäre Variablen für DatabaseSetup.php setzen
        $db = [
            'host' => $formData['db_host'],
            'user' => $formData['db_user'],
            'pass' => $formData['db_pass'],
            'name' => $formData['db_name']
        ];

        // DatabaseSetup einbinden und ausführen
        require '../app/DatabaseSetup.php';
    }

    /**
     * Behandelt SMTP-Konfiguration
     */
    private function handleMailConfig($config)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // CSRF Token validieren
            if (
                !isset($_SESSION['csrf_token']) || !isset($formData['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $formData['csrf_token'])
            ) {

                $errorMsg = 'CSRF Token Validierung fehlgeschlagen.';
                if (!isset($_SESSION['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missing');
                }
                if (!isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missingFormToken');
                }
                if (isset($_SESSION['csrf_token']) && isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missmatch');
                }

                throw new Exception($errorMsg);
            }

            return $this->processMailConfig($_POST);
        }

        return $this->renderSetupStep('mail_config', [
            'title' => TranslationUtil::t('setup.title.mailConfig'),
            'step' => 4
        ]);
    }

    /**
     * Verarbeitet SMTP-Konfiguration
     */
    private function processMailConfig($formData)
    {
        try {
            // CSRF Token validieren
            if (
                !isset($_SESSION['csrf_token']) || !isset($formData['csrf_token']) ||
                !hash_equals($_SESSION['csrf_token'], $formData['csrf_token'])
            ) {

                $errorMsg = 'CSRF Token Validierung fehlgeschlagen.';
                if (!isset($_SESSION['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missing');
                }
                if (!isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missingFormToken');
                }
                if (isset($_SESSION['csrf_token']) && isset($formData['csrf_token'])) {
                    $errorMsg .= ' ' . TranslationUtil::t('csrf.error.missmatch');
                }

                throw new Exception($errorMsg);
            }
            // SMTP-Verbindung testen
            $testResult = $this->testMailConnection($formData);
            if (!$testResult['success']) {
                return $this->renderSetupStep('mail_config', [
                    'title' => TranslationUtil::t('setup.title.mailConfig'),
                    'step' => 4,
                    'error' => TranslationUtil::t('setup.error.smtptest') . ($testResult['error'] ?? 'Unbekannter Fehler'),
                    'form_data' => $formData
                ]);
            }

            // Konfiguration speichern
            $this->updateMailConfig($formData);

            return $this->completeSetup();
        } catch (Exception $e) {
            return $this->renderSetupStep('mail_config', [
                'title' => TranslationUtil::t('setup.title.mailConfig'),
                'step' => 4,
                'error' => $e->getMessage(),
                'form_data' => $formData ?? []
            ]);
        }
    }

    /**
     * Testet SMTP-Verbindung (analog zur MailUtil::checkConnection)
     */
    private function testMailConnection($formData)
    {
        try {
            // Validierung der erforderlichen Felder
            $requiredFields = ['mail_host', 'mail_username', 'mail_password', 'mail_port'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    return ['success' => false, 'error' => "Feld {$field} " . TranslationUtil::t('setup.error.missingField')];
                }
            }

            // Temporäre config für Test erstellen
            $tempConfig = [
                'mail' => [
                    'host' => $formData['mail_host'],
                    'username' => $formData['mail_username'],
                    'password' => $formData['mail_password'],
                    'encryption' => $formData['mail_encryption'] ?? 'tls',
                    'port' => (int) $formData['mail_port']
                ]
            ];

            // Original-Konfiguration sichern
            $configContent = file_get_contents($this->configFile);
            if ($configContent === false) {
                return ['success' => false, 'error' => TranslationUtil::t('setup.config.err.notReadable')];
            }

            // Temporäre Konfiguration schreiben
            $pattern = '/(\$mail\s*=\s*\[)(.*?)(\];)/s';

            $newMailArray = var_export($tempConfig['mail'], true);
            $newMailArray = preg_replace("/^array \(/", "[", $newMailArray);
            $newMailArray = preg_replace('/\)$/', "]", $newMailArray);
            $newMailArray = preg_replace('/=> \n\s+/', "=> ", $newMailArray);

            $replacement = '$mail = ' . $newMailArray . ";";
            $testConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($testConfigContent === null) {
                return ['success' => false, 'error' => TranslationUtil::t('setup.config.err.notWritable')];
            }

            file_put_contents($this->configFile, $testConfigContent);

            // MailUtil Test verwenden
            $result = MailUtil::checkConnection();

            // Original-Konfiguration wiederherstellen
            file_put_contents($this->configFile, $configContent);

            return ['success' => $result];
        } catch (Exception $e) {
            // Original-Konfiguration wiederherstellen (falls möglich)
            if (isset($configContent)) {
                file_put_contents($this->configFile, $configContent);
            }
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Aktualisiert SMTP-Konfiguration in config.php
     */
    private function updateMailConfig($formData)
    {
        $newConfig = [
            'host' => $formData['mail_host'],
            'username' => $formData['mail_username'],
            'password' => $formData['mail_password'],
            'encryption' => $formData['mail_encryption'],
            'port' => (int) $formData['mail_port'],
            'fromEmail' => $formData['mail_from_email'],
            'fromName' => $formData['mail_from_name'],
            'enableWelcomeMail' => isset($formData['mail_enable_welcome'])
        ];

        // Konfiguration lesen
        $configContent = file_get_contents($this->configFile);
        if ($configContent === false) {
            throw new Exception(TranslationUtil::t('setup.config.err.notReadable'));
        }

        // Aktuelles $mail-Array suchen und ersetzen (wie in AdminController)
        $pattern = '/(\$mail\s*=\s*\[)(.*?)(\];)/s';

        $newMailArray = var_export($newConfig, true);
        $newMailArray = preg_replace("/^array \(/", "[", $newMailArray);
        $newMailArray = preg_replace('/\)$/', "]", $newMailArray);
        $newMailArray = preg_replace('/=> \n\s+/', "=> ", $newMailArray);

        $replacement = '$mail = ' . $newMailArray . ";";
        $newConfigContent = preg_replace($pattern, $replacement, $configContent);

        if ($newConfigContent === null) {
            throw new Exception(TranslationUtil::t('setup.config.err.notReplaceable'));
        }

        file_put_contents($this->configFile, $newConfigContent);
    }

    /**
     * Setup abschließen
     */
    private function completeSetup()
    {
        return $this->renderSetupStep('complete', [
            'title' => TranslationUtil::t('setup.finish.msg1'),
            'success' => TranslationUtil::t('setup.finish.msg2'),
            'login_username' => TranslationUtil::t('setup.finish.msg3'),
            'login_password' => TranslationUtil::t('setup.finish.msg4'),
            'login_email' => TranslationUtil::t('setup.finish.msg5')
        ]);
    }

    private function renderSetupStep($step, $data = [])
    {
        // Standard-Variablen initialisieren um "Undefined array key" Fehler zu vermeiden
        $defaultData = [
            'setup_step' => $step,
            'title' => 'Setup',
            'step' => $this->getStepNumber($step), // <- NEUE METHODE
            'error' => null,
            'success' => null,
            'form_data' => [],
            'login_username' => null,
            'login_password' => null,
            'login_email' => null
        ];

        // Übergebene Daten mit Defaults mergen
        $templateData = array_merge($defaultData, $data);

        // CSRF Token für Formulare generieren
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new Exception(TranslationUtil::t('setup.controller.err.invalidSessionInitialization'));
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        Flight::latte()->render('setup.latte', $templateData);
    }

    /**
     * NEUE METHODE: Ermittelt die Schritt-Nummer basierend auf dem Setup-Step
     */
    private function getStepNumber($setupStep)
    {
        switch ($setupStep) {
            case 'config_missing':
                return 1;
            case 'config_not_writable':
                return 2;
            case 'database_config':
            case 'database_success':
                return 3;
            case 'mail_config':
            case 'complete':
                return 4;
            default:
                return null;
        }
    }
}
