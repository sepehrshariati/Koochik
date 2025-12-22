<?php
namespace Kouchik\HttpKernel\Tests\Routing;
use Kouchik\HttpKernel\Tests\Mocks\Middlewares\DummyMiddleware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;
use Laminas\Diactoros\Response;

class RouteGroupTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->middleware([DummyMiddleware::class]);

        // Define route groups
        $this->app->group('/group', function () {
            // Simple GET route within a group
            $this->app->get('/middleware-test', function (Request $request) {
                $response = new Response();
                $response->getBody()->write('Route group middleware test');
                return $response;
            });

            // Route with parameters
            $this->app->get('/user/{id}', function (Request $request, $id) {
                $response = new Response();
                $response->getBody()->write("User ID: $id");
                return $response;
            });

            // Route with POST method
            $this->app->post('/create', function (Request $request) {
                $response = new Response();
                $response->getBody()->write('Create route');
                return $response;
            });

            // Nested route group
            $this->app->group('/nested', function () {
                $this->app->get('/test', function (Request $request) {
                    $response = new Response();
                    $response->getBody()->write('Nested group test');
                    return $response;
                });
            });
        });
    }

    public function testRouteGroupMiddlewareApplied(): void
    {
        $request = $this->get('/group/middleware-test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Route group middleware test', (string) $response->getBody());
    }

    public function testRouteGroupWithParameters(): void
    {
        $request = $this->get('/group/user/42');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('User ID: 42', (string) $response->getBody());
    }

    public function testRouteGroupWithPostMethod(): void
    {
        $request = $this->post('/group/create');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Create route', (string) $response->getBody());
    }

    public function testNestedRouteGroup(): void
    {
        $request = $this->get('/group/nested/test');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Dummy-Header-Value', $response->getHeaderLine('Dummy-Header'));
        $this->assertSame('Nested group test', (string) $response->getBody());
    }

    public function testNonExistentRouteInGroup(): void
    {
        $request = $this->get('/group/non-existent');
        $response = $this->send($request);

        $this->assertSame(404, $response->getStatusCode());
    }
}
