<?php

namespace Calcagno\Router;

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
    callable|string $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): void {
    $this->addRoute("GET", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function post(
    string $route,
    callable|string $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): void {
    $this->addRoute("POST", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function put(
    string $route,
    callable|string $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): void {
    $this->addRoute("PUT", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function patch(
    string $route,
    callable|string $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): void {
    $this->addRoute("PATCH", $route, $handler, $name, $middleware);
  }

  /**
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  public function delete(
    string $route,
    callable|string $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): void {
    $this->addRoute("DELETE", $route, $handler, $name, $middleware);
  }
}
