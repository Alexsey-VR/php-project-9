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
    ): PsrResponseInterface {
        $url = Url::fromArray($request->getParsedBodyParam("url"));
        $this->urlRepository->save($url);

        $isValid = $this->urlRepository->isValid();
        $message = $this->urlRepository->getMessage();
        if ($isValid || $url->exists()) {
            $this->flash->addMessage('success', $message);

            $toUrlInfo = $this->router->urlFor(
                'url.show',
                ['id' => "{$url->getId()}"]
            );
            return $response->withRedirect($toUrlInfo);
        }

        $params = [
            'title' => "Сервис для проверки сайтов на SEO пригодность",
            'messages' => ['error' => [$message]],
            'errors' => ['url' => ['name' => $url->getUrl()]]
        ];
        $response = $response->withStatus(422);

        return $this->renderer->render(
            $response,
            'index.phtml',
            $params
        );
    }
}
