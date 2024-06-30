# Koochik - A Simple, Elegant PHP HTTP Kernel
Koochik is a simple, elegant, and easy-to-understand PHP HTTP kernel inspired by the Slim framework. It provides a lightweight and intuitive way to handle HTTP requests and responses, allowing developers to build robust web applications effortlessly.

## Installation

You can install Koochik via Composer.

```bash
composer require koochik/koochik
```

## Basic Usage

```
require __DIR__ . '/../vendor/autoload.php';

use Koochik\Koochik\Application;
use Koochik\Koochik\Router;
use DI\ContainerBuilder;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface as Request;


$builder = new ContainerBuilder();
$container = $builder->build();
$router = new Router();
$app = new Application($router, $container);

$app->get('/welcome', function (Request $request) {
    $response = new Response();
    $response->getBody()->write('Welcome to Koochik!');
    return $response;
})
```


## Controllers
You can organize your routes using controllers for better structure and readability.

```
// src/Controllers/HomeController.php
namespace App\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;

class HomeController {
    public function welcome(Request $request) {
        $response = new Response();
        $response->getBody()->write('Welcome to Koochik via Controller!');
        return $response;
    }
}

```
then use them as

```
// index.php
$app->get('/welcome', [HomeController::class, 'welcome']);

```


## Middlewares
Middlewares can be used to modify the request and response objects, or to perform operations like authentication. You can create a middleware as:

```
// src/Middleware/AuthMiddleware.php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;

class AuthMiddleware {
    public function process(Request $request, Handler $handler): Response {
        // Example authentication check
        if ($request->getHeaderLine('X-Auth-Token') !== 'secret-token') {
            $response = new Response();
            $response->getBody()->write('Unauthorized');
            return $response->withStatus(401);
        }
        return $handler->handle($request);
    }
}

```
And then use them as:
```
// index.php
use App\Middleware\AuthMiddleware;

$app->add(AuthMiddleware::class);
```

Koochik supports dynamic route parameters to create flexible and dynamic routes.
## Dynamic route parameters
```
$app->get('/user/{id}', function (Request $request, $id) {
    $response = new Response();
    $response->getBody()->write('User ID: ' . $id);
    return $response;
});

```

## deopendency injection

Koochik uses PHP-DI for dependency injection, making it easy to manage your dependencies. Suppose you have a service like:
```
// src/Services/GreetingService.php
namespace App\Services;

class GreetingService {
    public function greet($name) {
        return "Hello, $name!";
    }
}

```
Koochik uses PHP-DI for dependency injection, making it easy to manage your dependencies. Suppose you have a service like:
```
// index.php
use App\Services\GreetingService;

$builder->addDefinitions([
    GreetingService::class => \DI\create(GreetingService::class)
]);

$app->get('/greet/{name}', function (Request $request, $name, GreetingService $greetingService) {
    $response = new Response();
    $response->getBody()->write($greetingService->greet($name));
    return $response;
});

```

## Route Groups

You can organize routes into groups to apply common middleware or attributes.

```
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



## Testing
Koochik is easy to test using PHPUnit. Here's an example test case leveraging the ApplicationTestCase.
```
<?php
namespace Tests\Routing;

use Psr\Http\Message\ServerRequestInterface as Request;
use Laminas\Diactoros\Response;
use Tests\TestCases\ApplicationTestCase;
use Tests\Mocks\Controllers\DemoController;

class RouteControllerTest extends ApplicationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define routes using controller methods
        $this->app->get('/get-route', [DemoController::class, 'hello']);
    }

    public function testGetRoute(): void
    {
        $request = $this->get('/get-route');
        $response = $this->send($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Hello world', (string)$response->getBody());
    }

}

```




