<?php

declare(strict_types=1);

namespace LaraLinky\Exceptions;

use Illuminate\Http\Client\Response;
use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

final class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $status = null,
        public readonly mixed $responseBody = null,
        public readonly ?RequestInterface $request = null,
        public readonly ?Response $response = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $status ?? 0, $previous);
    }
}
