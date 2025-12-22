<?php
namespace Kouchik\HttpKernel\Tests\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;
use Kouchik\HttpKernel\Tests\Mocks\Controllers\DemoController;

class RouteControllerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define routes using controller methods
        $this->app->get('/get-route', [DemoController::class, 'hello']);
        $this->app->post('/post-route', [DemoController::class, 'hello']);
        $this->app->put('/put-route', [DemoController::class, 'hello']);
        $this->app->delete('/delete-route', [DemoController::class, 'hello']);
        $this->app->patch('/patch-route', [DemoController::class, 'hello']);
        $this->app->options('/options-route', [DemoController::class, 'hello']);

        // Define a route with dynamic parameters
        $this->app->get('/greet/{name}/{lastname}/{age}', [DemoController::class, 'greet']);
    }

    public function testGetRoute(): void
    {
        $request = $this->get('/get-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testPostRoute(): void
    {
        $request = $this->post('/post-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testPutRoute(): void
    {
        $request = $this->put('/put-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testDeleteRoute(): void
    {
        $request = $this->delete('/delete-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testPatchRoute(): void
    {
        $request = $this->patch('/patch-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testOptionsRoute(): void
    {
        $request = $this->options('/options-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

    public function testDynamicRouteParams(): void
    {
        $request = $this->get('/greet/John/Doe/30');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello user John Doe 30', (string)$response->getBody());
    }
}
