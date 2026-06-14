<?php

namespace Analyzer\Exceptions;

use Analyzer\Interfaces\AppExceptionInterface;
use Throwable;

class UrlCheckRepositoryException extends AppException implements AppExceptionInterface
{
    public function __construct(int $code, ?Throwable $previous = null)
    {
        parent::__construct('', $code, $previous);
    }
}
