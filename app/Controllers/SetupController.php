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
    public function runSetup()
    {
        try {
            // Debug: POST-Daten loggen
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                error_log("Setup POST Data: " . print_r($_POST, true));
            }

            // Schritt 1: Prüfen ob config.php existiert
            if (!$this->checkConfigExists()) {
                return $this->renderSetupStep('config_missing', [
                    'title' => 'Setup - Konfigurationsdatei fehlt',
                    'step' => 1,
                    'error' => 'config.php nicht gefunden. Bitte kopieren Sie config.php_TEMPLATE zu config.php und machen Sie die Datei für den Webserver beschreibbar.'
                ]);
            }

            // Schritt 2: Prüfen ob config.php beschreibbar ist
            if (!$this->checkConfigWritable()) {
                return $this->renderSetupStep('config_not_writable', [
                    'title' => 'Setup - Berechtigung fehlt',
                    'step' => 2,
                    'error' => 'config.php ist nicht beschreibbar. Bitte setzen Sie die Dateiberechtigungen (chmod 664) und Owner (chown www-data:www-data) entsprechend.'
                ]);
            }

            // Konfiguration laden
            $config = include $this->configFile;

            // Schritt 3: Datenbankonfiguration prüfen
            if ($this->needsDatabaseConfig($config)) {
                return $this->handleDatabaseConfig($config);
            }

            // Schritt 4: SMTP-Konfiguration prüfen
            if ($this->needsMailConfig($config)) {
                return $this->handleMailConfig($config);
            }

            // Setup abgeschlossen - weiterleiten zu Login
            return $this->completeSetup();

        } catch (Exception $e) {
            LogUtil::logAction(LogType::ERROR, 'SetupController', 'runSetup', $e->getMessage());
            return $this->renderSetupStep('error', [
                'title' => 'Setup - Fehler',
                'error' => 'Ein Fehler ist aufgetreten: ' . $e->getMessage()
            ]);
        }
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
            'title' => 'Setup - Datenbank konfigurieren',
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
            if (!isset($_SESSION['csrf_token']) || !isset($formData['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $formData['csrf_token'])) {
                throw new Exception('Ungültiger CSRF Token.');
            }

            // Validierung
            $requiredFields = ['db_host', 'db_user', 'db_pass', 'db_name'];
            foreach ($requiredFields as $field) {
                if (empty($formData[$field])) {
                    throw new Exception("Feld {$field} ist erforderlich.");
                }
            }

            // Datenbankverbindung testen
            $testConnection = $this->testDatabaseConnection($formData);
            if (!$testConnection['success']) {
                return $this->renderSetupStep('database_config', [
                    'title' => 'Setup - Datenbank konfigurieren',
                    'step' => 3,
                    'error' => 'Datenbankverbindung fehlgeschlagen: ' . ($testConnection['error'] ?? 'Unbekannter Fehler'),
                    'form_data' => $formData
                ]);
            }

            // Konfiguration speichern
            $this->updateDatabaseConfig($formData);

            // DatabaseSetup ausführen
            $this->runDatabaseSetup($formData);

            // Erfolgsmeldung anzeigen
            return $this->renderSetupStep('database_success', [
                'title' => 'Setup - Datenbank erfolgreich',
                'step' => 3,
                'success' => 'Datenbanksetup wurde erfolgreich abgeschlossen!'
            ]);

        } catch (Exception $e) {
            return $this->renderSetupStep('database_config', [
                'title' => 'Setup - Datenbank konfigurieren',
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
            return ['success' => false, 'error' => 'Unbekannter Fehler: ' . $e->getMessage()];
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
            throw new Exception('Konfigurationsdatei konnte nicht gelesen werden.');
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
            // CSRF Token validieren für alle POST-Anfragen
            if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
                return $this->renderSetupStep('mail_config', [
                    'title' => 'Setup - E-Mail konfigurieren',
                    'step' => 4,
                    'error' => 'Ungültiger CSRF Token.'
                ]);
            }
            
            if (isset($_POST['setup_step']) && $_POST['setup_step'] === 'mail') {
                return $this->processMailConfig($_POST);
            } elseif (isset($_POST['skip_mail'])) {
                return $this->completeSetup();
            }
        }

        return $this->renderSetupStep('mail_config', [
            'title' => 'Setup - E-Mail konfigurieren',
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
            if (!isset($_SESSION['csrf_token']) || !isset($formData['csrf_token']) || 
                !hash_equals($_SESSION['csrf_token'], $formData['csrf_token'])) {
                throw new Exception('Ungültiger CSRF Token.');
            }

            // SMTP-Verbindung testen
            $testResult = $this->testMailConnection($formData);
            if (!$testResult['success']) {
                return $this->renderSetupStep('mail_config', [
                    'title' => 'Setup - E-Mail konfigurieren',
                    'step' => 4,
                    'error' => 'SMTP-Verbindung fehlgeschlagen: ' . ($testResult['error'] ?? 'Unbekannter Fehler'),
                    'form_data' => $formData
                ]);
            }

            // Konfiguration speichern
            $this->updateMailConfig($formData);

            return $this->completeSetup();

        } catch (Exception $e) {
            return $this->renderSetupStep('mail_config', [
                'title' => 'Setup - E-Mail konfigurieren',
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
                    return ['success' => false, 'error' => "Feld {$field} ist erforderlich."];
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
                return ['success' => false, 'error' => 'Konfigurationsdatei konnte nicht gelesen werden.'];
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
                return ['success' => false, 'error' => 'Fehler beim Erstellen der Test-Konfiguration.'];
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
            throw new Exception('Konfigurationsdatei konnte nicht gelesen werden.');
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
            throw new Exception('Fehler beim Ersetzen der Konfiguration.');
        }

        file_put_contents($this->configFile, $newConfigContent);
    }

    /**
     * Setup abschließen
     */
    private function completeSetup()
    {
        return $this->renderSetupStep('complete', [
            'title' => 'Setup - Abgeschlossen',
            'success' => 'Setup wurde erfolgreich abgeschlossen!',
            'login_username' => 'super.admin',
            'login_password' => 'Test1000!',
            'login_email' => 'super.admin@test.local'
        ]);
    }

    /**
     * Rendert Setup-Template
     */
    private function renderSetupStep($step, $data = [])
    {
        // Standard-Variablen initialisieren um "Undefined array key" Fehler zu vermeiden
        $defaultData = [
            'setup_step' => $step,
            'title' => 'Setup',
            'step' => null,
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
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        Flight::latte()->render('setup.latte', $templateData);
    }
}