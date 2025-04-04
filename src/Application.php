<?php

namespace Koochik\Koochik;
use Koochik\Koochik\MiddlewareGroup;
use Koochik\Koochik\Contracts\RouteInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Relay\Relay;

class Application {
    private Router $router;
    private ContainerInterface $container;
    private array $middlewares = [];
    private array $middlewareGroups = [];

    private $customNotFoundHandler;
    private $customMethodNotAllowedHandler;

    public function __construct(Router $router, ContainerInterface $container) {
        $this->router = $router;
        $this->container = $container;
    }

    public function setNotFoundHandler(callable $handler){
        $this->customNotFoundHandler = $handler;
    }

    public function setMethodNotAllowedHandler(callable $handler): void {
        $this->customMethodNotAllowedHandler = $handler;
    }


    private function makeMiddlewaresInstances($middlewares): array {
        $instances = [];

        foreach ($middlewares as $middlewareName) {
            $instances[] = $this->container->get($middlewareName);
        }

        return $instances;
    }

    public function run(): void {
        $request = ServerRequestFactory::fromGlobals();
        $this->container->set(ServerRequestInterface::class, function () use ($request) {
            return $request;
        });
        $response = $this->dispatchRequest($request);
        $this->sendTheResponse($response);
    }

    //=================================== HTTP METHODS ======================================

    public function get(string $uri, $handler): Route {
        return $this->router->register('GET', $uri, $handler);
    }

    public function post(string $uri, $handler): Route {
        return $this->router->register('POST', $uri, $handler);
    }

    public function head(string $uri, $handler)
    {
        return $this->router->register('HEAD', $uri, $handler);

    }

    public function options(string $uri, $handler)
    {
        return $this->router->register('OPTIONS', $uri, $handler);

    }

    public function patch(string $uri, $handler)
    {
        return $this->router->register('PATCH', $uri, $handler);

    }

    public function delete(string $uri, $handler)
    {
        return $this->router->register('DELETE', $uri, $handler);

    }

    public function put(string $uri, $handler)
    {
        return $this->router->register('PUT', $uri, $handler);

    }


    //=================================== HTTP METHODS ======================================


