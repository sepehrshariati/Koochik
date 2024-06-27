<?php

namespace Tests\Traits;

use DI\ContainerBuilder;
use Koochik\Koochik\Application;
use Koochik\Koochik\Router;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Stream;
use Psr\Http\Message\ServerRequestInterface;

trait TestApp
{
    public Application $app;

    protected function setUp(): void
    {
        // Set up the framework
        $builder = new ContainerBuilder();
        $builder->addDefinitions([]);
        $container = $builder->build();
        $router = new Router();
        $this->app = new Application($router, $container);
    }

    protected function createRequest(string $method, string $uri, array $headers = []): ServerRequestInterface
    {
        $serverFactory = new ServerRequestFactory();
        $request = $serverFactory->createServerRequest($method, $uri)
            ->withBody(new Stream('php://temp', 'rw'));

        foreach ($headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request->withBody(new Stream('php://temp', 'rw'));
    }

    protected function get(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('GET', $uri, $headers, $body);
    }


    protected function post(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('POST', $uri, $headers, $body);
    }

    protected function put(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('PUT', $uri, $headers, $body);
    }

    protected function delete(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('DELETE', $uri, $headers, $body);
    }

    protected function patch(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('PATCH', $uri, $headers, $body);
    }

    protected function options(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('OPTIONS', $uri, $headers, $body);
    }

    protected function head(string $uri, array $headers = [], string $body = null): ServerRequestInterface
    {
        return $this->createRequest('HEAD', $uri, $headers, $body);
    }

    protected function send(ServerRequestInterface $request)
    {
        $this->app->getContainer()->set(ServerRequestInterface::class, function () use ($request) {
            return $request;
        });

        return $this->app->dispatchRequest($request);
    }
}