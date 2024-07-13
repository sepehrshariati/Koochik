<?php

namespace Koochik\Koochik;

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as StandardRouteParser;
use Koochik\Koochik\Contracts\RouterInterface;

class Router implements RouterInterface {
    private RouteCollector $routeCollector;
    private array $routes = [];
    private array $currentGroupChain = [];

    public function __construct() {
        $this->routeCollector = new RouteCollector(new StandardRouteParser(), new GroupCountBasedDataGenerator());
    }

    public function register(string $method, string $uri, $handler, $middlewares = []): Route {
        $uri = $this->getCurrentGroupPrefix() . $uri;
        $route = new Route($method, $uri, $handler, $middlewares);
        $route->setGroups($this->currentGroupChain); // Set group chain
        $this->routes[] = $route;
        $this->routeCollector->addRoute($route->getMethod(), $route->getUri(), $route->getHandler());
        return $route;
    }

    public function dispatch(string $httpMethod, string $uri): array {
        $dispatcher = new GroupCountBasedDispatcher($this->routeCollector->getData());
        return $dispatcher->dispatch($httpMethod, $uri);
    }

    public function group(string $prefix, callable $callback): Group {
        $group = new Group($prefix, $this);
        $this->currentGroupChain[] = $group; // Add group to chain
        $callback($group);
        array_pop($this->currentGroupChain); // Remove group from chain
        return $group;
    }

    private function getCurrentGroupPrefix(): string {
        return implode('', array_map(fn($group) => $group->getPrefix(), $this->currentGroupChain));
    }

    public function getRoutes(): array {
        return $this->routes;
    }

    public function getMiddlewaresForHandler($handler): array {
        foreach ($this->routes as $route) {
            if ($route->getHandler() === $handler) {
                return $route->getMiddlewares();
            }
        }
        return [];
    }

    public function getRoute(string $httpMethod, $handler): Route {
        foreach ($this->routes as $route) {
            if ($route->getMethod() === $httpMethod && $route->getHandler() === $handler) {
                return $route;
            }
        }

        throw new \RuntimeException("Route not found for the specified HTTP method and URI");
    }



}
