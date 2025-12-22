<?php
namespace Kouchik\HttpKernel\Contracts;
interface RouteInterface
{
    // Define route dispatching constants
    public const NOT_FOUND = 0;
    public const FOUND = 1;
    public const METHOD_NOT_ALLOWED = 2;


    public function getMethod(): string;

    public function getUri(): string;

    public function getHandler();

    public function getMiddlewares(): array;

    public function Middleware($middlewares): static;
}