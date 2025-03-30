<?php
namespace App\Middleware;

use Flight;
use App\Utils\SessionUtil;

/**
 * Class Name: CsrfMiddleware
 *
 * Middlewware Klasse zur Überprüfung ob ein Request ein gültiges CSRF-Token enthält
 *
 * @package App\Middleware
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class CsrfMiddleware
{
    /**
     * Verifies the CSRF token of incoming POST requests.
     *
     * @param array $params The route parameters.
     */
    public function before(array $params): void
    {
        if(Flight::request()->method == 'POST') {
            $token = Flight::request()->data->csrf_token;
            if($token !== SessionUtil::get('csrf_token')) {
                Flight::halt(403, 'Ungültiges CSRF-Token');
            }
        }
    }
}
?>