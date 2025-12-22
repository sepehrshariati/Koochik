# HTTP Kernel

A lightweight, elegant PSR-7 / PSR-15 HTTP kernel used as the core routing and middleware engine inside the Kouchik framework.

This package provides a simple and intuitive way to handle HTTP requests, routing, middleware, and dependency injection, inspired by Slim.

---

## Installation

Install via Composer:

```bash
composer require kouchik/http-kernel
```

---

## Basic Usage

```php
require __DIR__ . '/../vendor/autoload.php';

use Kouchik\HttpKernel\Application;
use Kouchik\HttpKernel\Router;
use DI\ContainerBuilder;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$builder = new ContainerBuilder();
$container = $builder->build();

$router = new Router();
$app = new Application($router, $container);

$app->get('/welcome', function (Request $request) {
    $response = new Response();
    $response->getBody()->write('Welcome!');
    return $response;
});
```

---

## Controllers

You can organize your routes using controllers for better structure and readability.

```php
// src/Controllers/HomeController.php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;

class HomeController
{
    public function welcome(Request $request)
    {
        $response = new Response();
        $response->getBody()->write('Welcome via Controller!');
        return $response;
    }
}
```

Register the controller method as a route:

```php
// index.php
use App\Controllers\HomeController;

$app->get('/welcome', [HomeController::class, 'welcome']);
```

---

## Middlewares

Middlewares can modify the request/response flow or perform tasks such as authentication.

```php
// src/Middleware/AuthMiddleware.php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response;

class AuthMiddleware
{
    public function process(Request $request, Handler $handler): ResponseInterface
    {
        if ($request->getHeaderLine('X-Auth-Token') !== 'secret-token') {
            $response = new Response();
            $response->getBody()->write('Unauthorized');
            return $response->withStatus(401);
        }

        return $handler->handle($request);
    }
}
```

Attach middleware:

```php
use App\Middleware\AuthMiddleware;

$app->add(AuthMiddleware::class);
```

---

## Dynamic Route Parameters

HTTP Kernel supports dynamic route parameters.

```php
$app->get('/user/{id}', function (Request $request, $id) {
    $response = new Response();
    $response->getBody()->write('User ID: ' . $id);
    return $response;
});
```

---

## Dependency Injection

HTTP Kernel uses **PHP-DI** for dependency injection.

### Define a service

```php
// src/Services/GreetingService.php
namespace App\Services;

class GreetingService
{
    public function greet(string $name): string
    {
        return "Hello, {$name}!";
    }
}
```

### Register and inject it

```php
use App\Services\GreetingService;

$builder->addDefinitions([
    GreetingService::class => \DI\create(GreetingService::class),
]);

$app->get('/greet/{name}', function (
    Request $request,
    $name,
    GreetingService $greetingService
) {
    $response = new Response();
    $response->getBody()->write($greetingService->greet($name));
    return $response;
});
```

---

## Route Groups

Routes can be grouped and assigned shared middleware.

```php
$app->group('/api', function () use ($app) {
    $app->get('/users', function (Request $request) {
        $response = new Response();
        $response->getBody()->write('User List');
        return $response;
    });

    $app->get('/posts', function (Request $request) {
        $response = new Response();
        $response->getBody()->write('Post List');
        return $response;
    });
})->add(AuthMiddleware::class);
```

---

## Testing

HTTP Kernel is easy to test using PHPUnit.

```php
<?php

namespace Kouchik\HttpKernel\Tests\Routing;

use Tests\TestCases\ApplicationTestCase;
use Tests\Mocks\Controllers\DemoController;

class RouteControllerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->get('/get-route', [DemoController::class, 'hello']);
    }

    public function testGetRoute(): void
    {
        $request = $this->get('/get-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string) $response->getBody());
    }
}
```