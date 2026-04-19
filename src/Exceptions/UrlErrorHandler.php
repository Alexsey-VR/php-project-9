<?php

namespace Analyzer\Exceptions;

use Slim\Handlers\ErrorHandler;
use Slim\Exception\HttpException;
use Slim\Interfaces\CallableResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;

class UrlErrorHandler extends ErrorHandler
{
    public function __construct(
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            $callableResolver,
            $responseFactory,
            $logger
        );
    }
}
