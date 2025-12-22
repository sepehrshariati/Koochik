<?php
namespace Kouchik\HttpKernel\Tests\Middleware;
use Psr\Http\Message\ServerRequestInterface AS Request;
use Kouchik\HttpKernel\Tests\Mocks\Controllers\DemoController;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\DummyMiddleware;
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

    public function testGlobalMiddlewareAppliedInOptions(): void
    {
        $request = $this->options('/global-middleware-test');
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


    public function testGlobalMiddlewareAppliedToControllerInOptions(): void
    {
        $request = $this->options('/controller-middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
    }




}
