<?php
namespace Tests\Unit;

use App\Utils\CorsUtil;
use Flight;
use PHPUnit\Framework\TestCase;

class CorsUtilTest extends TestCase
{
    protected function setUp(): void
    {
        $_SERVER = [];
        Flight::set('flight.request', new \flight\net\Request());
        Flight::set('flight.response', new \flight\net\Response());
        Flight::response()->clear();
    }

    public function testSetAndGetAllowedHosts(): void
    {
        $cors = new CorsUtil(['https://example.com']);
        $this->assertSame(['https://example.com'], $cors->getAllowedHosts());

        $cors->setAllowedHosts(['https://foo.com']);
        $this->assertSame(['https://foo.com'], $cors->getAllowedHosts());
    }

    public function testAllowOriginsAddsHeaderWhenOriginAllowed(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://allowed.com';

        $cors = new CorsUtil(['https://allowed.com']);
        $this->invokePrivateMethod($cors, 'allowOrigins');

        $headers = Flight::response()->headers();
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $headers);
        $this->assertEquals('https://allowed.com', $headers['Access-Control-Allow-Origin']);
    }

    public function testAllowOriginsDoesNothingWhenOriginNotAllowed(): void
    {
        $_SERVER['HTTP_ORIGIN'] = 'https://evil.com';
        Flight::set('flight.request', new \flight\net\Request());

        $cors = new CorsUtil(['https://allowed.com']);
        $this->invokePrivateMethod($cors, 'allowOrigins');

        $headers = Flight::response()->headers();
        $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $headers);
    }

    /**
     * @runInSeparateProcess
     */
    public function testSetupCorsHandlesOptionsRequest(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
        $_SERVER['HTTP_ORIGIN'] = 'https://allowed.com';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'] = 'GET';
        $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] = 'X-Custom-Header';

        $cors = new CorsUtil(['https://allowed.com']);

        ob_start();
        $cors->setupCors(true);
        ob_end_clean();

        $headers = Flight::response()->headers();

        $this->assertArrayHasKey('Access-Control-Allow-Methods', $headers);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $headers);
        $this->assertEquals(200, Flight::response()->status());
    }

    private function invokePrivateMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
