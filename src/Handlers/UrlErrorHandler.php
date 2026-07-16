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
use Slim\Views\PhpRenderer;

class UrlErrorHandler extends ErrorHandler
{
    private const array ERROR_CODES_INFO = [
        "50001" => "URL ID имеет не корректный тип данных",
        "50002" => "URL Timestamp имеет не корректный тип данных",
        "50003" => "PDO не возвращает ID последнего сохранённого элемента",
        "50004" => "Не возможно получить объект UrlInterface в проверке",
        "50005" => "Переменная должна быть строкового типа"
    ];

    public function __construct(
        private PhpRenderer $renderer,
        CallableResolverInterface $callableResolver,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface $logger = null
    ) {
        $this->renderer = $renderer;
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
            $code = $this->exception->getErrorCode();
            $responseErrorCode = intval(mb_substr(strval($code), 0, 3));
            $response = $this->responseFactory->createResponse($responseErrorCode);
            $response = $response->withHeader('Content-type', $this->defaultErrorRendererContentType);

            $message = "Ошибка уже в обработке. Приносим извинения за неудобства.";
            if ($this->displayErrorDetails) {
                $message = self::ERROR_CODES_INFO[$code] ?? 'Неизвестная ошибка';
            }

            $params = [
                'details' => $this->displayErrorDetails,
                'code' => $responseErrorCode,
                'message' => $message
            ];

            return $this->renderer->render(
                $response,
                'exceptions/urlException.phtml',
                $params
            );
        } else {
            return parent::respond();
        }
    }
}
