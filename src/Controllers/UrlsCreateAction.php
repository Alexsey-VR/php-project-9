<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Slim\Http\ServerRequest;
use Analyzer\Repository\ValidatedUrlRepository;
use Analyzer\Url\Url;
use Analyzer\Interfaces\AppExceptionInterface;
use Analyzer\Exceptions\{UrlException, UrlRepositoryException, UrlsCreateActionException};

class UrlsCreateAction
{
    private ValidatedUrlRepository $urlRepository;
    private Messages $flash;
    private RouteParserInterface $router;
    private PhpRenderer $renderer;

    public function __construct(
        ValidatedUrlRepository $urlRepository,
        PhpRenderer $renderer,
        Messages $flash,
        RouteParserInterface $router
    ) {
        $this->urlRepository = $urlRepository;
        $this->flash = $flash;
        $this->renderer = $renderer;
        $this->router = $router;
    }

    public function __invoke(
        ServerRequest $request,
        SlimResponseInterface $response,
    ): ?PsrResponseInterface {
        try {
            ['name' => $urlName] = $request->getParsedBodyParam("url");
            $urlInfo = ['name' => htmlspecialchars(
                is_string($urlName) ? $urlName : ''
            )];

            $url = Url::fromArray($urlInfo);
            $this->urlRepository->save($url);

            if ($this->urlRepository->isValid()) {
                $this->flash->addMessage(
                    'success',
                    $this->urlRepository->getMessage()
                );

                $toUrlInfo = $this->router->urlFor(
                    'urlInfo',
                    ['id' => "{$url->getId()}"]
                );
                return $response->withRedirect($toUrlInfo);
            }

            if ($url->exists()) {
                $this->flash->addMessage(
                    'error',
                    $this->urlRepository->getMessage()
                );

                $toUrlInfo = $this->router->urlFor(
                    'urlInfo',
                    ['id' => "{$url->getId()}"]
                );
                $response = $response->withStatus(422);

                return $response->withRedirect($toUrlInfo);
            }

            $params = [
                'messages' => ['error' => [$this->urlRepository->getMessage()]],
                'errors' => ['url' => ['name' => $url->getUrl()]]
            ];
            $response = $response->withStatus(422);

            return $this->renderer->render(
                $response,
                'index.phtml',
                $params
            );
        } catch (AppExceptionInterface $exception) {
            $data = file_get_contents(__DIR__ . "/../Exceptions/errorCodesInfo.json");
            $errorCodesInfo = json_decode(
                $data ?: '', flags:JSON_OBJECT_AS_ARRAY
            );
            $errorCode = strval($exception->getErrorCode());            
            $debugMessage = $errorCodesInfo[$errorCode];

            if (
                $exception instanceof UrlException ||
                $exception instanceof UrlRepositoryException
            ) {
                throw new UrlsCreateActionException(
                    $debugMessage ?: "Неизвестная ошибка",
                    intval(mb_substr($errorCode, 0, 3)),
                    $exception
                );
            }

            return parrent::respond();
        }
    }
}
