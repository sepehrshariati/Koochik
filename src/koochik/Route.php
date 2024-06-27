<?php

namespace Koochik\Koochik;

use Koochik\Koochik\Contracts\RouteInterface;

class Route implements RouteInterface {
    private string $method;
    private string $uri;
    private $handler;
    private array $middlewares = [];
    private array $middlewareGroups = [];
    private array $groups;
    private string $name;

    public function __construct(string $method, string $uri, $handler, $middlewares = []) {
        $this->method = $method;
        $this->uri = $uri;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
        $this->name= '';

    }

    public function getMethod(): string {
        return $this->method;
    }

    public function getUri(): string {
        return $this->uri;
    }

    public function getHandler() {
        return $this->handler;
    }


    public function middleware($middlewares): static {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }



    public function getMiddlewares(): array {
        return $this->middlewares;
    }

    public function setGroups(array $groups): void {
        $this->groups = $groups;
    }

    public function getGroups(): array {
        return $this->groups;
    }


    public function middlewareGroup($middlewareGroups): static {
        if (is_string($middlewareGroups)) {
            $middlewareGroups = [$middlewareGroups];
        }
        $this->middlewareGroups = array_merge($this->middlewareGroups, $middlewareGroups);
        return $this;
    }


    public function name($name): static {
        $this->name = $name;
        return $this;
    }

    public function getName(): string {
        return $this->name;
    }


    public function getMiddlewareGroups(): array {
        return $this->middlewareGroups;
    }

}
