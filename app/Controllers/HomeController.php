<?php

namespace APP\Controllers;

use App\Models\User;
use App\Utils\SessionUtil;
use App\Utils\TranslationUtil;
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
 * Renders the home page.
 *
 * This method renders the home page and provides the necessary
 * data to the template. It expects the user to be logged in.
 * Also checks for low backup codes warning from session.
 *
 * @return void
 */
public function showHome()
{
    $user = SessionUtil::get('user');
    
    // Check for low backup codes warning from session
    $backupCodesWarning = SessionUtil::get('backup_codes_low_warning');
    if ($backupCodesWarning !== null) {
        // Keep the warning for this page load but remove from session
        SessionUtil::remove('backup_codes_low_warning');
    }
    
    Flight::latte()->render('home.latte', [
        'title' => TranslationUtil::t('home.title'),
        'user' => $user,
        'sessionTimeout' => SessionUtil::getRemainingTime(),
        'backupCodesWarning' => $backupCodesWarning
    ]);
    
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