    public function middleware(string | Array $middlewares): static {
        if (is_string($middlewares)) {
            $middlewares = [$middlewares];
        }
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function middlewareGroup(string $name): MiddlewareGroup {
        $middlewareGroup = new MiddlewareGroup();
        $this->middlewareGroups[$name] = $middlewareGroup;
        return $middlewareGroup;
    }


    public function dispatchRequest(ServerRequestInterface $request): ResponseInterface {
        $httpMethod = $request->getMethod();
        $uri = $request->getUri()->getPath();

        // First try to dispatch the actual request, regardless of method
        $routeInfo = $this->router->dispatch($httpMethod, $uri);

        // Create an early response based on route info
        $earlyResponse = null;

        switch ($routeInfo[0]) {
            case RouteInterface::NOT_FOUND:
                // For OPTIONS, we need special handling if the route exists but for other methods
                if ($httpMethod === 'OPTIONS') {
                    // Check if this path exists for other methods
                    $allowedMethods = $this->getAllowedMethods($uri);
                    if (!empty($allowedMethods)) {
                        // Automatically add HEAD if GET is present
                        if (in_array('GET', $allowedMethods) && !in_array('HEAD', $allowedMethods)) {
                            $allowedMethods[] = 'HEAD';
                        }
                        $allowedMethods[] = 'OPTIONS';
                        $earlyResponse = new Response();
                        $earlyResponse = $earlyResponse->withHeader('Allow', implode(', ', array_unique($allowedMethods)));
                    } else {
                        $earlyResponse = $this->customNotFoundHandler
                            ? ($this->customNotFoundHandler)($request)
                            : $this->getNotFoundResponse();
                    }
                } else {
                    $earlyResponse = $this->customNotFoundHandler
                        ? ($this->customNotFoundHandler)($request)
                        : $this->getNotFoundResponse();
                }
                break;

            case RouteInterface::METHOD_NOT_ALLOWED:
                if ($httpMethod === 'OPTIONS') {
                    // Return OPTIONS response with Allow header
                    $allowedMethods = $routeInfo[1] ?? []; // This contains allowed methods from router
                    // Automatically add HEAD if GET is present
                    if (in_array('GET', $allowedMethods) && !in_array('HEAD', $allowedMethods)) {
                        $allowedMethods[] = 'HEAD';
                    }
                    $allowedMethods[] = 'OPTIONS';
                    $earlyResponse = new Response();
                    $earlyResponse = $earlyResponse->withHeader('Allow', implode(', ', array_unique($allowedMethods)));
                } else {
                    $earlyResponse = $this->customMethodNotAllowedHandler
                        ? ($this->customMethodNotAllowedHandler)($request)
                        : $this->makeMethodNotAllowedResponse();
                }
                break;

            case RouteInterface::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                if (is_callable($handler)) {
                    return $this->makeFoundResponseFromCallback($handler, $vars, $request);
                } else {
                    [$controllerName, $method] = $handler;
                    return $this->makeFoundResponse($controllerName, $httpMethod, $handler, $method, $vars, $request);
                }
                break;

            default:
                $earlyResponse = $this->makeDefaultResponse();
        }

        // If we have an early response (for OPTIONS, NOT_FOUND, METHOD_NOT_ALLOWED),
        // pass it through the middleware stack
        if ($earlyResponse) {
            // Use global middlewares for automatic responses
            $middlewares = $this->makeMiddlewaresInstances($this->middlewares);

            // Create a handler for the response
            $finalHandler = function($request) use ($earlyResponse) {
                return $earlyResponse;
            };

            return $this->handleMiddlewaresAndController($middlewares, $finalHandler, $request);
        }

        // This should never be reached as all cases should be handled above
        return new Response();
    }

    private function getAllowedMethods(string $uri): array {
        $allowedMethods = [];
        $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD']; // List of methods to check

        foreach ($methods as $method) {
            $routeInfo = $this->router->dispatch($method, $uri);
            if ($routeInfo[0] === RouteInterface::FOUND) {
                $allowedMethods[] = $method;
            }
        }

        // If GET is allowed but HEAD isn't explicitly defined, add HEAD
        if (in_array('GET', $allowedMethods) && !in_array('HEAD', $allowedMethods)) {
            $allowedMethods[] = 'HEAD';
        }

        return array_unique($allowedMethods);
    }


    public function getNotFoundResponse(): Response {
        $response = new Response('php://memory', 404);
        $response->getBody()->write('404 Not Found');
        return $response;
    }

    public function makeMethodNotAllowedResponse(): Response {
        $response = new Response('php://memory', 405);
        $response->getBody()->write('405 Method Not Allowed');
        return $response;
    }

    public function makeDefaultResponse(): Response {
        $response = new Response('php://memory', 500);
        $response->getBody()->write('500 Internal Server Error');
        return $response;
    }

    public function sendTheResponse(ResponseInterface $response): void {
        header(sprintf(
            'HTTP/%s %d %s',
            '1.1',
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ));
        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $header, $value), false);
            }
        }

        echo $response->getBody();
    }

    public function group(string $prefix, callable $callback): Group {
        return $this->router->group($prefix, $callback);
    }

    private function getMiddlewares(Route $route): array {

        $TotalRouteMiddlewares = [];
        $TotalRouteMiddlewaresGroups=[];

        //Add global middlewares
        $TotalRouteMiddlewares = array_merge($TotalRouteMiddlewares, $this->middlewares);

        // Add route specific middlewares
        $TotalRouteMiddlewares = array_merge($TotalRouteMiddlewares, $route->getMiddlewares());

        // Add route specific middleware Groups
        $TotalRouteMiddlewaresGroups = array_merge($TotalRouteMiddlewaresGroups, $route->getMiddlewareGroups());




        // Add middlewares and middleware groups from groups the route belong to
        foreach ($route->getGroups() as $group) {
            $TotalRouteMiddlewares = array_merge($TotalRouteMiddlewares, $group->getMiddlewares());
            $TotalRouteMiddlewaresGroups = array_merge($TotalRouteMiddlewaresGroups, $group->getMiddlewareGroups());

        }


        // Next, add middlewares from middleware groups

        foreach ($TotalRouteMiddlewaresGroups as $middlewaresGroup) {
            $TotalRouteMiddlewares = array_merge($TotalRouteMiddlewares, $this->middlewareGroups[$middlewaresGroup]->getMiddlewares());
        }

        // return the array of middleware instances
        return $this->makeMiddlewaresInstances($TotalRouteMiddlewares);
    }


    private function handleMiddlewaresAndController(array $middlewares, callable $controllerHandler, ServerRequestInterface $request): ResponseInterface {
        $middlewares[] = new class($controllerHandler) implements \Psr\Http\Server\MiddlewareInterface {
            private $controllerHandler;

            public function __construct(callable $controllerHandler) {
                $this->controllerHandler = $controllerHandler;
            }

            public function process(ServerRequestInterface $request, \Psr\Http\Server\RequestHandlerInterface $handler): ResponseInterface {
                return ($this->controllerHandler)($request);
            }
        };

        $relay = new Relay($middlewares);
        return $relay->handle($request);
    }

    public function makeFoundResponse($controllerName, $httpMethod, $handler, $method, $vars, $request): ResponseInterface {
        $controller = $this->container->get($controllerName);

        $route = $this->router->getRoute($httpMethod, $handler);
        $middlewares = $this->getMiddlewares($route);

        $controllerHandler = function ($request) use ($controller, $method, $vars) {
            return $this->container->call([$controller, $method], $vars);
        };

        return $this->handleMiddlewaresAndController($middlewares, $controllerHandler, $request);
    }

    private function makeFoundResponseFromCallback(callable $handler, array $vars, ServerRequestInterface $request): ResponseInterface {
        $route = $this->router->getRoute($request->getMethod(), $handler);

        $middlewares = $this->getMiddlewares($route);

        $controllerHandler = function ($request) use ($handler, $vars) {
            return $this->container->call($handler, $vars);
        };

        return $this->handleMiddlewaresAndController($middlewares, $controllerHandler, $request);
    }

    public function getRoutes(): array
    {
        return $this->router->getRoutes();
    }

    public function getContainer(): ContainerInterface {
        return $this->container;
    }

}
