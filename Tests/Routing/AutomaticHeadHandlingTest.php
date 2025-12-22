<?php

namespace Kouchik\HttpKernel\Tests\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\DummyMiddleware;

class AutomaticHeadHandlingTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Standard GET route
        $this->app->get('/test-get', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('This is the body');
            return $response->withHeader('X-Custom', 'TestValue');
        });

        // Route with Middleware
        $this->app->get('/test-middleware', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('Body with middleware');
            return $response;
        })->middleware(DummyMiddleware::class);
    }

    /**
     * Test HEAD request on a route that only has GET defined.
     * Expects 200 OK, Correct Headers, but Empty Body.
     */
    public function testHeadRequestOnGetRoute(): void
    {
        $request = $this->head('/test-get');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        // Body must be empty for HEAD
        $this->assertSame('', (string) $response->getBody());
        // Headers should still be present
        $this->assertSame('TestValue', $response->getHeaderLine('X-Custom'));
    }

    /**
     * Test HEAD request applies middlewares defined on the GET route.
     */
    public function testHeadRequestAppliesMiddleware(): void
    {
        $request = $this->head('/test-middleware');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        // Check for header added by DummyMiddleware
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
    }

    /**
     * Test explicit HEAD route takes precedence.
     */
    public function testExplicitHeadRoute(): void
    {
        $this->app->head('/test-explicit', function () {
            $r = new Response();
            return $r->withHeader('X-Explicit', 'Yes');
        });

        $request = $this->head('/test-explicit');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Yes', $response->getHeaderLine('X-Explicit'));
    }
}