<?php

namespace APP\Controllers;

use App\Models\User;
use App\Utils\SessionUtil;
use Flight;

/**
 * Class Name: DashboardController
 *
 * Controller Klasse für Methoden die für das Dashboard benötigt werden.
 *
 * @package App\Controllers
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class HomeController
{

    /**
     * Shows the dashboard.
     *
     * If the user is not logged in, it redirects to the login page.
     * Otherwise, it renders the dashboard template with the title "Dashboard".
     */
    public function showHome()
    {
        Flight::latte()->render('home.latte', ['title' => 'Home', 'sessionTimeout' => SessionUtil::getRemainingTime(), 'user' => SessionUtil::get('user')]);
    }

    /**
     * Logs out the current user by destroying the session and redirecting to the login page.
     */

    public function logout()
    {
        SessionUtil::destroy();
        Flight::redirect('/login');
    }
}
