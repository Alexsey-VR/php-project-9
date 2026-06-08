<?php

namespace Analyzer\Exceptions;

use Analyzer\Interfaces\AppExceptionInterface;
use Throwable;

class UrlsCreateActionException extends AppException implements AppExceptionInterface
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
