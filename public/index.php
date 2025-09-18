<?php

/**
 * import latte template engine libraries
 */

use Latte\Engine as LatteEngine;
use App\Utils\CorsUtil;
use App\Utils\LogUtil;
use App\Utils\LogType;
use App\Utils\SessionUtil;
use App\Utils\TranslationUtil;

/**
 * log all errors to a file
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL ^ E_DEPRECATED);

/*
 * include composer libraries
 */
require '../vendor/autoload.php';

// It is better practice to not use static methods for everything. It makes your
// app much more difficult to unit test easily.
// This is important as it connects any static calls to the same $app object
$app = Flight::app();

/**
 * tell Flight where are our views
 */
$app->set('flight.views.path', '../app/views');

/**
 * init translation util
 */
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'de'])) {
    TranslationUtil::setLang($_GET['lang']);
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

TranslationUtil::init();
$app->set('trans', fn($key) => TranslationUtil::t($key));
$app->set('lang', TranslationUtil::getLang());


/**
 * configure latte template engine
 */
$app->register('latte', LatteEngine::class, [], function (LatteEngine $latte) use ($app) {

    // make translation available in latte templates
    $latte->addFunction('trans', fn($s) => $app->get('trans')($s));
    
    // Setze den Cache-Ordner für Latte
    $cacheDir = '../cache/';

    // Prüfen, ob das Cache-Verzeichnis beschreibbar ist
    if (!is_writable($cacheDir)) {
        LogUtil::logAction(LogType::ERROR, 'index', 'latte->cacheCheck', "ERROR: Cache directory '$cacheDir' is not writable.");
        Flight::halt(500, "Cache directory '$cacheDir' is not writable by the web server.");
    }

    $latte->setTempDirectory($cacheDir);
    $latte->setLoader(new \Latte\Loaders\FileLoader($app->get('flight.views.path')));
});

/*
 * Load the config file
 */
$config = [];
try {
    if (!file_exists('../config.php')) {
        throw new Exception('Config file not found. Please create a config.php file.');
    }
    $config = include __DIR__ . '/../config.php';
} catch (Exception $e) {
    //Flight::halt(500, $e->getMessage());
    $app->latte()->render("errors/error.latte", [
        'code' => 500,
        'message' => $e->getMessage()
    ]);
    Flight::halt();
}

/**
 * enable cors
 */
$CorsUtil = new CorsUtil($config['allowedHosts']);
$app->before('start', [$CorsUtil, 'setupCors']);

/**
 * execute Database setup methods
 */
require '../app/DatabaseSetup.php';

/**
 * configure idiorm ORM
 */
try {
    ORM::configure('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=utf8mb4');
    ORM::configure('username', $config['db']['user']);
    ORM::configure('password', $config['db']['pass']);
} catch (Exception $e) {
    $app->halt(500, 'Database connection failed: ' . $e->getMessage());
}

/**
 * Start the session
 */
$app->before('start', function () use ($app) {
    // Session initialisieren - alle Komplexität ist jetzt in SessionUtil
    SessionUtil::initialize();
    
    // Falls Session ungültig/abgelaufen ist, wird automatisch destroyed
    // und wir können entsprechend reagieren
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Session wurde zerstört - zur Login-Seite weiterleiten
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '';
        if (!in_array($currentRoute, ['/login', '/register', '/forgot-password', '/reset-password', '/verify-2fa'])) {
            $app->redirect('/logout');
            exit;
        }
    }
});
/*
$app->before('start', function () use ($config, $app) {
    $defaultTimeout = $config['application']['sessionTimeout']; // Standard-Session-Timeout
    $unlimitedRoutes = ['/login', '/register', '/forgot-password', '/reset-password']; // Routen mit "unendlicher" Sitzung
    $currentRoute = $_SERVER['REQUEST_URI']; // Aktuelle Route ermitteln

    // Falls die aktuelle Route eine unendliche Sitzung haben soll
    $isUnlimited = in_array($currentRoute, $unlimitedRoutes);

    // timeout festlegen
    $timeout = $isUnlimited ? (60 * 60 * 24 * 30) : $defaultTimeout;

    // default Session-Cookie-Parameter setzen, bevor die Session gestartet wird
    ini_set('session.gc_maxlifetime', $timeout);
    ini_set('session.use_cookies', 1);

    session_set_cookie_params([
        'lifetime' => $timeout,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Erst jetzt die Session starten und Cookie setzen
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        setcookie(session_name(), session_id(), [
            'expires' => time() + $timeout, // Explizite Ablaufzeit setzen
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'strict'
        ]);
    }

    // **Timeout-Überprüfung durchführen, bevor last_activity gesetzt wird**
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
    } elseif (!$isUnlimited  && (time() - $_SESSION['last_activity'] > $defaultTimeout)) {
        session_unset();
        session_destroy();
        $app->redirect('/logout');
        exit;
    }

    // **Erst am Ende aktualisieren**
    $_SESSION['last_activity'] = time();
    
    // **CSRF-Token generieren, falls noch nicht vorhanden**
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // **Session-ID regelmäßig erneuern (alle 10 Minuten)**
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > $timeout) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
});
*/


