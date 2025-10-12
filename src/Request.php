<?php

declare(strict_types=1);

namespace Calcagno\Router;

final class Request
{
  private array $get;
  private array $post;
  private array $server;
  private array $headers;
  private array $body;
  private readonly string $method;
  private readonly string $path;

  public function __construct()
  {
    $this->server = $_SERVER;
    $this->get = filter_input_array(INPUT_GET, FILTER_DEFAULT) ?: [];
    $this->post = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];
    $this->headers = $this->parseHeaders();
    $this->body = $this->parseBody();
    $this->method = $this->detectMethod();
    $this->path = $this->detectPath();
  }

  private function parseHeaders(): array
  {
    $headers = [];
    foreach ($this->server as $key => $value) {
      if (str_starts_with($key, 'HTTP_')) {
        $name = str_replace('_', '-', substr($key, 5));
        $headers[$name] = $value;
      }
    }
    return $headers;
  }

  private function parseBody(): array
  {
    $data = [];

    $contentType = $this->server['CONTENT_TYPE'] ?? '';
    $raw = file_get_contents('php://input');

    if (str_contains($contentType, 'application/json')) {
      $data = json_decode($raw, true) ?: [];
    } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
      parse_str($raw, $data);
    }

    return $data;
  }

  private function detectMethod(): string
  {
    $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];
    $method = $this->server['REQUEST_METHOD'] ?? 'GET';

    if (isset($this->post['_method'])) {
      $spoof = strtoupper($this->post['_method']);
      if (in_array($spoof, $validMethods)) {
        $method = $spoof;
      }
      unset($this->post['_method']);
    }

    if (!in_array($method, $validMethods)) {
      throw new \InvalidArgumentException("Invalid HTTP method: {$method}", 400);
    }


    return $method;
  }

  private function detectPath(): string
  {
    $route = $this->get['route'] ?? '/';
    return '/' . trim($route, '/');
  }

  public function method(): string
  {
    return $this->method;
  }

  public function path(): string
  {
    return $this->path;
  }
}
