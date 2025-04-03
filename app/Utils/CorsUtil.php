<?php
namespace App\Utils;

use Flight;

/**
 * Class Name: CorsUtil
 *
 * Hilfsklasse zur implementierung von CORS Regeln.
 *
 * @package App\Utils
 * @author Sascha Heimann
 * @version 1.0
 * @since 2025-02-24
 *
 * Änderungen:
 * - 1.0 (2025-02-24): Erstellt.
 */
class CorsUtil
{
    // properties
    public $allowedHosts;

    // properties methods
    
    /**
     * Sets the allowed hosts for CORS requests.
     *
     * @param mixed $name The new value for allowed hosts.
     *
     * @return void
     */

    public function setAllowedHosts($hostsArr)
    {
        $this->allowedHosts = $hostsArr;
    }
    /**
     * Retrieves the list of allowed hosts for CORS requests.
     *
     * @return mixed The current list of allowed hosts.
     */

    public function getAllowedHosts()
    {
        return $this->allowedHosts;
    }

    /**
     * Constructs a new CORS util object with the given allowed hosts.
     *
     * @param array $allowedHostsArray List of hosts allowed for CORS requests.
     */
    public function __construct($allowedHostsArray)
    {
        $this->setAllowedHosts($allowedHostsArray);
    }

    /**
     * Configures CORS headers based on the provided allowed hosts.
     *
     * This method checks the incoming request for an origin and sets the appropriate
     * CORS headers if the origin is allowed. It also handles preflight OPTIONS requests
     * by setting the allowed methods and headers, and responds with a 200 status code.
     *
     * @param array $allowedhosts List of hosts allowed for CORS requests.
     *
     * @return void
     */
    public function setupCors(): void
    {
        $request = Flight::request();
        $response = Flight::response();
        if ($request->getVar('HTTP_ORIGIN') !== '') {
            $this->allowOrigins();
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Max-Age', '86400');
        }

        if ($request->method === 'OPTIONS') {
            if ($request->getVar('HTTP_ACCESS_CONTROL_REQUEST_METHOD') !== '') {
                $response->header(
                    'Access-Control-Allow-Methods',
                    'GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD'
                );
            }
            if ($request->getVar('HTTP_ACCESS_CONTROL_REQUEST_HEADERS') !== '') {
                $response->header(
                    "Access-Control-Allow-Headers",
                    $request->getVar('HTTP_ACCESS_CONTROL_REQUEST_HEADERS')
                );
            }

            $response->status(200);
            $response->send();
            exit;
        }
    }

    /**
     * Adds the Access-Control-Allow-Origin header if the request origin is in the
     * list of allowed hosts.
     *
     * @return void
     */
    private function allowOrigins(): void
    {
        // ACHTUNG: allowedHosts ist defniert in config.php!

        $request = Flight::request();

        if (in_array($request->getVar('HTTP_ORIGIN'), $this->getAllowedHosts(), true) === true) {
            $response = Flight::response();
            $response->header("Access-Control-Allow-Origin", $request->getVar('HTTP_ORIGIN'));
        }
    }
}
