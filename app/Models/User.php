<?php

namespace APP\Models;

use ORM;
use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\TranslationUtil;

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
     * Erstellt einen neuen Benutzer.
     * @param string $username Der Benutzername
     * @param string $email Die E-Mail-Adresse
     * @param string $firstname Der Vorname
     * @param string $lastname Der Nachname
     * @param string $status Der Account Status des Benutzers (z.B. "active" = 1 oder "inactive" = 0)
     * @param string $password Das Passwort (gehasht)
     * @param string $roles Die Rollen des Benutzers (z.B. "User" oder "Admin" oder mehere Rollen. Z.b. "User,IT,HR")
     * @return User|null Der neue Benutzer oder null, wenn der Benutzer nicht erstellt werden konnte
     */
    public static function createUser($username, $email, $firstname, $lastname, $status, $password, $roles, $ldapEnabled = 0)
    {
        ORM::configure('logging', true);
        $user = ORM::for_table(self::$tableName)->create();
        $user->username = $username;
        $user->email = $email;
        $user->firstname = $firstname;
        $user->lastname = $lastname;
        $user->status = $status;
        $user->password = $password;
        $user->roles = $roles;
        $user->ldapEnabled = $ldapEnabled; // LDAP-Flag setzen
        $erg = $user->save() ? $user : null; // Rückgabe des Benutzers oder null


        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
        }

        return $erg;
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
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
        $erg = self::findUserByField('reset_token', $token);

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
     * @return bool True if the user was successfully updated, false otherwise.
     */
    public static function updateuser($userId, $email, $username, $firstname, $lastname, $status, $roles, $password, $ldapEnabled = 0)
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
            if ($password!==null) {
                $user->password = $password;
            }

            $erg = $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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

    public static function setResetToken($token, $email)
    {
        ORM::configure('logging', true);
        $user = self::findUserByEmail($email);
        if ($user !== false) {
            $user->reset_token = $token;
            $erg =  $user->save();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
        if ($user !==false) {
            $erg = $user->delete();

            // letzte query loggen
            $queries = ORM::get_query_log();
            if (!empty($queries)) {
                $lastQuery = end($queries);
                LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
            $query->whereLike('username', "%$search%");
        }

        $totalUsers = $query->count();
        $users = $query->limit($pageSize)->offset($offset)->findMany();

        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
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
}
