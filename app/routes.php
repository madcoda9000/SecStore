<?php

use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;
use App\Controllers\RateLimitController;
use App\Controllers\LogController;
use App\Controllers\SetupController;
use App\Middleware\AdminCheckMiddleware;
use App\Middleware\AuthCheckMiddleware;
use App\Middleware\RateLimiter;
use App\Middleware\CsrfMiddleware;
use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\SecurityMetrics;

// Globale Variable aus index.php verfügbar machen
global $needsSetup;

// ==========================================
// SETUP ROUTES (nur wenn Setup benötigt wird)
// ==========================================
if ($needsSetup) {
     Flight::route('GET /setup', function () {
        (new SetupController)->runSetup();
    });

    Flight::route('POST /setup', function () {
        // Hier prüfen ob Skip gewünscht ist
        $skipMail = isset($_POST['skip_mail']) && $_POST['skip_mail'] === '1';
        (new SetupController)->runSetup($skipMail);
    });

    // Alle anderen Routen zum Setup umleiten
    Flight::route('*', function () {
        Flight::redirect('/setup');
    });

    // Hier stoppen - keine weiteren Routen laden wenn Setup benötigt wird
    return;
}

$csrfMiddleware = new CsrfMiddleware();

/**
 * Helper-Funktionen für sauberere Route-Definitionen
 */

/**
 * Rate Limiter Check mit Konfigurationsprüfung
 */
function checkRateLimit(string $limitType = 'global'): bool
{
    global $config;

    // Prüfen ob Rate Limiting in den Settings aktiviert ist
    if (!($config['rateLimiting']['enabled'] ?? true)) {
        return true; // Rate Limiting deaktiviert
    }

    $rateLimiter = new RateLimiter($config['rateLimiting']['limits'] ?? []);
    return $rateLimiter->checkLimit($limitType);
}

/**
 * Route Helper für authentifizierte Routen mit Rate Limiting
 */
function secureRoute(string $pattern, callable $handler, string $limitType = 'global', bool $requireAdmin = false, bool $requireAuth = true)
{
    Flight::route($pattern, function (...$params) use ($handler, $limitType, $requireAdmin, $requireAuth) {
        // 1. Rate Limiting Check
        if (!checkRateLimit($limitType)) {
            return;
        }

        // 2. Request Logging
        LogUtil::logAction(LogType::REQUEST, 'routes.php', 'secureRoute', $_SERVER['REQUEST_METHOD'] . ': ' . $_SERVER['REQUEST_URI']);

        // 3. Authentication Check
        if ($requireAuth) {
            AuthCheckMiddleware::checkIfAuthenticated();
        }

        // 4. Admin Check
        if ($requireAdmin) {
            AdminCheckMiddleware::checkForAdminRole();
        }

        // 5. Handler ausführen
        return $handler(...$params);
    });
}

/**
 * Route Helper für POST-Routen mit CSRF-Schutz
 */
function securePostRoute(string $pattern, callable $handler, string $limitType = 'global', bool $requireAdmin = false, bool $requireAuth = true)
{
    global $csrfMiddleware;

    Flight::route('POST ' . $pattern, function (...$params) use ($handler, $limitType, $requireAdmin, $requireAuth, $csrfMiddleware) {
        // 1. Rate Limiting Check
        if (!checkRateLimit($limitType)) {
            return;
        }

        // 2. Request Logging
        LogUtil::logAction(LogType::REQUEST, 'routes.php', 'securePostRoute', 'POST: ' . $_SERVER['REQUEST_URI']);

        // 3. CSRF Protection
        $csrfMiddleware->before([]);

        // 4. Authentication Check
        if ($requireAuth) {
            AuthCheckMiddleware::checkIfAuthenticated();
        }

        // 5. Admin Check
        if ($requireAdmin) {
            AdminCheckMiddleware::checkForAdminRole();
        }

        // 6. Handler ausführen
        return $handler(...$params);
    });
}

/**
 * Auth Route Helper (Login, Register, etc.)
 */
function authRoute(string $method, string $pattern, callable $handler, string $limitType)
{
    global $csrfMiddleware;

    Flight::route($method . ' ' . $pattern, function (...$params) use ($handler, $limitType, $method, $csrfMiddleware) {
        // 1. Rate Limiting Check
        if (!checkRateLimit($limitType)) {
            return;
        }

        // 2. Request Logging
        LogUtil::logAction(LogType::REQUEST, 'routes.php', 'authRoute', $method . ': ' . $_SERVER['REQUEST_URI']);

        // 3. CSRF für POST-Requests
        if ($method === 'POST') {
            $csrfMiddleware->before([]);
        }

        // 4. Handler ausführen
        return $handler(...$params);
    });
}

