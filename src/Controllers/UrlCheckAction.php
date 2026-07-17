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
use Analyzer\Exceptions\UrlException;
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
            $id = is_numeric($args['id']) ? intval($args['id']) : throw new UrlException(50001);
            $this->url = $this->urlRepository->find($id);

            $url = ($this->url instanceof UrlInterface) ? $this->url : throw new UrlException(50004);
            $this->urlCheck = UrlCheck::fromUrl($url);

            $this->urlCheck->execute();
            $this->urlCheckRepository->save($this->urlCheck);

            $timestamp = $this->urlCheck->getTimestamp() ?? throw new UrlException(50002);

            $this->url->setTimestamp($timestamp);
            $this->urlRepository->save($this->url);

            $toUrlInfo = $this->router->urlFor('urls.url.show', ['id' => "{$this->url->getId()}"]);

            $this->flash->addMessage("success", "Страница успешно проверена");

            return $response->withRedirect($toUrlInfo);
        } catch (ConnectException | RequestException $e) {
            $this->flash->addMessage("error", "Произошла ошибка при проверке, не удалось подключиться");

            $toUrlInfo = $this->router->urlFor('urls.url.show', ['id' => "{$this->url->getId()}"]);
            return $response->withRedirect($toUrlInfo);
        }
    }
}
