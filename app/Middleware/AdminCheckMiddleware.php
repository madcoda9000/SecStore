<?php
namespace App\Middleware;

use Flight;
use App\Utils\SessionUtil;
use App\Models\User;

/**
 * Class Name: AdminCheckMiddleware
 *
 * Middlewware Klasse zur Überprüfung von Rollenzugehörigkeit eines Benutzers.
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class AdminCheckMiddleware
{

    /**
     * Check if the user is an admin and redirect to login if not
     * logged in or halt with a 403 status code if logged in but not an admin.
     */
    public static function checkForAdminRole()
    {
        $user = User::findUserById(SessionUtil::get('user')['id']);
        if (isset($user)) {
            $roles = explode(',', $user->roles);
            if (!in_array('Admin', $roles)) {
                throw new \Exception("Access denied.", 403);
            }
        }
    }
}