// Startseite
Flight::route('/', function () {
    Flight::redirect('/login');
});

// ==========================================
// AUTHENTICATION ROUTES
// ==========================================

// Registrierung
authRoute('GET', '/register', function () {
    (new AuthController)->showRegister();
}, 'register');

authRoute('POST', '/register', function () {
    (new AuthController)->register();
}, 'register');

// Login
authRoute('GET', '/login', function () {
    (new AuthController)->showLogin();
}, 'login');

authRoute('POST', '/login', function () {
    (new AuthController)->login();
}, 'login');

// 2FA Routes
authRoute('POST', '/2fa-verify', function () {
    (new AuthController)->verify2FA();
}, '2fa');

authRoute('GET', '/2fa-verify', function () {
    (new AuthController)->verify2FA();
}, '2fa');

secureRoute('GET /enable-2fa(/@comesFromSettings)', function ($comesFromSettings) use ($csrfMiddleware) {
    $csrfMiddleware->before([]);
    (new ProfileController)->enable2FA($comesFromSettings);
}, '2fa');

// Passwort vergessen
authRoute('GET', '/forgot-password', function () {
    (new AuthController)->showForgotPassword();
}, 'forgot-password');

authRoute('POST', '/forgot-password', function () {
    (new AuthController)->forgotPassword();
}, 'forgot-password');

// Passwort zurücksetzen
authRoute('GET', '/reset-password/@token', function ($token) {
    (new AuthController)->showResetPassword($token);
}, 'reset-password');

authRoute('POST', '/reset-password', function () {
    (new AuthController)->resetPassword();
}, 'reset-password');

// ==========================================
// USER ROUTES (Authenticated)
// ==========================================

// Home
secureRoute('GET /home', function () {
    (new HomeController)->showHome();
});

// Profile
secureRoute('GET /profile', function () {
    (new ProfileController)->showProfile();
});

// Profile Actions
securePostRoute('/profileChangePassword', function () {
    (new ProfileController)->profileChangePassword();
});

securePostRoute('/profileChangeEmail', function () {
    (new ProfileController)->profileChangeEmail();
});

secureRoute('POST /disableAndReset2FA', function () {
    (new ProfileController)->disableAndReset2FA();
});

secureRoute('POST /initiate2faSetup', function () {
    (new ProfileController)->initiate2faSetup();
});

// ==========================================
// ADMIN ROUTES
// ==========================================

// Security Dashboard Routes
// Daily Security Check (für CRON Job)
Flight::route('GET /cron/daily-security-check', function () {
    // Nur von localhost oder mit speziellem Token erlauben
    if (
        $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' &&
        ($_GET['token'] ?? '') !== 'your-secret-cron-token'
    ) {
        Flight::halt(403, 'Access denied');
    }

    SecurityMetrics::logDailySummary();
    Flight::json(['status' => 'success', 'timestamp' => date('Y-m-d H:i:s')]);
});

secureRoute('GET /admin/security', function () {
    (new AdminController)->showSecurityDashboard();
}, 'admin', true);

secureRoute('GET /admin/security/metrics', function () {
    (new AdminController)->getSecurityMetrics();
}, 'admin', true);

// Admin Settings
secureRoute('GET /admin/settings', function () {
    (new AdminController)->showSettings();
}, 'admin', true);

// Admin Settings Updates
securePostRoute('/admin/updateLogSettings', function () {
    (new AdminController)->updateLogSettings(Flight::request()->data);
}, 'admin', true);

securePostRoute('/admin/updateMailSettings', function () {
    (new AdminController)->updateMailSettings(Flight::request()->data);
}, 'admin', true);

securePostRoute('/admin/updateApplicationSettings', function () {
    (new AdminController)->updateApplicationSettings(Flight::request()->data);
}, 'admin', true);

securePostRoute('/admin/updateBruteforceSettings', function () {
    (new AdminController)->updateBruteForceSettings(Flight::request()->data);
}, 'admin', true);

securePostRoute('/admin/updateLdapSettings', function () {
    (new AdminController)->updateLdapSettings(Flight::request()->data);
}, 'admin', true);


// Admin User Management
// Bulk User Operations
securePostRoute('/admin/users/bulk', function () {
    (new AdminController)->bulkUserOperations();
}, 'admin', true);

// User Export
secureRoute('GET /admin/users/export', function () {
    (new AdminController)->exportUsers();
}, 'admin', true);

secureRoute('GET /admin/users', function () {
    (new AdminController)->fetchUsersPaged();
}, 'admin', true);

