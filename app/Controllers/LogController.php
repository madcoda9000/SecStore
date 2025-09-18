<?php

namespace App\Controllers;

use App\Utils\LogType;
use App\Utils\LogUtil;
use App\Utils\SessionUtil;
use App\Utils\TranslationUtil;
use ORM;
use Flight;

/**
 * Class Name: LogController
 *
 * Controller Klasse für Log Methoden im Admin Kontext
 *
 * @package App\Controllers
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class LogController
{

    /**
     * Renders the audit logs view.
     *
     * This function uses the Latte templating engine to render the 'admin/logsAudit.latte' view.
     * It provides the view with the title 'Audit Logs', the current user session, and the remaining session timeout.
     */

    public static function showAuditLogs()
    {
        Flight::latte()->render('admin/logsAudit.latte', [
            'title' => TranslationUtil::t('logs.audit.title'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of audit logs from the database.
     *
     * This function is called through an AJAX request from the audit logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * audit logs. It then returns a JSON response containing the list of audit
     * logs, total number of audit logs, total number of pages, current page and
     * page size.
     */
    public static function fetchAuditLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('AUDIT', $search, $page, $pageSize);
    }

    /**
     * Renders the system logs view.
     *
     * This function utilizes the Latte templating engine to render the 'admin/logsSystem.latte' view.
     * It provides the view with the title 'System Logs', the current user session, and the remaining session timeout.
     */

    public static function showSystemLogs()
    {
        Flight::latte()->render('admin/logsSystem.latte', [
            'title' => TranslationUtil::t('logs.audit.system'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of system logs from the database.
     *
     * This function is called through an AJAX request from the system logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * system logs. It then returns a JSON response containing the list of system
     * logs, total number of system logs, total number of pages, current page and
     * page size.
     */
    public static function fetchSystemLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('SYSTEM', $search, $page, $pageSize);
    }

    /**
     * Renders the security logs view.
     *
     * This function uses the Latte templating engine to render the 'admin/logsSecurity.latte' view.
     * It provides the view with the title 'Security Logs', the current user session, and the remaining session timeout.
     */
    public static function showSecurityLogs()
    {
        Flight::latte()->render('admin/logsSecurity.latte', [
            'title' => TranslationUtil::t('logs.audit.system'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of security logs from the database.
     *
     * This function is called through an AJAX request from the security logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * security logs. It then returns a JSON response containing the list of security
     * logs, total number of security logs, total number of pages, current page and
     * page size.
     */
    public static function fetchSecurityLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('SECURITY', $search, $page, $pageSize);
    }


    /**
     * Renders the request logs view.
     *
     * This function uses the Latte templating engine to render the 'admin/logsRequest.latte' view.
     * It provides the view with the title 'Request Logs', the current user session, and the remaining session timeout.
     */
    public static function showRequestLogs()
    {
        Flight::latte()->render('admin/logsRequest.latte', [
            'title' => TranslationUtil::t('logs.audit.request'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of request logs from the database.
     *
     * This function is called through an AJAX request from the request logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * request logs. It then returns a JSON response containing the list of request
     * logs, total number of request logs, total number of pages, current page and
     * page size.
     */
    public static function fetchRequestLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('REQUEST', $search, $page, $pageSize);
    }

    /**
     * Renders the database logs view.
     *
     * This function utilizes the Latte templating engine to render the 'admin/logsDb.latte' view.
     * It provides the view with the title 'Database Logs', the current user session, and the remaining session timeout.
     */
    public static function showDbLogs()
    {
        Flight::latte()->render('admin/logsDb.latte', [
            'title' => TranslationUtil::t('logs.audit.database'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of database logs from the database.
     *
     * This function is called through an AJAX request from the database logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * database logs. It then returns a JSON response containing the list of database
     * logs, total number of database logs, total number of pages, current page and
     * page size.
     */
    public static function fetchDbLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('SQL', $search, $page, $pageSize);
    }

    /**
     * Renders the mail logs view.
     *
     * This function uses the Latte templating engine to render the 'admin/logsMail.latte' view.
     * It provides the view with the title 'Mail Logs', the current user session, and the remaining session timeout.
     */

    public static function showMailLogs()
    {
        Flight::latte()->render('admin/logsMail.latte', [
            'title' => TranslationUtil::t('logs.audit.mail'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of mail logs from the database.
     *
     * This function is called through an AJAX request from the mail logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * mail logs. It then returns a JSON response containing the list of mail
     * logs, total number of mail logs, total number of pages, current page and
     * page size.
     */
    public static function fetchMailLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('MAIL', $search, $page, $pageSize);
    }

    /**
     * Renders the error logs view.
     *
     * This function uses the Latte templating engine to render the 'admin/logsError.latte' view.
     * It provides the view with the title 'Error Logs', the current user session, and the remaining session timeout.
     */
    public static function showErrorLogs()
    {
        Flight::latte()->render('admin/logsError.latte', [
            'title' => TranslationUtil::t('logs.audit.error'),
            'user' => SessionUtil::get('user'),
            'sessionTimeout' => SessionUtil::getRemainingTime()
        ]);
    }

    /**
     * Fetches a paginated list of error logs from the database.
     *
     * This function is called through an AJAX request from the error logs page.
     * It retrieves the search query, page number and page size from the request
     * query string and calls the `listLogs` method to retrieve the matching
     * error logs. It then returns a JSON response containing the list of error
     * logs, total number of error logs, total number of pages, current page and
     * page size.
     */
    public static function fetchErrorLogs()
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        $pageSize = isset($_GET['pageSize']) ? (int) $_GET['pageSize'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        self::listLogs('ERROR', $search, $page, $pageSize);
    }

    /**
     * Retrieves a paginated list of logs from the database that match the given log type and search query.
     *
     * This function takes the log type, search query, page number and page size as parameters and returns a JSON response containing the list of matching logs, total number of matching logs, total number of pages, current page and page size.
     *
     * @param string $logType the type of log to retrieve (e.g. 'ERROR', 'MAIL', etc.)
     * @param string $search the search query to filter the logs with
     * @param int $page the page number to retrieve
     * @param int $pageSize the number of logs to retrieve per page
     */
    private static function listLogs($logType, $search, $page, $pageSize)
    {
        ORM::configure('logging', true);

        // Basis-Query erstellen
        $query = ORM::for_table('logs')
            ->where('type', $logType);
    
        // Falls ein Suchbegriff vorhanden ist, in den Spalten user, context und message suchen
        if (!empty($search)) {
            $query->where_raw('(user LIKE ? OR context LIKE ? OR message LIKE ?)', [
                "%$search%", "%$search%", "%$search%"
            ]);
        }
    
        // Gesamtanzahl der passenden Logs ermitteln
        $totalLogs = clone $query; // Klonen, um die Zählung nicht zu beeinflussen
        $totalLogs = $totalLogs->count();
    
        // Sortierung nach ID aufsteigend hinzufügen, dann Pagination anwenden
        $logs = $query
            ->order_by_desc('id')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->find_array();
    
        // letzte query loggen
        $queries = ORM::get_query_log();
        if (!empty($queries)) {
            $lastQuery = end($queries);
            LogUtil::logAction(LogType::SQL, 'LogController', 'listLogs', $lastQuery);
        }

        // Gesamtseitenzahl berechnen
        $totalPages = ceil($totalLogs / $pageSize);

        LogUtil::logAction(LogType::AUDIT, 'LogController', 'listLogs', 'SUCCESS: fetched ' . $logType . ' logs.');
    
        // JSON-Antwort zurückgeben
        Flight::json([
            'logs' => $logs,
            'total' => $totalLogs,
            'totalPages' => $totalPages,
            'page' => $page,
            'pageSize' => $pageSize
        ]);
    }
}
