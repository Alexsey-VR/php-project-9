<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\ServerRequest;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Url\Url;
use Analyzer\Exceptions\UrlException;

class UrlsCreateAction
{
    private ValidatedUrlRepository $urlRepository;
    private Messages $flash;
    private RouteParserInterface $router;
    private PhpRenderer $renderer;
    private string $template;
    private string $routeName;

    public function __construct(
        ValidatedUrlRepository $urlRepository,
        Messages $flash
    ) {
        $this->urlRepository = $urlRepository;
        $this->flash = $flash;
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(
        ServerRequest $request,
        SlimResponseInterface $response,
        array $args
    ): ?PsrResponseInterface {
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
                $this->routeName,
                ['id' => "{$url->getId()}"]
            );
            return $response->withRedirect($toUrlInfo);
        }

        $toMainPage = $this->router->urlFor('mainPage');
        if ($url->exists()) {
            $this->flash->addMessage(
                'error',
                $this->urlRepository->getMessage()
            );

            $toUrlInfo = $this->router->urlFor(
                $this->routeName,
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
            $this->template,
            $params
        );
    }

    public function setRenderer(PhpRenderer $renderer): UrlsCreateAction
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function setTemplate(string $template): UrlsCreateAction
    {
        $this->template = $template;

        return $this;
    }

    public function setRouter(
        RouteParserInterface $router
    ): UrlsCreateAction {
        $this->router = $router;

        return $this;
    }

    public function setRouteName(
        string $routeName
    ): UrlsCreateAction {
        $this->routeName = $routeName;

        return $this;
    }
}
