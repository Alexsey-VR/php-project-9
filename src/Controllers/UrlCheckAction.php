<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Interfaces\{UrlInterface, UrlCheckInterface};
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Interfaces\AppExceptionInterface;
use Analyzer\Exceptions\UrlCheckActionException;
use Analyzer\Exceptions\{UrlException, UrlCheckException};
use Analyzer\Exceptions\UrlCheckRepositoryException;
use GuzzleHttp\Exception\{ConnectException, RequestException};

class UrlCheckAction
{
    private ValidatedUrlRepository $urlRepository;
    private UrlCheckRepository $urlCheckRepository;
    private RouteParserInterface $router;
    private Messages $flash;
    private ?UrlInterface $url;
    private UrlCheckInterface $urlCheck;

    public function __construct(
        ValidatedUrlRepository $urlRepository,
        UrlCheckRepository $urlCheckRepository,
        Messages $flash,
        RouteParserInterface $router
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlCheckRepository = $urlCheckRepository;
        $this->flash = $flash;
        $this->router = $router;
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
            $id = intval(
                is_string($args['id']) ? $args['id'] : null
            );

            $this->url = $this->urlRepository->find($id);

            $this->urlCheck = UrlCheck::fromUrl(
                ($this->url instanceof UrlInterface) ?
                    $this->url : throw new UrlException(50004)
            );

            $this->urlCheck->execute();
            $this->urlCheckRepository->save($this->urlCheck);

            $timestamp = $this->urlCheck->getTimestamp();

            $this->url->setTimestamp(
                is_string($timestamp) ?
                    $timestamp : throw new UrlException(50002)
            );
            $this->urlRepository->save($this->url);

            $toUrlInfo = $this->router->urlFor('urlInfo', ['id' => "{$this->url->getId()}"]);

            $this->flash->addMessage("success", "Страница успешно проверена");

            return $response->withRedirect($toUrlInfo);
        } catch (ConnectException | RequestException $e) {
            $this->flash->addMessage("error", "Произошла ошибка при проверке, не удалось подключиться");

            $toUrlInfo = $this->router->urlFor('urlInfo', ['id' => "{$this->url->getId()}"]);
            return $response->withRedirect($toUrlInfo);
        } catch (AppExceptionInterface $exception) {
            $data = file_get_contents(__DIR__ . "/../Exceptions/errorCodesInfo.json");
            $errorCodesInfo = json_decode(
                $data ?: '',
                flags:JSON_OBJECT_AS_ARRAY
            );
            $errorCode = strval($exception->getErrorCode());

            $debugMessage = ($exception instanceof UrlException ||
                $exception instanceof UrlCheckException ||
                $exception instanceof UrlCheckRepositoryException) ?
                $errorCodesInfo[$errorCode] : "Неизвестная ошибка";

            throw new UrlCheckActionException(
                $debugMessage,
                intval(mb_substr($errorCode, 0, 3)),
                $exception
            );
        }
    }
}
