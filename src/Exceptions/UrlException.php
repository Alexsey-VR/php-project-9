<?php

namespace Analyzer\Exceptions;

use Exception;
use Throwable;

class UrlException extends Exception
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