/*
 * Load the routes file.
 * A route is really just a URL, but saying route makes you sound cooler.
 * When someone hits that URL, you point them to a function or method
 * that will handle the request.
 */
require '../app/routes.php';

// 404 - Not Found automatisch abfangen
$app->map('notFound', function () use ($app) {
    LogUtil::logAction(LogType::ERROR, 'index', 'map->notfound', 'ERROR 404: Requested Url (' . $app->request()->url . ') not found.');
    http_response_code(404);
    $app->latte()->render('errors/error404.latte');
});

// Allgemeiner Fehler-Handler für alle anderen Fehler (403, 500, 503 etc.)
$app->map('error', function (Throwable $ex) use ($app) {
    $code = $ex->getCode();

    // Nur erlaubte HTTP-Fehlercodes setzen, sonst 500 als Fallback
    if (!in_array($code, [403, 404, 500, 503])) {
        $code = 500;
    }

    http_response_code($code);
    LogUtil::logAction(LogType::ERROR, 'index', 'map->error', 'ERROR ' . $code . ': ' . $ex->getMessage());
    $app->latte()->render("errors/error.latte", [
        'code' => $code,
        'message' => $ex->getMessage()
    ]);
});



// Add the headers in a filter
Flight::before('start', function () use ($app) {
    // Set the X-Frame-Options header to prevent clickjacking
    $app->response()->header('X-Frame-Options', 'SAMEORIGIN');

    // Set the Content-Security-Policy header to prevent XSS
    // Note: 'unsafe-inline' should be used temprary only!!! Don't forget to put inline script into seperate js file and then remove this here!
    // $app->response()->header("Content-Security-Policy", "default-src 'self' 'unsafe-inline'; img-src data: w3.org/svg/2000:img-src 'self'; style-src 'self' 'unsafe-inline'");
    
    // Option 1: Report-Only Mode for testing
    $app->response()->header("Content-Security-Policy", "default-src 'self'; img-src data: w3.org/svg/2000 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");

    // Option 2: on successful testing, switch to enforcing mode.
    //$app->response()->header("Content-Security-Policy", "default-src 'self'; img-src data: w3.org/svg/2000 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'");

    // Set the X-XSS-Protection header to prevent XSS
    $app->response()->header('X-XSS-Protection', '1; mode=block');

    // Set the X-Content-Type-Options header to prevent MIME sniffing
    $app->response()->header('X-Content-Type-Options', 'nosniff');

    // Set the Referrer-Policy header to control how much referrer information is sent
    $app->response()->header('Referrer-Policy', 'no-referrer-when-downgrade');

    // OPTIMIERTE HSTS IMPLEMENTATION
    // Prüfung auf HTTPS mit mehreren Methoden für bessere Kompatibilität
    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') ||
        (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)
    );

    if ($isHttps) {
        // HSTS Header mit optimalen Einstellungen
        // max-age=31536000 = 1 Jahr
        // includeSubDomains = Gilt auch für alle Subdomains
        // preload = Ermöglicht Aufnahme in Browser-Preload-Listen
        $app->response()->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    } else {
        // OPTIONAL: Redirect zu HTTPS für bessere Sicherheit
        // Nur wenn du automatische HTTPS-Redirects möchtest
        /*
        $httpsUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $app->response()->status(301);
        $app->response()->header('Location', $httpsUrl);
        $app->halt();
        */
    }

    // Set the Permissions-Policy header to control what features and APIs can be used
    $app->response()->header('Permissions-Policy', 'geolocation=()');
});
/**
 * At this point, your app should have all the instructions it needs and it'll
 * "start" processing everything. This is where the magic happens.
 */
$app->start();
