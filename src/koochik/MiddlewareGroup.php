<?php

namespace Koochik\Koochik;

class MiddlewareGroup
{
    private array $middlewares=[];
    public function __construct($middlewares=[])
    {
        $this->middlewares = $middlewares;

    }

    public function middleware($middlewares): static
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function getMiddlewares()
    {
        return $this->middlewares;
    }

}