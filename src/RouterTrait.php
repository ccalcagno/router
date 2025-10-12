<?php

namespace Calcagno\Router;

use InvalidArgumentException;
use Throwable;

/**
 * Trait RouterTrait
 * @package Calcagno\Router
 */
trait RouterTrait
{
  /** @const int Bad Request */
  public const BAD_REQUEST = 400;

  /** @const int Not Found */
  public const NOT_FOUND = 404;

  /** @const int Method Not Allowed */
  public const METHOD_NOT_ALLOWED = 405;

  /** @const int Not Implemented */
  public const NOT_IMPLEMENTED = 501;

  /** @const int Internal Server Error */
  public const INTERNAL_SERVER_ERROR = 500;

  /** @const int Forbiden */
  public const FORBIDDEN = 403;

  /**
   * @param string $method
   * @param string $route
   * @param callable|string $handler
   * @param string|null $name
   * @param array|string|null $middleware
   */
  protected function addRoute(
    string $method,
    string $route,
    callable|string|array $handler,
    ?string $name = null,
    null|array|string $middleware = null
  ): Route {
    $route = rtrim($route, "/");
    $route = (!$this->group ? $route : "/{$this->group}{$route}");

    $data = $this->data;
    $namespace = $this->namespace;
    $middleware = $middleware ?? (!empty($this->middleware[$this->group]) ? $this->middleware[$this->group] : null);
    $router = function () use ($method, $handler, $data, $route, $name, $namespace, $middleware) {
      return [
        "route" => $route,
        "name" => $name,
        "method" => $method,
        "middlewares" => $middleware,
        "handler" => $this->handler($handler, $namespace),
        "action" => $this->action($handler),
        "data" => $data
      ];
    };

    $route = preg_replace('~{([^}]*)}~', "([^/]+)", $route);
    $this->routes[$method][$route] = $router();

    return new Route($route, $method, $handler);
  }

  private function resolvePath(string $path): string
  {
    $path = trim($path, '/');

    if ($path === '') {
      return $path;
    }

    return "/{$path}";
  }

  /**
   * @return bool
   */
  private function execute(): bool
  {
    try {
      if ($this->route === null) {
        throw new HttpException('Rota não encontrada.', self::NOT_FOUND);
      }

      if (!$this->middleware()) {
        throw new HttpException('Middleware bloqueou a execução.', self::FORBIDDEN);
      }

      if (is_callable($this->route['handler'])) {
        call_user_func($this->route['handler'], ($this->route['data'] ?? []), $this);
        return true;
      }

      $controller = $this->route['handler'];
      $method = $this->route['action'];

      if (!class_exists($controller)) {
        throw new HttpException("Controller {$controller} não encontrado.", self::BAD_REQUEST);
      }

      if (!method_exists($controller, $method)) {
        throw new HttpException("Método {$method} não permitido em {$controller}.", self::METHOD_NOT_ALLOWED);
      }

      $instance = new $controller($this);
      $params = $this->getParams($instance, $method);

      call_user_func_array([$instance, $method], $params);
      return true;
    } catch (HttpException $e) {
      $this->error = $e->getStatusCode();
    } catch (InvalidArgumentException) {
      $this->error = self::BAD_REQUEST;
    } catch (Throwable) {
      $this->error = self::INTERNAL_SERVER_ERROR;
    }

    return false;
  }

  private function getParams(object $instance, string $method): array
  {
    $pathParams = $this->getPathParams();
    $queryParams = $this->getQueryParams();
    $bodyParams = $this->getBodyParams();

    $reflection = new \ReflectionMethod($instance, $method);
    $args = [];

    foreach ($reflection->getParameters() as $param) {
      $name = $param->getName();
      $type = $param->getType();

      if ($name === 'input') {
        $args[] = $bodyParams;
        continue;
      }

      if (array_key_exists($name, $pathParams)) {
        $args[] = $this->castValue($pathParams[$name], $type);
        continue;
      }

      if (array_key_exists($name, $queryParams)) {
        $args[] = $this->castValue($queryParams[$name], $type);
        continue;
      }

      if ($param->isDefaultValueAvailable()) {
        $args[] = $param->getDefaultValue();
        continue;
      }

      throw new \InvalidArgumentException("Missing required parameter '{$name}' for {$reflection->getDeclaringClass()->getName()}::{$method}()");
    }

    return $args;
  }

