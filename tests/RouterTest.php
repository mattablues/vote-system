<?php

declare(strict_types=1);

namespace Radix\Tests;

use PHPUnit\Framework\TestCase;
use Radix\Routing\Router;

class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    public function testCanAddGetRoute(): void
    {
        $this->router->get('/test', function () {
            return 'Test passed';
        });

        $routes = $this->router->routes();

        /** @var array{path:string,params:array<string,mixed>} $firstRoute */
        $firstRoute = $routes[0];

        /** @var array<string,mixed> $params */
        $params = $firstRoute['params'];

        $this->assertCount(1, $routes);
        $this->assertEquals('/test', $firstRoute['path']);
        $this->assertEquals('GET', $params['method']);
    }

    public function testCanMatchRoute(): void
    {
        $this->router->get('/test/{id:\d+}', function () {
            return 'Test passed';
        });

        $route = $this->router->match('/test/123', 'GET');

        $this->assertNotFalse($route);
        $this->assertEquals('123', $route['id']);
    }

    public function testRouteNotFound(): void
    {
        $this->router->get('/test', function () {
            return 'Test passed';
        });

        $route = $this->router->match('/non-existent', 'GET');

        $this->assertFalse($route);
    }

    public function testCannotAddDuplicateRoute(): void
    {
        $this->router->get('/test', function () {
            return 'Test passed';
        });

        $this->expectException(\InvalidArgumentException::class);
        $this->router->get('/test', function () {
            return 'Duplicate';
        });
    }

    public function testCanAddMiddlewareToRoute(): void
    {
        $this->router->get('/test', function () {
            return 'Test passed';
        })->middleware(['auth', 'logging']);

        $route = $this->router->routes()[0];

        $this->assertArrayHasKey('middlewares', $route);
        $this->assertEquals(['auth', 'logging'], $route['middlewares']);
    }

    public function testCanAddMiddlewareToGroup(): void
    {
        // Definiera en middleware-grupp
        $this->router->group(['middleware' => ['auth', 'logging']], function (Router $router) {
            $router->get('/route1', function () {
                return 'Route 1';
            });

            $router->post('/route2', function () {
                return 'Route 2';
            });
        });

        $routes = $this->router->routes();

        // Kontrollera att båda rutterna innehåller middleware från gruppen
        $this->assertArrayHasKey('middlewares', $routes[0]);
        $this->assertEquals(['auth', 'logging'], $routes[0]['middlewares']);
        $this->assertArrayHasKey('middlewares', $routes[1]);
        $this->assertEquals(['auth', 'logging'], $routes[1]['middlewares']);
    }

    public function testGroupMiddlewareDoesNotAffectOtherRoutes(): void
    {
        // Definiera en middleware-grupp
        $this->router->group(['middleware' => ['auth']], function (Router $router) {
            $router->get('/secured', function () {
                return 'Secured route';
            });
        });

        // Lägg till en rutt utanför gruppen
        $this->router->get('/public', function () {
            return 'Public route';
        });

        $routes = $this->router->routes();

        // Kontrollera att '/secured' har middleware, men inte '/public'
        $this->assertArrayHasKey('middlewares', $routes[0]);
        $this->assertEquals(['auth'], $routes[0]['middlewares']);
        $this->assertArrayNotHasKey('middlewares', $routes[1]);
    }
}