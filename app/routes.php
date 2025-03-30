<?php

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;
use App\Controllers\LogController;
use App\Middleware\CsrfMiddleware;
use App\Middleware\AdminCheckMiddleware;
use App\Middleware\AuthCheckMiddleware;
use App\Utils\LogUtil;
use App\Utils\LogType;

$csrfMiddleware = new CsrfMiddleware();


// Startseite (Weiterleitung zu Login)
Flight::route('/', function() {
    Flight::redirect('/login');
});

// Registrierung
Flight::route('GET /register', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /register');
    (new AuthController)->showRegister();
});
Flight::route('POST /register', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /register');
    $csrfMiddleware->before([]);
    (new AuthController)->register();
});

// Login
Flight::route('GET /login', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /login');
    (new AuthController)->showLogin();
});
Flight::route('POST /login', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /login');
    $csrfMiddleware->before([]);
    (new AuthController)->login();
});

// 2fa
Flight::route('GET /enable-2fa(/@comesFromSettings)', function($comesFromSettings) use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /enable-2fa');
    $csrfMiddleware->before([]);
    AuthCheckMiddleware::checkIfAuthenticated();
    (new ProfileController)->enable2FA($comesFromSettings);
});
Flight::route('GET /2fa-verify(/@comesFrom2faEnable?)', function($comesFrom2faEnable = null) use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /2fa-verify');
    $csrfMiddleware->before([]);
    (new AuthController)->show2faVerify($comesFrom2faEnable);
});
Flight::route('POST /2fa-verify', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /2fa-verify');
    $csrfMiddleware->before([]);
    (new AuthController)->verify2FA();
});
Flight::route('POST /disable-2fa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /disable-2fa');
    AuthCheckMiddleware::checkIfAuthenticated();
    $_SESSION['last_activity'] = time();     
    $erg = (new ProfileController)->disable2FA();
    if ($erg) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
});
Flight::route('POST /enable-2fa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /enable-2fa');
    AuthCheckMiddleware::checkIfAuthenticated();
    $_SESSION['last_activity'] = time();     
    $erg = (new ProfileController)->enable2FA();
    if ($erg) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false]);
    }
});

// Passwort vergessen
Flight::route('GET /forgot-password', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /forgot-password');
    (new AuthController)->showForgotPassword();
});
Flight::route('POST /forgot-password', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /forgot-password');
    $csrfMiddleware->before([]);
    (new AuthController)->forgotPassword();
});

// Passwort zurücksetzen
Flight::route('GET /reset-password/@token', array(new AuthController, 'showResetPassword'));
Flight::route('POST /reset-password', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /reset-password');
    $csrfMiddleware->before([]);
    (new AuthController)->resetPassword();
});

// Home (geschützt)
Flight::route('GET /home', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /home');
    AuthCheckMiddleware::checkIfAuthenticated();
    (new HomeController)->showHome();
});

// profile (geschützt)
Flight::route('GET /profile', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /profile');
    AuthCheckMiddleware::checkIfAuthenticated();
    (new ProfileController)->showProfile();
});
Flight::route('POST /profileChangePassword', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /profileChangePassword');
    AuthCheckMiddleware::checkIfAuthenticated();
    $csrfMiddleware->before([]);
    (new ProfileController)->profileChangePassword();
});
Flight::route('POST /profileChangeEmail', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /profileChangeEmail');
    AuthCheckMiddleware::checkIfAuthenticated();
    $csrfMiddleware->before([]);
    (new ProfileController)->profileChangeEmail();
});
Flight::route('POST /disableAndReset2FA', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /disableAndReset2FA');
    AuthCheckMiddleware::checkIfAuthenticated();
    $csrfMiddleware->before([]);
    (new ProfileController)->disableAndReset2FA();
});
Flight::route('POST /initiate2faSetup', function() use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /initiate2faSetup');
    AuthCheckMiddleware::checkIfAuthenticated();
    $erg = (new ProfileController)->initiate2faSetup();
    echo json_encode(["success" => $erg]);
});

