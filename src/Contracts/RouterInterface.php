<?php
namespace Kouchik\HttpKernel\Contracts;
use Kouchik\HttpKernel\Route;

interface RouterInterface
{
    public function register(string $method, string $uri, $handler, $middlewares = []): Route;
    public function dispatch(string $httpMethod, string $uri): array;
    public function getMiddlewaresForHandler($handler): array;
}
