<?php

namespace Koochik\Koochik;

class Group
{
    private string $prefix;
    private array $middlewares = [];
    private array $middlewareGroups = [];
    private Router $router;

    public function __construct(string $prefix, Router $router)
    {
        $this->prefix = $prefix;
        $this->router = $router;
    }


    public function middleware(string | Array $middlewares): static {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }


//    public function middlewareGroup(string $group): static
//    {
//        $this->middlewareGroups[] = $group;
//        return $this;
//    }

    public function middlewareGroup($group): static {
        if (is_string($group)) {
            $group = [$group];
        }
        $this->middlewareGroups = array_merge($this->middlewareGroups, $group);
        return $this;
    }



    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function group(string $prefix, callable $callback): Group
    {
        $fullPrefix = $this->prefix . $prefix;
        return $this->router->group($fullPrefix, $callback);
    }

}
