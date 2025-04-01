<?php

namespace Koochik\Koochik\Tests\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;
use Koochik\Koochik\Tests\TestCases\ApplicationTestCase;

class AutomaticOptionsHandlingTest extends ApplicationTestCase
{
    /**
     * Set up the test environment by registering various routes.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Single-method route (GET only)
        $this->app->get('/test-single', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('GET response');
            return $response;
        });

        // Multi-method route (GET, POST, DELETE)
        $this->app->get('/test-multi', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('GET response');
            return $response;
        });

        $this->app->post('/test-multi', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('POST response');
            return $response;
        });

        $this->app->delete('/test-multi', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('DELETE response');
            return $response;
        });

        // Dynamic route with a parameter (GET only)
        $this->app->get('/test-user/{id}', function (Request $request, $id) {
            $response = new Response();
            $response->getBody()->write('User ' . $id);
            return $response;
        });

        // Explicit OPTIONS route
        $this->app->options('/test-explicit-options', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('Explicit OPTIONS response');
            return $response;
        });

        // POST-only route
        $this->app->post('/test-post-only', function (Request $request) {
            $response = new Response();
            $response->getBody()->write('POST response');
            return $response;
        });
    }

    /**
     * Test automatic OPTIONS handling for a route with a single method (GET).
     */
    public function testAutomaticOptionsForSingleMethodRoute(): void
    {
        $request = $this->options('/test-single');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('Allow'));
        $allowedMethods = explode(', ', $response->getHeaderLine('Allow'));
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'OPTIONS'], $allowedMethods);
    }

    /**
     * Test automatic OPTIONS handling for a route with multiple methods (GET, POST, DELETE).
     */
    public function testAutomaticOptionsForMultiMethodRoute(): void
    {
        $request = $this->options('/test-multi');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('Allow'));
        $allowedMethods = explode(', ', $response->getHeaderLine('Allow'));
        $this->assertEqualsCanonicalizing(['GET', 'POST', 'DELETE', 'HEAD', 'OPTIONS'], $allowedMethods);
    }

    /**
     * Test automatic OPTIONS handling for a dynamic route with a parameter (GET).
     */
    public function testAutomaticOptionsForDynamicRoute(): void
    {
        $request = $this->options('/test-user/123');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('Allow'));
        $allowedMethods = explode(', ', $response->getHeaderLine('Allow'));
        $this->assertEqualsCanonicalizing(['GET', 'HEAD', 'OPTIONS'], $allowedMethods);
    }

    /**
     * Test OPTIONS request to a non-existent route.
     */
    public function testOptionsForNonExistentRoute(): void
    {
        $request = $this->options('/non-existent');
        $response = $this->send($request);

        $this->assertSame(404, $response->getStatusCode());
    }

    /**
     * Test an explicit OPTIONS handler takes precedence over automatic handling.
     */
    public function testExplicitOptionsRoute(): void
    {
        $request = $this->options('/test-explicit-options');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Explicit OPTIONS response', (string) $response->getBody());
        $this->assertFalse($response->hasHeader('Allow'));
    }

    /**
     * Test automatic OPTIONS handling for a route with only POST method.
     */
    public function testAutomaticOptionsForPostOnlyRoute(): void
    {
        $request = $this->options('/test-post-only');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());
        $this->assertTrue($response->hasHeader('Allow'));
        $allowedMethods = explode(', ', $response->getHeaderLine('Allow'));
        $this->assertEqualsCanonicalizing(['POST', 'OPTIONS'], $allowedMethods);
    }
}