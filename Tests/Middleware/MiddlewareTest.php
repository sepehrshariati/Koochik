<?php
namespace Kouchik\HttpKernel\Tests\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\DemoMiddleware;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\DummyMiddleware;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\SampleMiddleware;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\UnauthenticatedMiddleware;
use Laminas\Diactoros\Response;



class MiddlewareTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Global middleware as an array
        $this->app->middleware([DummyMiddleware::class]);

        // Global middleware as a string
        $this->app->middleware(DummyMiddleware::class);

        // Route with additional middleware as an array
        $this->app->get('/middleware-test-array', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('Middleware test');
            return $response;
        })->middleware([DemoMiddleware::class]);

        // Route with additional middleware as a string
        $this->app->get('/middleware-test-string', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('Middleware test');
            return $response;
        })->middleware(DemoMiddleware::class);

        // Route group with middleware as an array
        $this->app->group('/group-array', function () {
            $this->app->get('/middleware-test', function (Request $request) {
                $response = new Response();
                $response->getBody()->write('Route group middleware test');
                return $response;
            });
        })->middleware([SampleMiddleware::class]);

        // Route group with middleware as a string
        $this->app->group('/group-string', function () {
            $this->app->get('/middleware-test', function (Request $request) {
                $response = new Response();
                $response->getBody()->write('Route group middleware test');
                return $response;
            });
        })->middleware(SampleMiddleware::class);

        // Route group with nested routes and middleware as an array
        $this->app->group('/nested-group-array', function () {
            $this->app->group('/sub-group', function () {
                $this->app->get('/test', function (Request $request) {
                    $response = new Response();
                    $response->getBody()->write('Nested group test');
                    return $response;
                });
            })->middleware([DemoMiddleware::class]);
        })->middleware([SampleMiddleware::class]);

        // Route group with nested routes and middleware as a string
        $this->app->group('/nested-group-string', function () {
            $this->app->group('/sub-group', function () {
                $this->app->get('/test', function (Request $request) {
                    $response = new Response();
                    $response->getBody()->write('Nested group test');
                    return $response;
                });
            })->middleware(DemoMiddleware::class);
        })->middleware(SampleMiddleware::class);

        // Route with UnauthenticatedMiddleware as a string
        $this->app->get('/unauthenticated', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('This should not be reached');
            return $response;
        })->middleware(UnauthenticatedMiddleware::class);
    }

    public function testGlobalMiddlewareApplied(): void
    {
        $request = $this->get('/middleware-test-array');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
    }

    public function testRouteMiddlewareAppliedArray(): void
    {
        $request = $this->get('/middleware-test-array');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Demo-Header-Value', $response->getHeaderLine('Demo-Header'));
        $this->assertSame('Middleware test', (string) $response->getBody());
    }

    public function testRouteMiddlewareAppliedString(): void
    {
        $request = $this->get('/middleware-test-string');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Demo-Header-Value', $response->getHeaderLine('Demo-Header'));
        $this->assertSame('Middleware test', (string) $response->getBody());
    }

    public function testRouteGroupMiddlewareAppliedArray(): void
    {
        $request = $this->get('/group-array/middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Sample-Header-Value', $response->getHeaderLine('Sample-Header'));
        $this->assertSame('Route group middleware test', (string) $response->getBody());
    }

    public function testRouteGroupMiddlewareAppliedString(): void
    {
        $request = $this->get('/group-string/middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Sample-Header-Value', $response->getHeaderLine('Sample-Header'));
        $this->assertSame('Route group middleware test', (string) $response->getBody());
    }

    public function testNestedRouteGroupMiddlewareAppliedArray(): void
    {
        $request = $this->get('/nested-group-array/sub-group/test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Sample-Header-Value', $response->getHeaderLine('Sample-Header'));
        $this->assertSame('Demo-Header-Value', $response->getHeaderLine('Demo-Header'));
        $this->assertSame('Nested group test', (string) $response->getBody());
    }

    public function testNestedRouteGroupMiddlewareAppliedString(): void
    {
        $request = $this->get('/nested-group-string/sub-group/test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Sample-Header-Value', $response->getHeaderLine('Sample-Header'));
        $this->assertSame('Demo-Header-Value', $response->getHeaderLine('Demo-Header'));
        $this->assertSame('Nested group test', (string) $response->getBody());
    }

    public function testUnauthenticatedMiddleware(): void
    {
        $request = $this->get('/unauthenticated');
        $response = $this->send($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('authenticated', (string) $response->getBody());
    }

    public function testNonExistentRouteInGroup(): void
    {
        $request = $this->get('/group-array/non-existent');
        $response = $this->send($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
