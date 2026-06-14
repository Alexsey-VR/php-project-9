<?php

namespace Analyzer\Exceptions;

use Analyzer\Interfaces\AppExceptionInterface;
use Exception;

abstract class AppException extends Exception implements AppExceptionInterface
{
    public function getErrorCode(): int
    {
        return $this->code;
    }
}
