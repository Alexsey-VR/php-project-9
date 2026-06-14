<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Interfaces\UrlInterface;
use Analyzer\Interfaces\AppExceptionInterface;
use Analyzer\Exceptions\UrlsReadActionException;
use Analyzer\Exceptions\{UrlException, UrlRepositoryException};
use Analyzer\Exceptions\{UrlCheckException, UrlCheckRepositoryException};

class UrlReadAction
{
    private ValidatedUrlRepository $urlRepository;
    private UrlCheckRepository $urlCheckRepository;
    private Messages $flash;
    private PhpRenderer $renderer;

    public function __construct(
        ValidatedUrlRepository $urlRepository,
        UrlCheckRepository $urlCheckRepository,
        PhpRenderer $renderer,
        Messages $flash
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlCheckRepository = $urlCheckRepository;
        $this->flash = $flash;
        $this->renderer = $renderer;
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): PsrResponseInterface {
        try {
            $id = $args['id'];

            $url = $this->urlRepository->find($id);
            $messages = $this->flash->getMessages();
            $checks = $this->urlCheckRepository->getEntitiesByUrlId($id);
            $checkItems = [];
            foreach ($checks as $check) {
                $checkItems[] = [
                    'id' => $check->getId(),
                    'status' => $check->getStatus(),
                    'h1' => $check->getH1(),
                    'title' => $check->getTitle(),
                    'description' => $check->getDescription(),
                    'timestamp' => $check->getTimestamp()
                ];
            }

            $params = [
                'name' => ($url instanceof UrlInterface) ? $url->getUrl() : '',
                'id' => ($url instanceof UrlInterface) ? $url->getId() : '',
                'timestamp' => ($url instanceof UrlInterface) ? $url->getTimestamp() : '',
                'messages' => $messages,
                'checks' => $checkItems
            ];

            if (!is_null($url)) {
                return $this->renderer
                    ->render(
                        $response,
                        'Urls/url.phtml',
                        $params
                    );
            }

            $params = [
                'details' => false,
                'code' => 404,
                'message' => 'Not found',
            ];

            return $this->renderer->render(
                $response->withStatus(404),
                '/Exceptions/urlException.phtml',
                $params
            );
        } catch (AppExceptionInterface $exception) {
            $data = file_get_contents(__DIR__ . "/../Exceptions/errorCodesInfo.json");
            $errorCodesInfo = json_decode(
                $data ?: '',
                flags:JSON_OBJECT_AS_ARRAY
            );
            $errorCode = strval($exception->getErrorCode());
            $debugMessage = $errorCodesInfo[$errorCode];

            if (
                $exception instanceof UrlException ||
                $exception instanceof UrlRepositoryException ||
                $exception instanceof UrlCheckException ||
                $exception instanceof UrlCheckRepositoryException
            ) {
                throw new UrlsReadActionException(
                    $debugMessage,
                    intval(mb_substr($errorCode, 0, 3)),
                    $exception
                );
            }

            throw new UrlsReadActionException(
                "Неизвестная ошибка",
                intval(mb_substr($errorCode, 0, 3)),
                $exception
            );
        }
    }
}
