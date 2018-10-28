<?php

namespace Middleware\Tests;

use AnnotationRoute\Collector;
use Middleware\Router;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use Middlewares\Utils\Factory\DiactorosFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

use function FastRoute\simpleDispatcher;
use function call_user_func;
use function explode;
use function json_encode;

/**
 * Class RouterTest
 *
 * @package Middleware\Tests
 * @covers \Middleware\Router
 */
class RouterTest extends TestCase
{
    /** @var Dispatcher */
    private $dispatcher;

    /**
     * Create a new route dispatcher
     */
    public function setUp()
    {
        $this->dispatcher = simpleDispatcher(function (Collector $collector) {
            $collector->addRoutesInPathWithNamespace(__DIR__ . '/Fixtures', 'Middleware\\Tests\\Fixtures');
        }, [
            'routeCollector' => '\\AnnotationRoute\\Collector',
        ]);
    }

    /**
     * @covers \Middleware\Router::process
     */
    public function testRouteOK(): void
    {
        $response = Dispatcher::run([
            (new Router($this->dispatcher, new DiactorosFactory()))
                ->withHandlerAttribute('handler'),

            function (RequestInterface $request) {
                list($controller, $method) = explode(':', $request->getAttribute('handler'));
                return call_user_func([new $controller, $method]);
            }
        ], Factory::createServerRequest('GET', '/users'));

        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @covers \Middleware\Router::process
     */
    public function testRouteNotFound(): void
    {
        $response = Dispatcher::run([
            new Router($this->dispatcher, new DiactorosFactory()),
        ], Factory::createServerRequest('GET', '/posts'));

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @covers \Middleware\Router::process
     */
    public function testRouteNotAllowed(): void
    {
        $response = Dispatcher::run([
            new Router($this->dispatcher, new DiactorosFactory()),
        ], Factory::createServerRequest('POST', '/users'));

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertEquals('GET', $response->getHeaderLine('Allow'));
    }

    /**
     * @covers \Middleware\Router::process
     */
    public function testRouteWithParameters(): void
    {
        $response = Dispatcher::run([
            new Router($this->dispatcher, new DiactorosFactory()),
            function (RequestInterface $request) {
                list($controller, $method) = explode(':', $request->getAttribute('Request-Handler'));
                return call_user_func([new $controller, $method], $request->getAttribute('params'));
            }
        ], Factory::createServerRequest('GET', '/users/2'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(json_encode(['user' => 2]), $response->getBody()->getContents());
    }
}
