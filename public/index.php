<?php

/**
 * import latte template engine libraries
 */

use Latte\Engine as LatteEngine;
use App\Utils\CorsUtil;
use App\Utils\LogUtil;
use App\Utils\LogType;

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
    Flight::halt(500, $e->getMessage());
}

// It is better practice to not use static methods for everything. It makes your
// app much more difficult to unit test easily.
// This is important as it connects any static calls to the same $app object
$app = Flight::app();

/**
 * tell Flight where are our views
 */
$app->set('flight.views.path', '../app/views');

/**
 * configure latte template engine
 */
$app->register('latte', LatteEngine::class, [], function (LatteEngine $latte) use ($app) {
    $latte->setTempDirectory('../cache/');
    $latte->setLoader(new \Latte\Loaders\FileLoader($app->get('flight.views.path')));
});


/**
 * enable cors
 */
$CorsUtil = new CorsUtil($config['allowedHosts']);
$app->before('start', [$CorsUtil, 'setupCors']);

/*
 * Load the routes file.
 * A route is really just a URL, but saying route makes you sound cooler.
 * When someone hits that URL, you point them to a function or method
 * that will handle the request.
 */
require '../app/routes.php';

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
 * enable session before each request and generate csrf token
 */
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
    $app->response()->header("Content-Security-Policy", "default-src 'self' 'unsafe-inline'; img-src data: w3.org/svg/2000:img-src 'self'");

    // Set the X-XSS-Protection header to prevent XSS
    $app->response()->header('X-XSS-Protection', '1; mode=block');

    // Set the X-Content-Type-Options header to prevent MIME sniffing
    $app->response()->header('X-Content-Type-Options', 'nosniff');

    // Set the Referrer-Policy header to control how much referrer information is sent
    $app->response()->header('Referrer-Policy', 'no-referrer-when-downgrade');

    // Set the Strict-Transport-Security header to force HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $app->response()->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
    }

    // Set the Permissions-Policy header to control what features and APIs can be used
    $app->response()->header('Permissions-Policy', 'geolocation=()');
});

/**
 * At this point, your app should have all the instructions it needs and it'll
 * "start" processing everything. This is where the magic happens.
 */
$app->start();
