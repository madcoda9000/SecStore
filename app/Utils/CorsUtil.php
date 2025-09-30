<?php
namespace App\Utils;

use Flight;

/**
 * Class Name: CorsUtil
 *
 * Hilfsklasse zur Implementierung von CORS Regeln.
 *
 * @package App\Utils
 */
class CorsUtil
{
    // properties
    public $allowedHosts;

    /**
     * Sets the allowed hosts for CORS requests.
     *
     * @param array $hostsArr The new value for allowed hosts.
     * @return void
     */
    public function setAllowedHosts($hostsArr)
    {
        $this->allowedHosts = $hostsArr;
    }

    /**
     * Retrieves the list of allowed hosts for CORS requests.
     *
     * @return array The current list of allowed hosts.
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
     * @param bool $testing Set true to suppress exit() for PHPUnit tests.
     * @return void
     */
    public function setupCors(bool $testing = false): void
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

            if (!$testing) {
                exit;
            }
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
        $request = Flight::request();

        if (in_array($request->getVar('HTTP_ORIGIN'), $this->getAllowedHosts(), true)) {
            $response = Flight::response();
            $response->header("Access-Control-Allow-Origin", $request->getVar('HTTP_ORIGIN'));
        }
    }
}
