<?php

namespace APP\Models;

use ORM;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\TranslationUtil;
use App\Utils\BackupCodeUtil;

/**
 * Class Name: User
 *
 * ORM Klasse für das Benutzer Objekt.
 *
 * @package App\Models
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class User extends ORM
{
    protected static $tableName = 'users'; // Der Tabellenname

    /**
     * Sets backup codes for a user identified by their ID.
     *
     * @param int $userId The ID of the user
     * @param string $hashedCodesJson JSON string of hashed backup codes
     * @return bool True if codes were successfully saved, false otherwise
     */
    public static function setBackupCodes(int $userId, string $hashedCodesJson): bool
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);
        if ($user !== false) {
            $user->mfaBackupCodes = $hashedCodesJson;
            $erg = $user->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setBackupCodes', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Gets the backup codes JSON for a user.
     *
     * @param int $userId The ID of the user
     * @return string|null JSON string of backup codes or null if not found
     */
    public static function getBackupCodes(int $userId): ?string
    {
        $user = self::findUserById($userId);
        if ($user !== false) {
            return $user->mfaBackupCodes;
        }
        return null;
    }

    /**
     * Updates backup codes for a user (e.g., after one is used).
     *
     * @param int $userId The ID of the user
     * @param string $updatedCodesJson Updated JSON string of backup codes
     * @return bool True if update was successful, false otherwise
     */
    public static function updateBackupCodes(int $userId, string $updatedCodesJson): bool
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);
        if ($user !== false) {
            $user->mfaBackupCodes = $updatedCodesJson;
            $erg = $user->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'updateBackupCodes', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Counts remaining backup codes for a user.
     *
     * @param int $userId The ID of the user
     * @return int Number of remaining unused backup codes
     */
    public static function countRemainingBackupCodes(int $userId): int
    {
        $backupCodesJson = self::getBackupCodes($userId);
        return BackupCodeUtil::countRemainingCodes($backupCodesJson);
    }

    /**
     * Resets (clears) all backup codes for a user.
     *
     * @param int $userId The ID of the user
     * @return bool True if reset was successful, false otherwise
     */
    public static function clearBackupCodes(int $userId): bool
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);
        if ($user !== false) {
            $user->mfaBackupCodes = null;
            $erg = $user->save();

            // Log query
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'clearBackupCodes', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Setzt den Verifizierungs-Token für einen Benutzer.
     *
     * @param int $userId Die ID des Benutzers
     * @param string $token Der Verifizierungs-Token
     * @param DateTime $expires Ablaufzeit des Tokens
     * @return bool True bei Erfolg, false bei Fehler
     */
    public static function setVerificationToken($userId, $token, $expires)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);

        if ($user !== false) {
            $user->verification_token = $token;
            $user->verification_token_expires = $expires->format('Y-m-d H:i:s');
            $erg = $user->save();

            // Query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setVerificationToken', $lastQuery);
            }

            return $erg;
        }

        return false;
    }

    /**
     * Verifiziert einen Benutzer anhand des Tokens.
     *
     * @param string $token Der Verifizierungs-Token
     * @return array|false Array mit User-Daten bei Erfolg, false bei Fehler
     */
    public static function verifyUserByToken($token)
    {
        ORM::configure('logging', true);

        $user = ORM::for_table(self::$tableName)
            ->where('verification_token', $token)
            ->find_one();

        // Query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'verifyUserByToken', $lastQuery);
        }

        if ($user === false) {
            return false;
        }

        // Prüfen ob Token abgelaufen ist
        $now = new \DateTime();
        $expires = new \DateTime($user->verification_token_expires);

        if ($now > $expires) {
            return false; // Token abgelaufen
        }

        // User aktivieren
        $user->status = 1;
        $user->verification_token = null;
        $user->verification_token_expires = null;
        $user->save();

        // Query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'verifyUserByToken', $lastQuery);
        }

        return [
            'username' => $user->username,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname
        ];
    }

    /**
     * Erstellt einen neuen Benutzer.
     * @param string $username Der Benutzername
     * @param string $email Die E-Mail-Adresse
     * @param string $firstname Der Vorname
     * @param string $lastname Der Nachname
     * @param string $status Der Account Status des Benutzers (z.B. "active" = 1 oder "inactive" = 0)
     * @param string $password Das Passwort (gehasht)
     * @param string $roles Die Rollen des Benutzers (z.B. "User" oder "Admin" oder mehere Rollen. Z.b. "User,IT,HR")
     * @param int $ldapEnabled ldap Authentifizierung aktiviert (0 = nein, 1 = ja)
     * @param int $entraIdEnabled Entra ID/Azure SSO Authentifizierung aktiviert (0 = nein, 1 = ja)
     * @return User|null Der neue Benutzer oder null, wenn der Benutzer nicht erstellt werden konnte
     */
    public static function createUser($username, $email, $firstname, $lastname, $status, $password, $roles, $ldapEnabled = 0, $entraIdEnabled = 0)
    {
        try {
            ORM::configure('logging', true);
            $user = ORM::for_table(self::$tableName)->create();
            $user->username = $username;
            $user->email = $email;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->status = $status;
            $user->password = $password;
            $user->roles = $roles;
            $user->ldapEnabled = $ldapEnabled;
            $user->entraIdEnabled = $entraIdEnabled;

            $erg = $user->save() ? $user : null;

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'createUser', $lastQuery);
            }

            return $erg;
        } catch (\PDOException $e) {
            // Log the error
            LogUtil::logAction(
                LogType::ERROR,
                'User.php',
                'createUser',
                'Failed to create user: ' . $e->getMessage()
            );
            return null;
        }
    }



    /**
     * Finds a user by their ID.
     *
     * @param int $id The ID of the user to find.
     * @return mixed The user object if found, or false if not found.
     */
    public static function findUserById($id)
    {
        ORM::configure('logging', true);
        $erg = ORM::for_table(self::$tableName)->where('id', $id)->find_one();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'findUserById', $lastQuery);
        }

        return $erg;
    }


    /**
     * Finds a user by their username.
     *
     * @param string $username The username of the user to find.
     * @return mixed The user object if found, or false if not found.
     */
    public static function findUserByUsername($username)
    {
        ORM::configure('logging', true);
        $erg = self::findUserByField('username', $username);

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'findUserByUsername', $lastQuery);
        }

        return $erg;
    }

    /**
     * Finds a user by their email address.
     *
     * @param string $email The email address of the user to find.
     * @return mixed The user object if found, or false if not found.
     */
    public static function findUserByEmail($email)
    {
        return self::findUserByField('email', $email);
    }

    /**
     * Finds a user by their reset token.
     *
     * @param string $token The reset token to find the user by.
     * @return mixed The user object if found, or false if not found.
     */
    public static function findUserByResetToken($token)
    {
        ORM::configure('logging', true);
        $erg = ORM::for_table(self::$tableName)->where('reset_token', $token)->where_gt('reset_token_expires', date('Y-m-d H:i:s'))->find_one();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'findUserByResetToken', $lastQuery);
        }

        return $erg;
    }

    /**
     * Finds a user by a specific field and value.
     *
     * @param string $field The database column to search in.
     * @param mixed $value The value to search for.
     * @return mixed The user object if found, or false if not found.
     */
    public static function findUserByField($field, $value)
    {
        ORM::configure('logging', true);
        $erg = ORM::for_table(self::$tableName)->where($field, $value)->find_one();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'findUserByField', $lastQuery);
        }

        return $erg;
    }

    /**
     * Checks if a user already exists with the given username or email.
     *
     * @param string $username The username to check for existence.
     * @param string $email The email address to check for existence.
     * @return string A message indicating if the username or email already exists, or "false" if neither exists.
     */
    public static function checkIfUserExists($username, $email)
    {
        ORM::configure('logging', true);
        $emailUser = ORM::for_table(self::$tableName)->where('email', $email)->find_one();
        $nameUser = ORM::for_table(self::$tableName)->where('username', $username)->find_one();
        $erg = "false";

        if ($emailUser !== false) {
            $erg = TranslationUtil::t('register.msg.errorEmail');
        } elseif ($nameUser !== false) {
            $erg = TranslationUtil::t('register.msg.errorUsername');
        }

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'checkIfUserExists', $lastQuery);
        }

        return $erg;
    }


    /**
     * Updates the status of a user.
     *
     * @param int $id The ID of the user whose status is to be updated.
     * @param string $newStatus The new status to be set for the user.
     * @return bool True if the status was successfully updated, false otherwise.
     */
    public static function updateUserStatus($id, $newStatus)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->status = $newStatus;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'updateUserStatus', $lastQuery);
            }

            return $erg;
        }
        return false;
    }


    /**
     * Updates a user by ID.
     *
     * @param int $userId The ID of the user to update.
     * @param string $email The new email address for the user.
     * @param string $username The new username for the user.
     * @param string $firstname The new first name for the user.
     * @param string $lastname The new last name for the user.
     * @param string $status The new status for the user.
     * @param string $roles The new roles for the user.
     * @param string $password The new password for the user, or null if password should not be changed.
     * @param int $ldapEnabled ldap Authentifizierung aktiviert (0 = nein, 1 = ja)
     * @param int $entraIdEnabled Entra ID/Azure SSO Authentifizierung aktiviert (0 = nein, 1 = ja)
     * @return bool True if the user was successfully updated, false otherwise.
     */
    public static function updateuser($userId, $email, $username, $firstname, $lastname, $status, $roles, $password, $ldapEnabled = 0, $entraIdEnabled = 0)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);

        if ($user !== false) {
            $user->email = $email;
            $user->username = $username;
            $user->firstname = $firstname;
            $user->lastname = $lastname;
            $user->status = $status;
            $user->roles = $roles;
            $user->ldapEnabled = $ldapEnabled; // LDAP-Flag setzen
            $user->entraIdEnabled = $entraIdEnabled;
            if ($password !== null) {
                $user->password = $password;
            }

            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'updateuser', $lastQuery);
            }
            return $erg;
        }
        return false;
    }

    /**
     * Changes the email address of a user identified by their ID.
     *
     * @param int $id The ID of the user whose email is to be changed.
     * @param string $new_email The new email address to be set for the user.
     * @return bool True if the email was successfully changed, false otherwise.
     */
    public static function changeEmailAddress($id, $new_email)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->email = $new_email;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'changeEmailAddress', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Sets a reset token for a user identified by their email.
     *
     * @param string $token The reset token to be set.
     * @param string $email The email address of the user for whom the token is to be set.
     * @return bool True if the token was successfully set and saved, false otherwise.
     */

    public static function setResetToken($token, $email, $expiryMinutes = 60)
    {
        ORM::configure('logging', true);
        $user = self::findUserByEmail($email);
        if ($user !== false) {
            $user->reset_token = $token;
            $user->reset_token_expires = date('Y-m-d H:i:s', time() + ($expiryMinutes * 60));
            $erg =  $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setResetToken', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Sets a MFA token for a user identified by their ID.
     *
     * @param string $token The MFA token to be set.
     * @param int $id The ID of the user for whom the token is to be set.
     * @return bool True if the token was successfully set and saved, false otherwise.
     */
    public static function setMfaToken($token, $id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaSecret = $token;
            $user->mfaEnabled = 0;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setMfaToken', $lastQuery);
            }

            return $erg;
        }
        return false;
    }


    /**
     * Disables MFA and resets the secret for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA is to be disabled.
     * @return bool True if MFA was successfully disabled and the secret was reset, false otherwise.
     */
    public static function disableAndReset2fa($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaSecret = '';
            $user->mfaEnabled = 0;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'disableAndReset2fa', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Enforces MFA for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA is to be enforced.
     * @return bool True if MFA was successfully enforced, false otherwise.
     */
    public static function enforceMfa($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaEnforced = 1;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'enforceMfa', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Disables enforcing MFA for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA enforcing is to be disabled.
     * @return bool True if MFA enforcing was successfully disabled, false otherwise.
     */
    public static function unenforceMfa($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaEnforced = 0;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'unenforceMfa', $lastQuery);
            }

            return $erg;
        }
        return false;
    }


    /**
     * Disables MFA for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA is to be disabled.
     * @return bool True if MFA was successfully disabled, false otherwise.
     */
    public static function disableMfaForUser($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaEnabled = 0;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'disableMfaForUser', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Enables MFA for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA is to be enabled.
     * @return bool True if MFA was successfully enabled, false otherwise.
     */
    public static function enableMfaForUser($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaEnabled = 1;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'enableMfaForUser', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Enables MFA setup for a user identified by their ID.
     *
     * This method expects the user ID to be passed.
     * If the user is found, the mfaStartSetup flag is set to 1.
     * This flag is used by the user to indicate that they want to set up MFA.
     *
     * @param int $id The ID of the user whose MFA setup is to be enabled.
     * @return bool True if MFA setup was successfully enabled, false otherwise.
     */
    public static function enableMfaSetupForUser($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaStartSetup = 1;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'enableMfaSetupForUser', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Disables MFA setup for a user identified by their ID.
     *
     * @param int $id The ID of the user whose MFA setup is to be disabled.
     * @return bool True if MFA setup was successfully disabled, false otherwise.
     */
    public static function disableMfaSetupForUser($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->mfaStartSetup = 0;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'disableMfaSetupForUser', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Sets a new password for a user identified by their ID.
     *
     * This method expects the user ID and the new password to be passed.
     * If the user is found and the password is valid, the password is updated
     * and the user is saved. The reset token is also reset to an empty string.
     *
     * @param int $userId The ID of the user whose password is to be set.
     * @param string $password The new password to be set.
     * @return bool True if the password was successfully set and saved, false otherwise.
     */
    public static function setNewPassword($userId, $password)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($userId);
        if ($user !== false) {
            $user->reset_token = '';
            $user->password = $password;
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setNewPassword', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Deletes a user by their ID.
     *
     * @param int $id The ID of the user to delete.
     * @return bool True if the user was successfully deleted, false otherwise.
     */
    public static function deleteUser($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $erg = $user->delete();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'deleteUser', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Retrieves the active session ID for a user by their ID.
     *
     * @param int $id The ID of the user whose active session ID is to be retrieved.
     * @return string|null The active session ID if found, or null if the user does not exist or has no active session ID.
     */
    public static function getActiveSessionId($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            return $user->activeSessionId;
        }
        return null;
    }

    /**
     * Sets the active session ID for a user by their ID.
     *
     * This method expects the user ID to be passed.
     * If the user is found, the activeSessionId field is set to the current session ID.
     *
     * @param int $id The ID of the user whose active session ID is to be set.
     * @return bool True if the active session ID was successfully set and saved, false otherwise.
     */
    public static function setActiveSessionId($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->activeSessionId = session_id();
            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'setActiveSessionId', $lastQuery);
            }

            return $erg;
        }
        return false;
    }

    /**
     * Retrieves the last known IP address for a user by their ID.
     *
     * @param int $id The ID of the user whose last known IP address is to be retrieved.
     * @return string|null The last known IP address if found, or null if the user does not exist or has no last known IP address.
     */
    public static function getLastKnownIp($id)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            return $user->lastKnownIp;
        }
        return null;
    }

    /**
     * Sets the last known IP address for a user by their ID.
     *
     * This method expects the user ID and the new IP address to be passed.
     * If the user is found, the lastKnownIp field is set to the new IP address.
     *
     * @param int $id The ID of the user whose last known IP address is to be set.
     * @param string $ip The new IP address to be set.
     * @return bool True if the last known IP address was successfully set and saved, false otherwise.
     */
    public static function updateLastKnownIp($id, $ip)
    {
        ORM::configure('logging', true);
        $user = self::findUserById($id);
        if ($user !== false) {
            $user->lastKnownIp = $ip;
            $erg = $user->save();
            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'User.php', 'updateLastKnownIp', $lastQuery);
            }
            return $erg;
        }
        return false;
    }

    /**
     * Retrieves all users from the database.
     *
     * @return array An array of user objects.
     */

    public static function getAllUsers()
    {
        ORM::configure('logging', true);
        $erg = ORM::for_table(self::$tableName)->find_many();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'getAllUsers', $lastQuery);
        }

        return $erg;
    }

    /**
     * Retrieves a paginated list of users, optionally filtered by a search term.
     *
     * @param int $offset The starting point for the user list.
     * @param int $pageSize The number of users to retrieve.
     * @param string|null $search Optional search term to filter users by username.
     * @return array|null An associative array containing 'users' (the list of users) and 'totalUsers' (the total count of users), or null if no users are found.
     */

    public static function getUsersPaged($offset, $pageSize, $search = null)
    {
        ORM::configure('logging', true);
        $query = ORM::forTable(self::$tableName);

        if ($search) {
            $searchParam = '%' . addcslashes($search, '%_\\') . '%';
            $query->where_raw('username LIKE ?', [$searchParam]);
        }

        $totalUsers = $query->count();
        $users = $query->limit($pageSize)->offset($offset)->findMany();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'getUsersPaged', $lastQuery);
        }

        if ($users && $totalUsers) {
            return [
                'users' => $users,
                'totalUsers' => $totalUsers
            ];
        } else {
            return null;
        }
    }

    /**
     * Retrieves multiple users by their IDs.
     *
     * @param array $userIds Array of user IDs to retrieve
     * @return array An array of user objects
     */
    public static function getUsersByIds(array $userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        ORM::configure('logging', true);

        // Convert IDs to integers for safety
        $userIds = array_map('intval', $userIds);

        // Build the IN clause with placeholders
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $users = ORM::for_table(self::$tableName)
            ->where_raw("id IN ({$placeholders})", $userIds)
            ->find_array(); // Use find_array() for better CSV compatibility

        // Log the query
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'User.php', 'getUsersByIds', $lastQuery);
        }

        return $users;
    }
}
