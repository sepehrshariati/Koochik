<?php
namespace Kouchik\HttpKernel\Tests\Routing;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Kouchik\HttpKernel\Tests\TestCases\ApplicationTestCase;

class NotFoundAndMethodNotAllowedHandlerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testDefaultNotFoundHandler()
    {
        $request = $this->get('/non-existent-route');
        $response = $this->send($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('404 Not Found', (string)$response->getBody());
    }

    public function testDefaultMethodNotAllowedHandler()
    {
        $this->app->get('/test-route', [new class {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();
                $response->getBody()->write('GET response');
                return $response;
            }
        }]);

        $request = $this->post('/test-route');
        $response = $this->send($request);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString('405 Method Not Allowed', (string)$response->getBody());
    }

    public function testCustomNotFoundHandler()
    {
        $this->app->setNotFoundHandler(function (ServerRequestInterface $request): ResponseInterface {
            $response = new Response();
            $response->getBody()->write('Custom 404 Not Found');
            return $response->withStatus(404);
        });

        $request = $this->get('/non-existent-route');
        $response = $this->send($request);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Custom 404 Not Found', (string)$response->getBody());
    }

    public function testCustomMethodNotAllowedHandler()
    {
        $this->app->get('/test-route', [new class {
            public function __invoke(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();
                $response->getBody()->write('GET response');
                return $response;
            }
        }]);

        $this->app->setMethodNotAllowedHandler(function (ServerRequestInterface $request): ResponseInterface {
            $response = new Response();
            $response->getBody()->write('Custom 405 Method Not Allowed');
            return $response->withStatus(405);
        });

        $request = $this->post('/test-route');
        $response = $this->send($request);

        $this->assertEquals(405, $response->getStatusCode());
        $this->assertStringContainsString('Custom 405 Method Not Allowed', (string)$response->getBody());
    }
}

