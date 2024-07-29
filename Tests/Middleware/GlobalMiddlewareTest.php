<?php
namespace Koochik\Tests\Middleware;
use Psr\Http\Message\ServerRequestInterface AS Request;
use Koochik\Tests\Mocks\Controllers\DemoController;
use Koochik\Tests\TestCases\ApplicationTestCase;
use Koochik\Tests\Mocks\Middlewares\DummyMiddleware;
use Laminas\Diactoros\Response;

class GlobalMiddlewareTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->middleware([DummyMiddleware::class]);

        $this->app->get('/global-middleware-test', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('Global middleware test');
            return $response;
        });

        // Controller route
        $this->app->get('/controller-middleware-test', [DemoController::class, 'hello']);

        // Route group
        $this->app->group('/group', function ($group) {
            $this->app->get('/middleware-test', function (Request $request) {
                $response = new Response();
                $response->getBody()->write('Route group middleware test');
                return $response;
            });
        });
    }

    public function testGlobalMiddlewareApplied(): void
    {
        $request = $this->get('/global-middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
    }

    public function testGlobalMiddlewareAppliedToController(): void
    {
        $request = $this->get('/controller-middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
    }

//    public function testGlobalMiddlewareAppliedToRouteGroup(): void
//    {
//        $request = $this->get('/group/middleware-test');
//        $response = $this->send($request);
//
//        $this->assertSame(200, $response->getStatusCode());
//        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
//    }
}