// admin settings (geschützt)
Flight::route('GET /admin/settings', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/settings');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->showSettings();
});
Flight::route('POST /admin/updateLogSettings', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/updateLogSettings');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->updateLogSettings(Flight::request()->data);
});
Flight::route('POST /admin/updateMailSettings', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/updateMailSettings');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->updateMailSettings(Flight::request()->data);
});
Flight::route('POST /admin/updateApplicationSettings', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/updateApplicationSettings');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->updateApplicationSettings(Flight::request()->data);
});
Flight::route('POST /admin/updateBruteforceSettings', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/updateBruteforceSettings');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->updateBruteForceSettings(Flight::request()->data);
});
// admin user routes (geschützt)
Flight::route('GET /admin/showEditUser/@id', function ($id) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/showEditUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->showEditeUser($id);
});
Flight::route('POST /admin/updateUser', function  () use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/updateUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    $csrfMiddleware->before([]);
    (new AdminController)->updateUser();
});
Flight::route('GET /admin/showCreateUser', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/showCreateUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();    
    (new AdminController)->showCreateUser();
});
Flight::route('POST /admin/createUser', function () use ($csrfMiddleware) {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/createUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    $csrfMiddleware->before([]);
    (new AdminController)->createUser();
});
Flight::route('GET /admin/users', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/users');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->fetchUsersPaged();
});
Flight::route('POST /admin/deleteUser', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/deleteUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->deleteUser();
});
Flight::route('POST /admin/disableMfa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/disableMfa');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->disableMfa();
});
Flight::route('POST /admin/enableMfa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/enableMfa');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->enableMfa();
});
Flight::route('POST /admin/enableUser', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/enableUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->enableUser();
});
Flight::route('POST /admin/disableUser', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/disableUser');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->disableUser();
});
Flight::route('POST /admin/enforceMfa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/enforceMfa');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->enforceMfa();
});
Flight::route('POST /admin/unenforceMfa', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/unenforceMfa');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->unenforceMfa();
});
// admin roles routes (geschützt)
Flight::route('GET /admin/showRoles', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/showRoles');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->showRoles();
});
Flight::route('GET /admin/roles', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/roles');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->listRoles();
});
Flight::route('POST /admin/roles/add', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/roles/add');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->addRole();
});
Flight::route('GET /admin/roles/checkUsers', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/roles/chekUsers');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->checkUsers();
});
Flight::route('POST /admin/roles/delete', function() {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'POST: /admin/roles/delete');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new AdminController)->deleteRole();
});

// Logs (geschützt)
Flight::route('GET /admin/logsAudit', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsAudit');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showAuditLogs();
});
Flight::route('GET /admin/logs/fetchAuditlogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchAuditlogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchAuditLogs();
});
Flight::route('GET /admin/logsRequest', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsRequest');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showRequestLogs();
});
Flight::route('GET /admin/logs/fetchRequestlogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchRequestlogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchRequestLogs();
});
Flight::route('GET /admin/logsSystem', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsSystem');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showSystemLogs();
});
Flight::route('GET /admin/logs/fetchSystemlogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchSystemlogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchSystemLogs();
});
Flight::route('GET /admin/logsDb', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsDb');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showDbLogs();
});
Flight::route('GET /admin/logs/fetchDblogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchDblogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchDbLogs();
});
Flight::route('GET /admin/logsMail', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsMail');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showMailLogs();
});
Flight::route('GET /admin/logs/fetchMaillogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchMaillogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchMailLogs();
});
Flight::route('GET /admin/logsError', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logsError');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->showErrorLogs();
});
Flight::route('GET /admin/logs/fetchErrorlogs', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/logs/fetchErrorlogs');
    AuthCheckMiddleware::checkIfAuthenticated();
    AdminCheckMiddleware::checkForAdminRole();
    (new LogController)->fetchErrorLogs();
});

// session routes
Flight::route('POST /extend-session', function() {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time(); 
    echo json_encode(["success" => true]);
});

// Logout
Flight::route('/logout', array(new ProfileController, 'logout'));
?>