secureRoute('GET /admin/showEditUser/@id', function ($id) {
    (new AdminController)->showEditeUser($id);
}, 'admin', true);

secureRoute('POST /admin/updateUser', function () {
    (new AdminController)->updateUser();
}, 'admin', true);

secureRoute('POST /admin/createUser', function () {
    (new AdminController)->createUser();
}, 'admin', true);

secureRoute('GET /admin/showCreateUser', function () {
    (new AdminController)->showCreateUser();
}, 'admin', true);

secureRoute('POST /admin/deleteUser', function () {
    (new AdminController)->deleteUser();
}, 'admin', true);

secureRoute('POST /admin/disableMfa', function () {
    (new AdminController)->disableMfa();
}, 'admin', true);

secureRoute('POST /admin/enableMfa', function () {
    (new AdminController)->enableMfa();
}, 'admin', true);

secureRoute('POST /admin/enableUser', function () {
    (new AdminController)->enableUser();
}, 'admin', true);

secureRoute('POST /admin/disableUser', function () {
    (new AdminController)->disableUser();
}, 'admin', true);

secureRoute('POST /admin/enforceMfa', function () {
    (new AdminController)->enforceMfa();
}, 'admin', true);

secureRoute('POST /admin/unenforceMfa', function () {
    (new AdminController)->unenforceMfa();
}, 'admin', true);

// Admin Role Management
secureRoute('GET /admin/showRoles', function () {
    (new AdminController)->showRoles();
}, 'admin', true);

secureRoute('GET /admin/roles', function () {
    (new AdminController)->listRoles();
}, 'admin', true);

secureRoute('POST /admin/roles/add', function () {
    (new AdminController)->addRole();
}, 'admin', true);

secureRoute('POST /admin/roles/delete', function () {
    (new AdminController)->deleteRole();
}, 'admin', true);

secureRoute('GET /admin/roles/checkUsers', function () {
    (new AdminController)->listRoles();
}, 'admin', true);

// ==========================================
// Rate Limiting Test Route (Admin)
// =========================================

// Rate Limiting Management (Admin)
secureRoute('GET /admin/rate-limits', function () {
    (new RateLimitController)->showSettings();
}, 'admin', true);

securePostRoute('/admin/rate-limits/update', function () {
    (new RateLimitController)->updateSettings();
}, 'admin', true);

secureRoute('GET /admin/rate-limits/violations', function () {
    (new RateLimitController)->showViolations();
}, 'admin', true);

securePostRoute('/admin/rate-limits/reset', function () {
    (new RateLimitController)->resetLimit();
}, 'admin', true);

// Live-Status (ohne ratelimitierung)
Flight::route('GET /admin/rate-limits/status', function () {
    LogUtil::logAction(LogType::REQUEST, 'routes.php', 'Flight:route', 'GET: /admin/rate-limits/status');
    (new RateLimitController)->getLiveStatus();
});

securePostRoute('/admin/rate-limits/clear', function () {
    (new RateLimitController)->clearViolations();
}, 'admin', true);

// ==========================================
// LOG ROUTES (Admin)
// ==========================================

$logRoutes = [
    'logsAudit' => 'showAuditLogs',
    'logsRequest' => 'showRequestLogs',
    'logsSystem' => 'showSystemLogs',
    'logsDb' => 'showDbLogs',
    'logsMail' => 'showMailLogs',
    'logsError' => 'showErrorLogs',
    'logsSecurity' => 'showSecurityLogs'
];

foreach ($logRoutes as $route => $method) {
    secureRoute("GET /admin/$route", function () use ($method) {
        (new LogController)->$method();
    }, 'admin', true);
}

$fetchLogRoutes = [
    'fetchAuditlogs' => 'fetchAuditLogs',
    'fetchRequestlogs' => 'fetchRequestLogs',
    'fetchSystemlogs' => 'fetchSystemLogs',
    'fetchDblogs' => 'fetchDbLogs',
    'fetchMaillogs' => 'fetchMailLogs',
    'fetchErrorlogs' => 'fetchErrorLogs',
    'fetchSecuritylogs' => 'fetchSecurityLogs'
];

foreach ($fetchLogRoutes as $route => $method) {
    secureRoute("GET /admin/logs/$route", function () use ($method) {
        (new LogController)->$method();
    }, 'admin', true);
}

// ==========================================
// UTILITY ROUTES
// ==========================================

// Session Extension
secureRoute('POST /extend-session', function () {
    session_regenerate_id(true);
    $_SESSION['last_activity'] = time();
    echo json_encode(["success" => true]);
}, 'global', false);

// Logout (kein Rate Limiting nötig)
Flight::route('/logout', array(new ProfileController, 'logout'));
