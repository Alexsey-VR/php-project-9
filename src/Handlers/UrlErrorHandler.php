<?php

namespace Analyzer\Handlers;

use Slim\Handlers\ErrorHandler;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Interfaces\CallableResolverInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Analyzer\Exceptions\{UrlException, UrlRepositoryException};
use Analyzer\Exceptions\{UrlCheckException, UrlCheckRepositoryException};
use PDOException;

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

    protected function respond(): ResponseInterface
    {
        if (
            $this->exception instanceof UrlException
            || $this->exception instanceof UrlCheckException
            || $this->exception instanceof UrlCheckRepositoryException
            || $this->exception instanceof UrlRepositoryException
        ) {
            $this->statusCode = $this->exception->getCode() ?: 500;
            $response = $this->responseFactory->createResponse($this->statusCode);
            $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);
            $renderer = $this->determineRenderer();
            $body = call_user_func($renderer, $this->exception, $this->displayErrorDetails);
            if ($body !== false) {
                /** @var string $body */
                $response->getBody()->write($body);
            }

            return $response;
        } else {
            return parent::respond();
        }
    }
}
