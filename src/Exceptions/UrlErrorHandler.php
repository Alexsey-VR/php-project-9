<?php

namespace Analyzer\Exceptions;

use Slim\Handlers\ErrorHandler;
use Slim\Exception\HttpException;

class UrlErrorHandler extends ErrorHandler
{
    protected function determineStatusCode(): int
    {
        $exceptionCode = 200;
        if ($this->method === 'OPTIONS') {
            return $exceptionCode;
        } elseif ($this->exception instanceof HttpException) {
            $exceptionCode = $this->exception->getCode();
        } else {
            $exceptionCode = 500;
        }

        return $exceptionCode;
    }
}
