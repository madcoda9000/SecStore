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
use App\Controllers\SetupController; // HINZUGEFÜGT für Setup

/**
 * log all errors to a file
 */
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL ^ E_DEPRECATED);

// define config var
$config = [];

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


/**
 * SETUP-PRÜFUNG 
 * Ersetzt das ursprüngliche "require '../app/DatabaseSetup.php';"
 */

// Setup-Prüfung und Variable für routes.php setzen
$needsSetup = false;

// Prüfen ob config.php existiert und beschreibbar ist
if (!file_exists('../config.php') || !is_writable('../config.php')) {
    $needsSetup = true;
} else {
    try {
        $config = include __DIR__ . '/../config.php';
    } catch (Exception $e) {
        //Flight::halt(500, $e->getMessage());
        $app->latte()->render("errors/error.latte", [
            'code' => 500,
            'message' => $e->getMessage()
        ]);
        Flight::halt();
    }
}

// Prüfen ob Datenbankonfiguration noch Default-Werte hat
if (!$needsSetup && isset($config['db']['host']) && $config['db']['host'] === 'YOUR_DB_SERVER_NAME') {
    $needsSetup = true;
}

// Prüfen ob Setup bereits abgeschlossen wurde
if (!$needsSetup && isset($config['setupCompleted']) && $config['setupCompleted'] === true) {
    $needsSetup = false; // Setup bereits abgeschlossen, auch wenn Mail-Defaults noch da sind
} elseif (!$needsSetup && isset($config['mail']['host']) && $config['mail']['host'] === 'YOUR_SMTP_SERVER') {
    $needsSetup = true;
}

/**
 * configure idiorm ORM (nur wenn Setup abgeschlossen)
 */
if (!$needsSetup) {
    try {
        ORM::configure('mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=utf8mb4');
        ORM::configure('username', $config['db']['user']);
        ORM::configure('password', $config['db']['pass']);
    } catch (Exception $e) {
        $app->halt(500, 'Database connection failed: ' . $e->getMessage());
    }
}


/**
 * enable cors
 */
if (!$needsSetup) {
    $CorsUtil = new CorsUtil($config['allowedHosts']);
    $app->before('start', [$CorsUtil, 'setupCors']);
}

/**
 * Session-Initialisierung (auch für Setup)
 */
if ($needsSetup) {
    // Minimale Session für Setup (ohne SessionUtil complexity)
    $app->before('start', function () {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // Basic session settings für Setup
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_httponly', 1);

            session_set_cookie_params([
                'lifetime' => 3600, // 1 Stunde für Setup
                'path' => '/',
                'domain' => '',
                'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);

            session_start();
        }
    });
} else {
    // Normale Session für Anwendung
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
}

/**
 * Routen laden - Setup-Logik ist in routes.php
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

    $app->latte()->render('errors/error.latte', [
        'code' => $code,
        'message' => $ex->getMessage()
    ]);
});

Flight::start();
