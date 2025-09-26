<?php

namespace App\Controllers;

use App\Models\User;
use App\Utils\SessionUtil;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\TranslationUtil;
use App\Utils\SecurityMetrics;
use ORM;
use Flight;
use Exception;

/**
 * Class Name: AdminController
 *
 * Controller Klasse für Methoden im Admin Kontext
 *
 * @package App\Controllers
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class AdminController
{

    /**
     * Security Dashboard für Admins
     */
    public function showSecurityDashboard()
    {
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        /*
        $securityData = SecurityMetrics::getSecurityDashboardData();

        Flight::latte()->render('admin/security_dashboard.latte', [
            'title' => 'Security Dashboard',
            'user' => $user,
            'sessionTimeout' => SessionUtil::getRemainingTime(),
            'securityData' => $securityData,
            'summary' => $securityData['summary'],
            'alerts' => $securityData['alerts'],
            'metrics' => $securityData['metrics']
        ]);
        */
        try {
            error_log("DEBUG: Starting Security Dashboard");

            $securityData = SecurityMetrics::getSecurityDashboardData();
            error_log('Security Data generated successfully');

            // DEBUG: Template-Variablen vorbereiten
            $templateVars = [
                'title' => 'Security Dashboard',
                'user' => SessionUtil::get('user'),
                'sessionTimeout' => SessionUtil::getRemainingTime(),
                'securityData' => $securityData,
                'summary' => $securityData['summary'],
                'alerts' => $securityData['alerts'],
                'metrics' => $securityData['metrics']
            ];

            error_log("DEBUG: Template vars prepared: " . print_r(array_keys($templateVars), true));

            // DEBUG: Template rendern
            error_log("DEBUG: About to render template");
            Flight::latte()->render('admin/security_dashboard.latte', $templateVars);
            //Flight::latte()->render('admin/simple_security_dashboard.latte', $templateVars);
            error_log("DEBUG: Template rendered successfully");
        } catch (Exception $e) {
            error_log("ERROR in Security Dashboard: " . $e->getMessage());
            error_log("ERROR Stack Trace: " . $e->getTraceAsString());

            // Fallback: Simple Error Page
            Flight::latte()->render('admin/simple_error.latte', [
                'title' => 'Security Dashboard Error',
                'error' => $e->getMessage(),
                'user' => SessionUtil::get('user'),
                'sessionTimeout' => SessionUtil::getRemainingTime()
            ]);
        }
    }

    /**
     * Security Metrics API für AJAX Updates
     */
    public function getSecurityMetrics()
    {
        $user = User::findUserById(SessionUtil::get("user")["id"]);

        $timeframe = $_GET['timeframe'] ?? '24h';

        switch ($timeframe) {
            case '1h':
                $data = SecurityMetrics::getHourlyMetrics();
                break;
            case '7d':
                $data = SecurityMetrics::getWeeklyMetrics();
                break;
            default:
                $data = SecurityMetrics::getSecurityDashboardData();
        }

        Flight::json($data);
    }

    /**
     * Settings page methods
     */

    /**
     * Displays the application settings page for administrators.
     *
     * This method checks if the user is logged in and has admin privileges.
     * If so, it loads the mail configuration settings and renders them on
     * the admin settings page. If the user is not logged in, or does not
     * have admin privileges, it redirects to the login page or displays a
     * 403 error.
     */

    public function showSettings()
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $roles = explode(",", $user->roles);
            if (in_array("Admin", $roles)) {
                $configFile = "../config.php";
                $config = include $configFile;
                $isWritable = is_writable($configFile);
                Flight::latte()->render("admin/settings.latte", [
                    "mail" => $config["mail"],
                    "bruteForceSettings" => $config["bruteForceSettings"],
                    "application" => $config["application"],
                    "logging" => $config["logging"],
                    "ldap" => $config["ldapSettings"],
                    "title" => "Settings",
                    "user" => $user,
                    "sessionTimeout" => SessionUtil::getRemainingTime(),
                    "configWritable" => $isWritable,
                ]);
            } else {
                http_response_code(403);
                throw new \Exception(TranslationUtil::t("error1"), 403);
            }
        } else {
            Flight::redirect("/login");
        }
    }


    /**
     * Updates the LDAP settings in the configuration file based on the provided form data.
     *
     * @param array $formData An associative array containing the new LDAP settings:
     *                        - 'ldapHost': The hostname of the LDAP server (string).
     *                        - 'ldapPort': The port number for the LDAP server (integer, defaults to 636 if null).
     *                        - 'domainPrefix': The domain prefix for LDAP authentication (string).
     *
     * @throws Exception If the configuration file cannot be read or if there is an error
     *                   while replacing the configuration content.
     *
     * This method performs the following steps:
     * 1. Verifies if the user is logged in by checking the session. If not, redirects to the login page.
     * 2. Retrieves the current user from the session.
     * 3. Constructs a new LDAP configuration array based on the provided form data.
     * 4. Reads the existing configuration file and replaces the `$ldapSettings` array with the new configuration.
     * 5. Saves the updated configuration back to the file.
     * 6. Logs the action for auditing purposes.
     * 7. Renders the settings page with a success message and updated configuration data.
     *
     * If the user is not found or the session is invalid, the method redirects to the login page.
     */
    public function updateLdapSettings($formData)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $newConfig = [
                "ldapHost" => $formData["ldapHost"] === null ? '' : $formData["ldapHost"],
                "ldapPort" => $formData["ldapPort"] === null ? 636  : (int) $formData["ldapPort"],
                "domainPrefix" => $formData["domainPrefix"] === null ? '' : addslashes($formData["domainPrefix"]),
            ];

            // Alte Konfiguration einlesen
            $configFile = "../config.php";
            $isWritable = is_writable($configFile);
            $config = include $configFile;
            // Alte Datei als Text laden
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                throw new Exception(TranslationUtil::t("error2"));
            }

            // Aktuelles `$ldapSettings`-Array suchen und ersetzen
            $pattern = '/(\$ldapSettings\s*=\s*\[)(.*?)(\];)/s';

            // Neues Mail-Array als formatierter PHP-Code
            $newldapArray = var_export($newConfig, true);
            $newldapArray = preg_replace("/^array \(/", "[", $newldapArray);
            $newldapArray = preg_replace('/\)$/', "]", $newldapArray);
            $newldapArray = preg_replace('/=> \n\s+/', "=> ", $newldapArray); // Mehrzeilige Werte schöner formatieren

            // Neuen Mail-Block zusammenbauen
            $replacement = '$ldapSettings = ' . $newldapArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new Exception(TranslationUtil::t("error3"));
            }

            // Datei mit neuem Inhalt speichern
            file_put_contents($configFile, $newConfigContent);

            // nue config laden
            $savedconfig = include $configFile;

            // log action
            LogUtil::logAction(
                LogType::AUDIT,
                "AdminController",
                "updateLogSettings",
                "SUCCESS: saved Logsettings.",
                $user->username
            );

            // template redner und meldung ausgeben
            Flight::latte()->render("admin/settings.latte", [
                "success" => TranslationUtil::t("settings.success1"),
                "mail" => $savedconfig["mail"],
                "bruteForceSettings" => $savedconfig["bruteForceSettings"],
                "application" => $savedconfig["application"],
                "logging" => $savedconfig["logging"],
                "ldap" => $savedconfig["ldapSettings"],
                "title" => "Settings",
                "user" => $user,
                "sessionTimeout" => SessionUtil::getRemainingTime(),
                "configWritable" => $isWritable,
            ]);
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Updates the logging settings.
     *
     * This method checks if the user is logged in and has the necessary
     * privileges. If so, it updates the logging settings based on the
     * provided form data. The method reads the existing configuration
     * file, modifies the logging settings, and writes the updated
     * configuration back to the file. If successful, it renders the
     * settings page with a success message. If the user is not authenticated,
     * it redirects to the login page.
     *
     * @param array $formData An associative array containing the new logging
     * settings, with keys: 'enableSqlLogging', 'enableRequestLogging',
     * 'enableAuditLogging', 'enableMailLogging', and 'enableSystemLogging'.
     * @throws Exception If the configuration file cannot be read or updated.
     */
    public function updateLogSettings($formData)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $newConfig = [
                "enableSqlLogging" => $formData["enableSqlLogging"] === null ? false : true,
                "enableRequestLogging" => $formData["enableRequestLogging"] === null ? false : true,
                "enableAuditLogging" => $formData["enableAuditLogging"] === null ? false : true,
                "enableMailLogging" => $formData["enableMailLogging"] === null ? false : true,
                "enableSystemLogging" => $formData["enableSystemLogging"] === null ? false : true,
            ];

            // Alte Konfiguration einlesen
            $configFile = "../config.php";
            $isWritable = is_writable($configFile);
            $config = include $configFile;
            // Alte Datei als Text laden
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                throw new Exception(TranslationUtil::t("error2"));
            }

            // Aktuelles `$logging`-Array suchen und ersetzen
            $pattern = '/(\$logging\s*=\s*\[)(.*?)(\];)/s';

            // Neues Mail-Array als formatierter PHP-Code
            $newLoggingArray = var_export($newConfig, true);
            $newLoggingArray = preg_replace("/^array \(/", "[", $newLoggingArray);
            $newLoggingArray = preg_replace('/\)$/', "]", $newLoggingArray);
            $newLoggingArray = preg_replace('/=> \n\s+/', "=> ", $newLoggingArray); // Mehrzeilige Werte schöner formatieren

            // Neuen Mail-Block zusammenbauen
            $replacement = '$logging = ' . $newLoggingArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new Exception(TranslationUtil::t("error3"));
            }

            // Datei mit neuem Inhalt speichern
            file_put_contents($configFile, $newConfigContent);

            // nue config laden
            $savedconfig = include $configFile;

            // log action
            LogUtil::logAction(
                LogType::AUDIT,
                "AdminController",
                "updateLogSettings",
                "SUCCESS: saved Logsettings.",
                $user->username
            );

            // template redner und meldung ausgeben
            Flight::latte()->render("admin/settings.latte", [
                "success" => TranslationUtil::t("settings.success1"),
                "mail" => $savedconfig["mail"],
                "bruteForceSettings" => $savedconfig["bruteForceSettings"],
                "application" => $savedconfig["application"],
                "logging" => $savedconfig["logging"],
                "ldap" => $savedconfig["ldapSettings"],
                "title" => "Settings",
                "user" => $user,
                "sessionTimeout" => SessionUtil::getRemainingTime(),
                "configWritable" => $isWritable,
            ]);
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Updates the mail configuration settings.
     *
     * This method checks if the user is logged in and has the necessary
     * privileges. If so, it updates the mail configuration settings based on
     * the provided form data. The method reads the existing configuration
     * file, modifies the mail settings, and writes the updated configuration
     * back to the file. If successful, it renders the settings page with a
     * success message. If the user is not authenticated, it redirects to the
     * login page.
     *
     * @param array $formData An associative array containing the new mail
     * settings, with keys: 'host', 'username', 'password', 'encryption',
     * 'port', 'fromEmail', 'fromName', and 'enableWelcomeMail'.
     * @throws Exception If the configuration file cannot be read or updated.
     */
    public function updateMailSettings($formData)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $newConfig = [
                "host" => $formData["host"],
                "username" => $formData["username"],
                "password" => $formData["password"],
                "encryption" => $formData["encryption"],
                "port" => (int) $formData["port"],
                "fromEmail" => $formData["fromEmail"],
                "fromName" => $formData["fromName"],
                "enableWelcomeMail" => isset($formData["enableWelcomeMail"]),
            ];

            // Alte Konfiguration einlesen
            $configFile = "../config.php";
            $isWritable = is_writable($configFile);
            $config = include $configFile;
            // Alte Datei als Text laden
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                throw new Exception(TranslationUtil::t("error2"));
            }

            // Aktuelles `$mail`-Array suchen und ersetzen
            $pattern = '/(\$mail\s*=\s*\[)(.*?)(\];)/s';

            // Neues Mail-Array als formatierter PHP-Code
            $newMailArray = var_export($newConfig, true);
            $newMailArray = preg_replace("/^array \(/", "[", $newMailArray);
            $newMailArray = preg_replace('/\)$/', "]", $newMailArray);
            $newMailArray = preg_replace('/=> \n\s+/', "=> ", $newMailArray); // Mehrzeilige Werte schöner formatieren

            // Neuen Mail-Block zusammenbauen
            $replacement = '$mail = ' . $newMailArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new Exception(TranslationUtil::t("error3"));
            }

            // Datei mit neuem Inhalt speichern
            file_put_contents($configFile, $newConfigContent);

            // nue config laden
            $savedconfig = include $configFile;

            // log action
            LogUtil::logAction(
                LogType::AUDIT,
                "AdminController",
                "updateMailSettings",
                "SUCCESS: saved Mailsettings.",
                $user->username
            );

            // template redner und meldung ausgeben
            Flight::latte()->render("admin/settings.latte", [
                "success" => TranslationUtil::t("settings.success1"),
                "mail" => $savedconfig["mail"],
                "bruteForceSettings" => $savedconfig["bruteForceSettings"],
                "application" => $savedconfig["application"],
                "logging" => $savedconfig["logging"],
                "ldap" => $savedconfig["ldapSettings"],
                "title" => "Settings",
                "user" => $user,
                "sessionTimeout" => SessionUtil::getRemainingTime(),
                "configWritable" => $isWritable,
            ]);
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Updates the application settings.
     *
     * @param array $formData An associative array with the new settings.
     *                        The array should contain the following keys:
     *                        - appUrl
     *                        - sessionTimeout
     *
     * @throws Exception If the configuration file could not be read or written.
     */
    public function updateApplicationSettings($formData)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $newConfig = [
                "appUrl" => $formData["appUrl"],
                "sessionTimeout" => (int) $formData["sessionTimeout"],
                "allowPublicRegister" => (bool) $formData["allowPublicRegister"],
                "allowPublicPasswordReset" => (bool) $formData["allowPublicPasswordReset"]
            ];

            // Alte Konfiguration einlesen
            $configFile = "../config.php";
            $isWritable = is_writable($configFile);
            $config = include $configFile;
            // Alte Datei als Text laden
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                throw new Exception(TranslationUtil::t("error2"));
            }

            // Aktuelles `$application`-Array suchen und ersetzen
            $pattern = '/(\$application\s*=\s*\[)(.*?)(\];)/s';

            // Neues application-Array als formatierter PHP-Code
            $newApplicationArray = var_export($newConfig, true);
            $newApplicationArray = preg_replace("/^array \(/", "[", $newApplicationArray);
            $newApplicationArray = preg_replace('/\)$/', "]", $newApplicationArray);
            $newApplicationArray = preg_replace('/=> \n\s+/', "=> ", $newApplicationArray); // Mehrzeilige Werte schöner formatieren

            // Neuen application-Block zusammenbauen
            $replacement = '$application = ' . $newApplicationArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new Exception(TranslationUtil::t("error3"));
            }

            // Datei mit neuem Inhalt speichern
            file_put_contents($configFile, $newConfigContent);

            // nue config laden
            $savedconfig = include $configFile;

            // log action
            LogUtil::logAction(
                LogType::AUDIT,
                "AdminController",
                "updateApplicationSettings",
                "SUCCESS: saved Applicationsettings.",
                $user->username
            );

            // template redner und meldung ausgeben
            Flight::latte()->render("admin/settings.latte", [
                "success" => TranslationUtil::t("settings.success1"),
                "mail" => $savedconfig["mail"],
                "bruteForceSettings" => $savedconfig["bruteForceSettings"],
                "application" => $savedconfig["application"],
                "logging" => $savedconfig["logging"],
                "ldap" => $savedconfig["ldapSettings"],
                "title" => "Settings",
                "user" => $user,
                "sessionTimeout" => SessionUtil::getRemainingTime(),
                "configWritable" => $isWritable,
            ]);
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Updates the brute force settings.
     *
     * This method checks if the user is logged in and has the necessary
     * privileges. If so, it updates the brute force settings based on the
     * provided form data. The method reads the existing configuration file,
     * modifies the brute force settings, and writes the updated configuration
     * back to the file. If successful, it renders the settings page with a
     * success message. If the user is not authenticated, it redirects to the
     * login page.
     *
     * @param array $formData An associative array containing the new brute force
     * settings, with keys: 'enableBruteForce', 'maxAttempts', and 'lockTime'.
     * @throws Exception If the configuration file cannot be read or updated.
     */
    public function updateBruteForceSettings($formData)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $newConfig = [
                "enableBruteForce" => $formData["enableBruteForce"] === null ? false : true,
                "maxAttempts" => (int) $formData["maxAttempts"],
                "lockTime" => (int) $formData["lockTime"],
            ];

            // Alte Konfiguration einlesen
            $configFile = "../config.php";
            $isWritable = is_writable($configFile);
            $config = include $configFile;
            // Alte Datei als Text laden
            $configContent = file_get_contents($configFile);
            if ($configContent === false) {
                throw new Exception(TranslationUtil::t("error2"));
            }

            // Aktuelles `$bruteForceSettings`-Array suchen und ersetzen
            $pattern = '/(\$bruteForceSettings\s*=\s*\[)(.*?)(\];)/s';

            // Neues bruteForceSettings-Array als formatierter PHP-Code
            $newBruteForceArray = var_export($newConfig, true);
            $newBruteForceArray = preg_replace("/^array \(/", "[", $newBruteForceArray);
            $newBruteForceArray = preg_replace('/\)$/', "]", $newBruteForceArray);
            $newBruteForceArray = preg_replace('/=> \n\s+/', "=> ", $newBruteForceArray); // Mehrzeilige Werte schöner formatieren

            // Neuen bruteForceSettings-Block zusammenbauen
            $replacement = '$bruteForceSettings = ' . $newBruteForceArray . ";";

            // Neuen Config-Code generieren
            $newConfigContent = preg_replace($pattern, $replacement, $configContent);

            if ($newConfigContent === null) {
                throw new Exception(TranslationUtil::t("error3"));
            }

            // Datei mit neuem Inhalt speichern
            file_put_contents($configFile, $newConfigContent);

            // nue config laden
            $savedconfig = include $configFile;

            // log action
            LogUtil::logAction(
                LogType::AUDIT,
                "AdminController",
                "updateBruteForceSettings",
                "SUCCESS: saved BruteForce sttings.",
                $user->username
            );

            // template redner und meldung ausgeben
            Flight::latte()->render("admin/settings.latte", [
                "success" => TranslationUtil::t("settings.success1"),
                "mail" => $savedconfig["mail"],
                "bruteForceSettings" => $savedconfig["bruteForceSettings"],
                "application" => $savedconfig["application"],
                "logging" => $savedconfig["logging"],
                "ldap" => $savedconfig["ldapSettings"],
                "title" => "Settings",
                "user" => $user,
                "sessionTimeout" => SessionUtil::getRemainingTime(),
                "configWritable" => $isWritable,
            ]);
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Users page Methods
     */

    /**
     * Zeigt die Benutzerliste an
     *
     * Läd die Liste aller Benutzer und gibt sie an die View weiter.
     */
    public function showUsers()
    {
        if (SessionUtil::get("iuser")["id"] === null) {
            Flight::redirect("/login");
        }
        $user = User::findUserById(SessionUtil::get("user")["id"]);
        if ($user !== false) {
            $roles = explode(",", $user->roles);
            if (in_array("Admin", $roles)) {
                $users = User::getAllUsers();
                Flight::render("admin/users", ["users" => $users]);
            } else {
                //Flight::redirect('/login');
                echo "403";
            }
        } else {
            Flight::redirect("/login");
        }
    }

    /**
     * Renders the create user page.
     *
     * This method renders the createUser.latte template with the given parameters.
     * It expects the user to be logged in. If the user is not logged in, it redirects to the login page.
     * The parameter 'title' is set to 'Create User' and 'user' is set to the current user session.
     * 'sessionTimeout' is set to the remaining session timeout and 'roles' is set to the list of all roles.
     */
    public function showCreateUser()
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }

        $roles = ORM::for_table("roles")->find_array();

        Flight::latte()->render("admin/createUser.latte", [
            "title" => TranslationUtil::t("user.new.title"),
            "user" => SessionUtil::get("user"),
            "sessionTimeout" => SessionUtil::getRemainingTime(),
            "roles" => $roles,
        ]);
    }

    /**
     * Creates a new user and stores it in the database.
     *
     * Requires the parameters username, email, firstname, lastname, password, status and roles.
     * Checks if the user already exists and prevents the creation of a new user if so.
     * If the user is created successfully, it returns a json response with a success message.
     * If there is an error while saving the new user, it returns a json response with an error message.
     */
    public function createUser()
    {
        $email = $_POST["email"];
        $user = $_POST["username"];
        $firstname = $_POST["firstname"];
        $lastname = $_POST["lastname"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
        $status = isset($_POST["status"]) ? $_POST["status"] : 0;
        $roles = isset($_POST["roles"]) ? $_POST["roles"] : "";
        $userCheck = User::checkIfUserExists($user, $email);
        $ldapEnabled = isset($_POST["ldapEnabled"]) ? $_POST["ldapEnabled"] : false;

        if ($userCheck !== "false") {
            Flight::json(["success" => false, "message" => TranslationUtil::t("user.new.error1")]);
            return;
        }

        $newUser = User::createUser($user, $email, $firstname, $lastname, $status, $password, $roles, $ldapEnabled == true ? 1 : 0);

        if (!$newUser) {
            Flight::json(["success" => false, "message" => TranslationUtil::t("user.new.error2")]);
        }

        LogUtil::logAction(LogType::AUDIT, "AdminController", "createUser", "SUCCESS: created new user.");
        Flight::json(["success" => true, "message" => TranslationUtil::t("user.new.success")]);
    }

    /**
     * Renders the edit user page.
     *
     * This method renders the editUser.latte template with the given parameters.
     * It expects the user to be logged in. If the user is not logged in, it redirects to the login page.
     * The parameter 'title' is set to 'Edit User' and 'user' is set to the current user session.
     * 'sessionTimeout' is set to the remaining session timeout and 'roles' is set to the list of all roles.
     * The user to be edited is retrieved by id from the database and passed to the template.
     */
    public function showEditeUser($userId)
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }

        $roles = ORM::for_table("roles")->find_array();

        Flight::latte()->render("admin/editUser.latte", [
            "title" => TranslationUtil::t("user.edit.title"),
            "user" => SessionUtil::get("user"),
            "sessionTimeout" => SessionUtil::getRemainingTime(),
            "roles" => $roles,
            "user" => SessionUtil::get("user"),
            "userToEdit" => User::findUserById($userId),
        ]);
    }

    /**
     * Updates a user by ID.
     *
     * It expects a valid user session to exist.
     * It expects the following POST parameters:
     * - id
     * - email
     * - username
     * - firstname
     * - lastname
     * - status
     * - roles
     * - password (optional)
     *
     * If the update is successful, it renders the page with a success message.
     * Otherwise, it renders the page with an error message.
     *
     * @return array JSON response containing the update status and a message.
     */
    public static function updateUser()
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::redirect("/login");
        }

        $userId = $_POST["id"];
        $email = $_POST["email"];
        $username = $_POST["username"];
        $firstname = $_POST["firstname"];
        $lastname = $_POST["lastname"];
        $status = isset($_POST["status"]) ? $_POST["status"] : 0;
        $roles = isset($_POST["roles"]) ? $_POST["roles"] : "";
        $password = isset($_POST["password"]) ? password_hash($_POST["password"], PASSWORD_DEFAULT) : null;
        $ldapEnabled = isset($_POST["ldapEnabled"]) ? $_POST["ldapEnabled"] : 0;

        $erg = User::updateuser($userId, $email, $username, $firstname, $lastname, $status, $roles, $password, $ldapEnabled);

        // log action
        LogUtil::logAction(LogType::AUDIT, "AdminController", "updateUser", "SUCCESS: updated user " . $username . ".");

        if ($erg === true) {
            Flight::json(["success" => true, "message" => TranslationUtil::t('user.edit.success') . $ldapEnabled . ""]);
        } else {
            Flight::json(["success" => false, "message" => TranslationUtil::t("user.edit.error1")]);
        }
    }

    /**
     * Fetches and displays a paginated list of users.
     *
     * This method retrieves user data based on the specified page number,
     * page size, and optional search criteria. It calculates the offset for pagination
     * and fetches users from the database. If the request is an AJAX request, it returns
     * a JSON response containing the user data and total user count. Otherwise, it renders
     * the user list page with the retrieved data.
     */

    public function fetchUsersPaged()
    {
        $page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
        $pageSize = isset($_GET["pageSize"]) ? max(1, (int) $_GET["pageSize"]) : 10;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        $offset = ($page - 1) * $pageSize;

        $erg = User::getUsersPaged($offset, $pageSize, isset($_GET["search"]) ? trim($_GET["search"]) : null);

        if ($erg) {
            $totalUsers = $erg["totalUsers"];
            $users = $erg["users"];
        } else {
            $totalUsers = 0;
            $users = [];
        }

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "fetchUsersPaged",
            "SUCCESS: fetched list of users.",
            SessionUtil::get("user")["username"]
        );

        // Prüfen, ob es eine AJAX-Anfrage ist
        if (
            !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) == "xmlhttprequest"
        ) {
            // JSON-Antwort für AJAX
            Flight::json([
                "users" => array_map(function ($user) {
                    return [
                        "id" => $user->id,
                        "status" => $user->status,
                        "username" => $user->username,
                        "email" => $user->email,
                        "mfaEnabled" => $user->mfaEnabled,
                        "mfaEnforced" => $user->mfaEnforced,
                        "mfaSecret" => $user->mfaSecret,
                    ];
                }, $users),
                "totalUsers" => $totalUsers,
            ]);
        } else {
            Flight::latte()->render("admin/users.latte", [
                "users" => $users,
                "page" => $page,
                "pageSize" => $pageSize,
                "totalUsers" => $totalUsers,
                "search" => $search,
                "title" => "Users",
                "message" => empty($users) ? "No users found for criteria: " . $search . "." : null,
                "user" => SessionUtil::get("user"),
                "sessionTimeout" => SessionUtil::getRemainingTime(),
            ]);
        }
    }

    /**
     * Deletes a user from the database.
     *
     * This method is called through an AJAX request from the user list page.
     * It retrieves the ID of the user to delete from the request body and
     * calls the User model's deleteUser method to delete the user. If the
     * deletion is successful, it returns a JSON response with a status of
     * true. Otherwise, it returns a JSON response with a status of false and
     * an error message.
     *
     * @return array JSON response containing the deletion status and
     * optional error message.
     */
    public function deleteUser()
    {
        $userIdToDelete = isset($_POST["id"]) ? (int) $_POST["id"] : null;

        // Falls keine ID vorhanden ist -> Fehler zurückgeben
        if (!$userIdToDelete) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        // Benutzer löschen
        $erg = User::deleteUser($userIdToDelete);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "deleteUser",
            "SUCCESS: deleted user " . $userIdToDelete . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim Löschen des Benutzers.");
        }
    }

    /**
     * Disables MFA for a user.
     *
     * This method is called through an AJAX request from the user list page.
     * It retrieves the ID of the user to disable MFA for from the request body
     * and calls the User model's disableMfaForUser method to disable MFA for
     * the user. If the disabling of MFA is successful, it returns a JSON
     * response with a status of true. Otherwise, it returns a JSON response
     * with a status of false and an error message.
     *
     * @return array JSON response containing the disabling status and
     * optional error message.
     */
    public static function disableMfa()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::disableMfaForUser($userId);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "disableMfa",
            "SUCCESS: disabled 2FA for user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des 2FA Status.");
        }
    }

    /**
     * Enables MFA for a user.
     *
     * This method is triggered through an AJAX request from the user list page.
     * It retrieves the user ID from the request body and calls the User model's
     * enableMfaForUser method to enable MFA for the specified user. If the operation
     * is successful, it returns a JSON response with a status of true. Otherwise,
     * it returns a JSON response with a status of false and an error message.
     *
     * @return array JSON response containing the enabling status and optional error message.
     */
    public static function enableMfa()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::enableMfaForUser($userId);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "enableMfa",
            "SUCCESS: enabled 2FA for user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des 2FA Status.");
        }
    }

    /**
     * Enforces MFA for a user.
     *
     * This method is triggered through an AJAX request from the user list page.
     * It retrieves the user ID from the request body and calls the User model's
     * enforceMfa method to enforce MFA for the specified user. If the operation
     * is successful, it returns a JSON response with a status of true. Otherwise,
     * it returns a JSON response with a status of false and an error message.
     *
     * @return array JSON response containing the enforcing status and optional error message.
     */
    public static function enforceMfa()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::enforceMfa($userId);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController.php",
            "enforeMfa",
            "SUCCESS: enforced 2FA for user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des 2FA Status.");
        }
    }

    /**
     * Disables enforcing MFA for a user.
     *
     * This method is called through an AJAX request from the user list page.
     * It retrieves the user ID from the request body and calls the User model's
     * unenforceMfa method to disable enforcing MFA for the user. If the
     * disabling of enforcing MFA is successful, it returns a JSON response with
     * a status of true. Otherwise, it returns a JSON response with a status of
     * false and an error message.
     *
     * @return array JSON response containing the disabling status and optional
     * error message.
     */
    public static function unenforceMfa()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::unenforceMfa($userId);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "unenforeMfa",
            "SUCCESS: unenforced 2FA for user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des 2FA Status.");
        }
    }

    /**
     * Disables a user account.
     *
     * This method is called through an AJAX request from the user list page.
     * It retrieves the user ID from the request body and calls the User model's
     * updateUserStatus method to disable the user account. If the disabling of
     * the user account is successful, it returns a JSON response with a status
     * of true. Otherwise, it returns a JSON response with a status of false and
     * an error message.
     *
     * @return array JSON response containing the disabling status and optional
     * error message.
     */
    public static function disableUser()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::updateUserStatus($userId, 0);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "disableUser",
            "SUCCESS: deactivated user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des Useraccount-Status.");
        }
    }

    /**
     * Enables a user account.
     *
     * This method is called through an AJAX request from the user list page.
     * It retrieves the user ID from the request body and calls the User model's
     * updateUserStatus method to enable the user account. If the enabling of the
     * user account is successful, it returns a JSON response with a status of
     * true. Otherwise, it returns a JSON response with a status of false and an
     * error message.
     *
     * @return array JSON response containing the enabling status and optional
     * error message.
     */
    public static function enableUser()
    {
        $userId = isset($_POST["id"]) ? $_POST["id"] : null;

        if (!$userId) {
            return self::handleResponse(false, "Ungültige Benutzer-ID.");
        }

        $erg = User::updateUserStatus($userId, 1);

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "enableUser",
            "SUCCESS: activated user " . $userId . ".",
            SessionUtil::get("user")["username"]
        );

        if ($erg) {
            return self::handleResponse(true);
        } else {
            return self::handleResponse(false, "Fehler beim speichern des Useraccount-Status.");
        }
    }

    /**
     * Helper method to handle the response for AJAX and non-AJAX requests.
     *
     * This method checks if the request is an AJAX request. If it is,
     * it returns a JSON response with the success status. If it's not
     * an AJAX request, it reloads the user list page with the current
     * pagination and search criteria and displays any error messages
     * if provided.
     *
     * @param bool $success Indicates the success of the operation.
     * @param string|null $errorMessage Optional error message to be displayed
     * in case of failure.
     */
    private static function handleResponse(bool $success, ?string $errorMessage = null)
    {
        if (
            !empty($_SERVER["HTTP_X_REQUESTED_WITH"]) &&
            strtolower($_SERVER["HTTP_X_REQUESTED_WITH"]) === "xmlhttprequest"
        ) {
            Flight::json(["success" => $success]);
            return;
        }

        // Falls kein AJAX-Request -> Benutzerliste erneut abrufen
        $page = isset($_GET["page"]) ? max(1, (int) $_GET["page"]) : 1;
        $pageSize = isset($_GET["pageSize"]) ? max(1, (int) $_GET["pageSize"]) : 10;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        $offset = ($page - 1) * $pageSize;
        $erg = User::getUsersPaged($offset, $pageSize, $search);

        $totalUsers = $erg["totalUsers"] ?? 0;
        $users = $erg["users"] ?? [];

        Flight::latte()->render("admin/users.latte", [
            "users" => $users,
            "page" => $page,
            "pageSize" => $pageSize,
            "totalUsers" => $totalUsers,
            "search" => $search,
            "title" => "Users",
            "error" => $success ? null : $errorMessage,
            "user" => SessionUtil::get("user"),
            "sessionTimeout" => SessionUtil::getRemainingTime(),
        ]);
    }

    /**
     * Renders the roles page.
     *
     * This method renders the roles page with the given parameters.
     * It expects the user to be logged in. If the user is not logged in, it redirects to the login page.
     * The parameter 'title' is set to 'Roles' and 'user' is set to the current user session.
     * 'sessionTimeout' is set to the remaining session timeout.
     */
    public static function showRoles()
    {
        Flight::latte()->render("admin/roles.latte", [
            "title" => TranslationUtil::t("roles.title"),
            "user" => SessionUtil::get("user"),
            "sessionTimeout" => SessionUtil::getRemainingTime(),
        ]);
    }

    /**
     * Überprüft, ob eine Rolle (in der Spalte "roles" der Tabelle "users")
     * noch Benutzern zugewiesen ist.
     *
     * @return array mit einem boolean-key "inUse", der angibt, ob die Rolle
     *         noch in Benutzung ist.
     */
    public function checkUsers()
    {
        ORM::configure("logging", true);
        $roleName = Flight::request()->query["role"] ?? null;

        if (!$roleName) {
            Flight::json(["error" => TranslationUtil::t('roles.error3')], 400);
            return;
        }

        // Prüfen, ob die Rolle irgendwo in der Spalte `roles` enthalten ist
        $userCount = ORM::for_table("users")
            ->where_raw("FIND_IN_SET(?, roles)", [$roleName])
            ->count();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
        }

        Flight::json(["inUse" => $userCount > 0]);
    }

    /**
     * Fetches a paginated list of roles from the database.
     *
     * This method is called through an AJAX request from the role list page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the Role model's find_array method to retrieve the
     * matching roles. It then calculates the total number of pages and returns
     * a JSON response containing the list of roles, total number of roles,
     * total number of pages, current page and page size.
     *
     * @return array JSON response containing the list of roles, total number of
     * roles, total number of pages, current page and page size.
     */
    public static function listRoles()
    {
        ORM::configure("logging", true);
        $page = isset($_GET["page"]) ? (int) $_GET["page"] : 1;
        $pageSize = isset($_GET["pageSize"]) ? (int) $_GET["pageSize"] : 10;
        $search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

        // Zähle die gesamte Anzahl an Treffern ohne Limitierung
        $totalRoles = ORM::for_table("roles")
            ->where_like("roleName", "%$search%")
            ->count();

        // Führe die eigentliche Abfrage mit Limitierung durch
        $roles = ORM::for_table("roles")
            ->where_like("roleName", "%$search%")
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->find_array();

        // letzte query loggen
        $queries = ORM::get_query_log();

        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, "AdminController", "listRoles", $lastQuery);
        }

        // Berechne die Gesamtseitenzahl
        $totalPages = ceil($totalRoles / $pageSize);

        LogUtil::logAction(LogType::AUDIT, "AdminController", "listRoles", "SUCCESS: fetched list of roles.");

        Flight::json([
            "roles" => $roles,
            "total" => $totalRoles,
            "totalPages" => $totalPages,
            "page" => $page,
            "pageSize" => $pageSize,
        ]);
    }

    /**
     * Adds a new role to the database.
     *
     * This method retrieves the role name from the request body and verifies
     * that it is not empty. If the role name is missing, a JSON response with
     * a status of false and an error message is returned. The method checks if
     * a role with the same name already exists in the database; if so, it
     * returns a JSON response indicating the role already exists. If the role
     * name is unique, it creates the new role in the database, logs the action,
     * and returns a JSON response with a status of true and a success message.
     *
     * @return array JSON response containing the creation status and optional
     * error message or role data.
     */
    public static function addRole()
    {
        $roleName = trim($_POST["roleName"]);
        if (!$roleName) {
            Flight::json(["success" => false, "message" => "Role name is required."]);
            return;
        }
        ORM::configure("logging", true);
        if (ORM::for_table("roles")->where("roleName", $roleName)->find_one()) {
            // letzte query loggen
            ORM::configure("logging", true);
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, "LogController", "listLogs", $lastQuery);
            }
            Flight::json(["success" => false, "message" => TranslationUtil::t("roles.error5")]);
            return;
        }

        $role = ORM::for_table("roles")->create();
        $role->roleName = $roleName;
        $role->save();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, "LogController", "listLogs", $lastQuery);
        }

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "addRole",
            "SUCCESS: added role " . $roleName . ".",
            SessionUtil::get("user")["username"]
        );

        Flight::json(["success" => true, "message" => TranslationUtil::t('roles.error6'), "role" => $role->as_array()]);
    }

    /**
     * Deletes a role from the database.
     *
     * This method is called through an AJAX request from the role list page.
     * It retrieves the ID of the role to delete from the request body and
     * calls the Role model's delete method to delete the role. If the role is
     * assigned to users, it returns a JSON response with a status of false and
     * an error message. Otherwise, it returns a JSON response with a status of
     * true and a success message.
     *
     * @return array JSON response containing the deletion status and
     * optional error message.
     */
    public static function deleteRole()
    {
        $roleId = (int) $_POST["roleId"];
        $role = ORM::for_table("roles")->find_one($roleId);

        if (!$role) {
            Flight::json(["success" => false, "message" => "Role not found."]);
            return;
        }
        ORM::configure("logging", true);
        $usersWithRole = ORM::for_table("users")
            ->where("roles", $role->roleName)
            ->count();
        // letzte query loggen
        ORM::configure("logging", true);
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, "LogController", "listLogs", $lastQuery);
        }
        if ($usersWithRole > 0) {
            Flight::json(["success" => false, "message" => "Cannot delete role. It is assigned to users."]);
            return;
        }

        $role->delete();
        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, "LogController", "listLogs", $lastQuery);
        }

        // log action
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "deleteRole",
            "SUCCESS: deleted role " . $role->roleName . ".",
            SessionUtil::get("user")["username"]
        );

        Flight::json(["success" => true, "message" => "Role deleted successfully."]);
    }

    /**
     * Bulk operations for user management
     */

    /**
     * Handles bulk user operations (delete, enable, disable, role assignment)
     */
    public static function bulkUserOperations()
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::json(["success" => false, "message" => "Unauthorized"]);
            return;
        }

        // NEUER CODE: JSON Request-Body lesen
        $input = file_get_contents('php://input');
        $jsonData = json_decode($input, true);

        // Fallback auf $_POST für Form-Requests
        $operation = $jsonData['operation'] ?? $_POST['operation'] ?? '';
        $userIds = $jsonData['userIds'] ?? $_POST['userIds'] ?? [];
        $options = $jsonData['options'] ?? $_POST['options'] ?? [];

        if (empty($userIds) || !is_array($userIds)) {
            LogUtil::logAction(
                LogType::SYSTEM,
                "AdminController",
                "bulkUserOperations",
                "No users selected - userIds: " . json_encode($userIds),
                SessionUtil::get("user")["username"]
            );
            Flight::json(["success" => false, "message" => "No users selected"]);
            return;
        }

        // Convert to integers and validate
        $userIds = array_map('intval', $userIds);
        $userIds = array_filter($userIds, function ($id) {
            return $id > 0;
        });

        $results = [];
        $success = 0;
        $failed = 0;
        $skipped = 0;

        switch ($operation) {
            case 'delete':
                $results = self::bulkDeleteUsers($userIds);
                break;

            case 'enable':
                $results = self::bulkToggleUsers($userIds, 1);
                break;

            case 'disable':
                $results = self::bulkToggleUsers($userIds, 0);
                break;

            case 'mfa_enforce':
                $results = self::bulkEnforceMfa($userIds, true);
                break;

            case 'mfa_unenforce':
                $results = self::bulkEnforceMfa($userIds, false);
                break;

            default:
                Flight::json(["success" => false, "message" => "Unknown operation: " . $operation]);
                return;
        }

        // Count results
        foreach ($results as $result) {
            if ($result['status'] === 'success') $success++;
            elseif ($result['status'] === 'failed') $failed++;
            elseif ($result['status'] === 'skipped') $skipped++;
        }

        // Log bulk operation
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "bulkUserOperations",
            sprintf(
                "BULK %s: %d success, %d failed, %d skipped",
                strtoupper($operation),
                $success,
                $failed,
                $skipped
            ),
            SessionUtil::get("user")["username"]
        );

        Flight::json([
            "success" => $success > 0,
            "operation" => $operation,
            "summary" => [
                "total" => count($userIds),
                "success" => $success,
                "failed" => $failed,
                "skipped" => $skipped
            ],
            "details" => $results,
            "message" => sprintf(
                "%s: %d successful, %d failed, %d skipped",
                ucfirst($operation),
                $success,
                $failed,
                $skipped
            )
        ]);
    }

    /**
     * Bulk delete users with protection for super.admin
     */
    private static function bulkDeleteUsers(array $userIds): array
    {
        $results = [];
        $currentUser = SessionUtil::get("user");

        foreach ($userIds as $userId) {
            // Get user info first
            $user = User::findUserById($userId);

            if (!$user) {
                $results[] = [
                    'userId' => $userId,
                    'status' => 'failed',
                    'reason' => 'User not found'
                ];
                continue;
            }

            // Protection: Can't delete super.admin
            if ($user['username'] === 'super.admin') {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'Cannot delete super.admin account'
                ];
                continue;
            }

            // Protection: Can't delete yourself
            if ($userId == $currentUser['id']) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'Cannot delete your own account'
                ];
                continue;
            }

            // Attempt deletion
            $deleteResult = User::deleteUser($userId);

            if ($deleteResult) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'success',
                    'reason' => 'User deleted successfully'
                ];
            } else {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'failed',
                    'reason' => 'Database error during deletion'
                ];
            }
        }

        return $results;
    }

    /**
     * Bulk enable/disable users
     */
    private static function bulkToggleUsers(array $userIds, int $status): array
    {
        $results = [];
        $statusText = $status ? 'enable' : 'disable';

        foreach ($userIds as $userId) {
            $user = User::findUserById($userId);

            if (!$user) {
                $results[] = [
                    'userId' => $userId,
                    'status' => 'failed',
                    'reason' => 'User not found'
                ];
                continue;
            }

            // Protection: Can't disable super.admin
            if ($user['username'] === 'super.admin' && $status === 0) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'Cannot disable super.admin account'
                ];
                continue;
            }

            // Check if change is needed
            if ($user['status'] == $status) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'User already ' . ($status ? 'enabled' : 'disabled')
                ];
                continue;
            }

            // Update status
            $updateResult = User::updateUserStatus($userId, $status);

            if ($updateResult) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'success',
                    'reason' => 'User ' . $statusText . 'd successfully'
                ];
            } else {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'failed',
                    'reason' => 'Database error during ' . $statusText
                ];
            }
        }

        return $results;
    }
    

    /**
     * Bulk MFA enforcement
     */
    private static function bulkEnforceMfa(array $userIds, bool $enforce): array
    {
        $results = [];
        $action = $enforce ? 'enforce' : 'unenforce';

        foreach ($userIds as $userId) {
            $user = User::findUserById($userId);

            if (!$user) {
                $results[] = [
                    'userId' => $userId,
                    'status' => 'failed',
                    'reason' => 'User not found'
                ];
                continue;
            }

            // Protection: Cannot enforce MFA for super.admin
            if ($user['username'] === 'super.admin') {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'Cannot enforce MFA for super.admin'
                ];
                continue;
            }

            // Check current enforcement status
            $currentlyEnforced = (bool) $user['mfaEnforced'];

            if ($currentlyEnforced === $enforce) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'skipped',
                    'reason' => 'MFA already ' . ($enforce ? 'enforced' : 'not enforced') . ' for user'
                ];
                continue;
            }

            // Update MFA enforcement
            $updateResult = $enforce ?
                User::enforceMfa($userId) :
                User::unenforceMfa($userId);

            if ($updateResult) {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'success',
                    'reason' => 'MFA ' . $action . 'ment updated successfully'
                ];
            } else {
                $results[] = [
                    'userId' => $userId,
                    'username' => $user['username'],
                    'status' => 'failed',
                    'reason' => 'Database error during MFA ' . $action . 'ment'
                ];
            }
        }

        return $results;
    }

    /**
     * Export users to CSV/Excel format
     */
    public static function exportUsers()
    {
        if (SessionUtil::get("user")["id"] === null) {
            Flight::json(["success" => false, "message" => "Unauthorized"]);
            return;
        }

        $format = $_GET['format'] ?? 'csv';
        $selectedIds = $_GET['userIds'] ?? null;

        // Get users (selected or all)
        if ($selectedIds && !empty($selectedIds)) {
            $userIds = explode(',', $selectedIds);
            $userIds = array_map('intval', $userIds);
            $users = User::getUsersByIds($userIds);
        } else {
            $users = User::getAllUsers();
        }

        if ($format === 'csv') {
            self::exportUsersCSV($users);
        } else {
            Flight::json(["success" => false, "message" => "Unsupported format"]);
        }
    }

    /**
     * Export users as CSV
     */
    private static function exportUsersCSV(array $users)
    {
        $filename = 'users_export_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');

        $output = fopen('php://output', 'w');

        // CSV header
        fputcsv($output, [
            'ID',
            'Username',
            'Email',
            'First Name',
            'Last Name',
            'Status',
            'Roles',
            'MFA Enabled',
            'MFA Enforced',
            'LDAP Enabled',
            'Created',
            'Last Login'
        ]);

        // User data
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['username'],
                $user['email'],
                $user['firstname'],
                $user['lastname'],
                $user['status'] ? 'Active' : 'Disabled',
                $user['roles'],
                $user['mfaEnabled'] ? 'Yes' : 'No',
                $user['mfaEnforced'] ? 'Yes' : 'No',
                $user['ldapEnabled'] ? 'Yes' : 'No',
                $user['created'] ?? '',
                $user['lastLogin'] ?? ''
            ]);
        }

        fclose($output);

        // Log export
        LogUtil::logAction(
            LogType::AUDIT,
            "AdminController",
            "exportUsers",
            "SUCCESS: exported " . count($users) . " users to CSV",
            SessionUtil::get("user")["username"]
        );

        exit;
    }
}
