<?php

namespace Analyzer\Exceptions;

use Analyzer\Interfaces\AppExceptionInterface;
use Throwable;

class UrlCheckException extends AppException
{
    public function __construct(int $code, ?Throwable $previous = null)
    {
        parent::__construct('', $code, $previous);
    }
}
