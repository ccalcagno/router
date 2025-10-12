<?php

declare(strict_types=1);

namespace Calcagno\Router;

class HttpException extends \Exception
{
  public function __construct(string $message, int $statusCode)
  {
    parent::__construct($message, $statusCode);
  }

  public function getStatusCode(): int
  {
    return $this->getCode();
  }
}
