<?php
namespace App\Middleware;

use Flight;
use App\Utils\SessionUtil;

/**
 * Class Name: AdminCheckMiddleware
 *
 * Middlewware Klasse zur Überprüfung ob ein user athentifiziert ist.
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class AuthCheckMiddleware
{

    
    /**
     * Checks if a user is authenticated and redirects to login if not.
     */
    public static function checkIfAuthenticated()
    {
        if (SessionUtil::get('user') === null) {
            Flight::redirect('/login');
        }
    }
}
