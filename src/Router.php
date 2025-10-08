<?php

namespace Calcagno\Router;

use InvalidArgumentException;

/**
 * Class Calcagno Router
 *
 * @author Robson V. Leite <https://github.com/robsonvleite>
 * @package Calcagno\Router
 */
final class Router extends Dispatch
{
  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function get(
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    return $this->addRoute("GET", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function post(
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    return $this->addRoute("POST", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function put(
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    return $this->addRoute("PUT", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function patch(
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    return $this->addRoute("PATCH", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function delete(
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    return $this->addRoute("DELETE", $route, $handler, $name, $middleware);
  }

  public function resource(string $name, string $controller)
  {
    if (!class_exists($controller)) {
      throw new InvalidArgumentException("Class {$controller} not found!");
    }

    $this->get("/$name",              [$controller, 'index']);
    $this->get("/$name/create",       [$controller, 'create']);
    $this->post("/$name",             [$controller, 'store']);
    $this->get("/$name/{id}",         [$controller, 'show']);
    $this->get("/$name/{id}/edit",    [$controller, 'edit']);
    $this->put("/$name/{id}",         [$controller, 'update']);
    $this->patch("/$name/{id}",       [$controller, 'update']);
    $this->delete("/$name/{id}",      [$controller, 'destroy']);
  }
}
