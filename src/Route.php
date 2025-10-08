<?php

declare(strict_types=1);

namespace Calcagno\Router;

final class Route
{
  private readonly string $path;
  private readonly string $method;
  private ?string $name = null;

  public function __construct(string $path, string $method)
  {
    $this->path = $this->resolvePath($path);
    $this->method = $this->resolveMethod($method);
  }

  public function name(string $name): self
  {
    if (empty($name)) {
      throw new \InvalidArgumentException("O nome da rota não pode ser vazio.");
    }

    $this->name = $name;
    return $this;
  }

  private function resolvePath(string $path): string
  {
    if (empty($path)) {
      return $path;
    }

    if ($path[0] !== '/' && $path !== '') {
      throw new \InvalidArgumentException("O path da rota deve começar com '/'.");
    }

    return $path;
  }

  private function resolveMethod(string $method): string
  {
    $method = strtoupper($method);
    $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];

    if (!in_array($method, $allowedMethods, true)) {
      throw new \InvalidArgumentException("Método HTTP inválido: {$method}.");
    }

    return $method;
  }
}