  private function castValue(mixed $value, ?\ReflectionType $type): mixed
  {
    if (!$type instanceof \ReflectionNamedType || $type->isBuiltin() === false) {
      return $value;
    }

    $typeName = $type->getName();

    if ($value === null) {
      return null;
    }

    return match ($typeName) {
      'int'    => (int) $value,
      'float'  => (float) $value,
      'bool'   => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
      'string' => (string) $value,
      'array'  => (array) $value,
      default  => $value,
    };
  }

  private function getPathParams(): array
  {
    $removeGroupFromPath = $this->group ? str_replace($this->group, "", $this->request->path()) : $this->request->path();
    $pathAssoc = trim($removeGroupFromPath, "/");
    $routeAssoc = trim($this->route['route'], "/");

    preg_match_all("~\{\s* ([a-zA-Z_][a-zA-Z0-9_-]*) \}~x", $routeAssoc, $keys, PREG_SET_ORDER);
    $routeDiff = array_values(array_diff_assoc(explode("/", $pathAssoc), explode("/", $routeAssoc)));

    $pathParams = [];
    $offset = 0;
    foreach ($keys as $key) {
      $pathParams[$key[1]] = ($routeDiff[$offset++] ?? null);
    }

    return $pathParams;
  }

  private function getQueryParams(): array
  {
    $queryParams = filter_input_array(INPUT_GET, FILTER_DEFAULT);
    unset($queryParams['route']);

    return $queryParams;
  }

  private function getBodyParams(): array
  {
    $post = filter_input_array(INPUT_POST, FILTER_DEFAULT);
    $bodyParams = [];

    if ((!empty($post['_method']) && in_array($post['_method'], ["PUT", "PATCH", "DELETE"])) || $this->request->method() == "POST") {
      unset($post['_method']);
      $bodyParams = $post;
    } elseif (in_array($this->request->method(), ["PUT", "PATCH", "DELETE"]) && !empty($_SERVER['CONTENT_LENGTH'])) {
      parse_str(file_get_contents('php://input', false, null, 0, $_SERVER['CONTENT_LENGTH']), $input);
      unset($input['_method']);

      $bodyParams = $input;
    }

    return $bodyParams;
  }

  /**
   * @return bool
   */
  private function middleware(): bool
  {
    if (empty($this->route["middlewares"])) {
      return true;
    }

    $middlewares = is_array(
      $this->route["middlewares"]
    ) ? $this->route["middlewares"] : [$this->route["middlewares"]];

    foreach ($middlewares as $middleware) {
      if (class_exists($middleware)) {
        $newMiddleware = new $middleware;
        if (method_exists($newMiddleware, "handle")) {
          if (!$newMiddleware->handle($this)) {
            return false;
          }
        } else {
          $this->error = self::METHOD_NOT_ALLOWED;
          return false;
        }
      } else {
        $this->error = self::NOT_IMPLEMENTED;
        return false;
      }
    }

    return true;
  }

  /**
   * @param callable|string $handler
   * @param string|null $namespace
   * @return callable|string
   */
  private function handler(callable|string|array $handler, ?string $namespace): callable|string
  {
    if (is_callable($handler)) {
      return $handler;
    }

    if (is_array($handler) && count($handler) === 2) {
      return $handler[0];
    }

    if (is_string($handler)) {
      return "{$namespace}\\" . explode($this->separator, $handler)[0];
    }

    return $handler;
  }

  /**
   * @param callable|string $handler
   * @return string|null
   */
  private function action(callable|string|array $handler): ?string
  {
    if (is_array($handler) && count($handler) === 2) {
      return $handler[1];
    }

    if (is_string($handler)) {
      return explode($this->separator, $handler)[1] ?? null;
    }

    return null;
  }

  /**
   * @param array $route_item
   * @param array|null $data
   * @return string|null
   */
  private function treat(array $route_item, ?array $data = null): ?string
  {
    $route = $route_item["route"];
    if (!empty($data)) {
      $arguments = [];
      $params = [];
      foreach ($data as $key => $value) {
        if (!strstr($route, "{{$key}}")) {
          $params[$key] = $value;
        }
        $arguments["{{$key}}"] = $value;
      }
      $route = $this->process($route, $arguments, $params);
    }

    return "{$this->projectUrl}{$route}";
  }

  /**
   * @param string $route
   * @param array $arguments
   * @param array|null $params
   * @return string
   */
  private function process(string $route, array $arguments, ?array $params = null): string
  {
    $params = (!empty($params) ? "?" . http_build_query($params) : null);
    return str_replace(array_keys($arguments), array_values($arguments), $route) . "{$params}";
  }
}
