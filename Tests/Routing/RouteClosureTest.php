<?php

namespace Tests\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;
use Tests\TestCases\ApplicationTestCase;

class RouteClosureTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define routes using closures
        $this->app->get('/get-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('GET response');
            return $response;
        });

        $this->app->post('/post-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('POST response');
            return $response;
        });

        $this->app->put('/put-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('PUT response');
            return $response;
        });

        $this->app->delete('/delete-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('DELETE response');
            return $response;
        });

        $this->app->patch('/patch-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('PATCH response');
            return $response;
        });

        $this->app->options('/options-route', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('OPTIONS response');
            return $response;
        });

        $this->app->head('/head-route', function (Request $request) {
            return new Response();
        });
    }

    public function testGetRoute(): void
    {
        $request = $this->get('/get-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('GET response', (string) $response->getBody());
    }

    public function testPostRoute(): void
    {
        $request = $this->post('/post-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('POST response', (string) $response->getBody());
    }

    public function testPutRoute(): void
    {
        $request = $this->put('/put-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('PUT response', (string) $response->getBody());
    }

    public function testDeleteRoute(): void
    {
        $request = $this->delete('/delete-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('DELETE response', (string) $response->getBody());
    }

    public function testPatchRoute(): void
    {
        $request = $this->patch('/patch-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('PATCH response', (string) $response->getBody());
    }

    public function testOptionsRoute(): void
    {
        $request = $this->options('/options-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OPTIONS response', (string) $response->getBody());
    }

    public function testHeadRoute(): void
    {
        $request = $this->head('/head-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        // HEAD responses do not return a body
        $this->assertSame('', (string) $response->getBody());
    }
}
