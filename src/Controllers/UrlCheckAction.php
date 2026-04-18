<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Interfaces\RouteParserInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Interfaces\UrlInterface;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Exceptions\UrlException;

class UrlCheckAction
{
    private ValidatedUrlRepository $urlRepository;
    private UrlCheckRepository $urlCheckRepository;
    private RouteParserInterface $router;
    private Messages $flash;
    private string $routeName;

    public function __construct(
        ValidatedUrlRepository $urlRepository,
        UrlCheckRepository $urlCheckRepository,
        Messages $flash
    ) {
        $this->urlRepository = $urlRepository;
        $this->urlCheckRepository = $urlCheckRepository;
        $this->flash = $flash;
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): PsrResponseInterface {
        $id = intval(
            is_string($args['id']) ? $args['id'] : null
        );

        $url = $this->urlRepository->find($id);

        $urlCheck = UrlCheck::fromUrl(
            ($url instanceof UrlInterface) ?
                $url : throw new UrlException("Internal error: can't get a url interface on checks")
        );

        if ($urlCheck->execute()) {
            $this->urlCheckRepository->save($urlCheck);

            $timestamp = $urlCheck->getTimestamp();
            $url->setTimestamp(
                is_string($timestamp) ?
                    $timestamp : throw new UrlException("Internal error: can't get a timestamp on checks")
            );
            $this->urlRepository->save($url);

            $this->flash->addMessage('success', $urlCheck->getMessage());
        } else {
            $this->flash->addMessage('error', $urlCheck->getMessage());
        }

        $toUrlInfo = $this->router->urlFor($this->routeName, ['id' => "{$url->getId()}"]);

        return $response->withRedirect($toUrlInfo);
    }

    public function setRouter(
        RouteParserInterface $router
    ): UrlCheckAction {
        $this->router = $router;

        return $this;
    }

    public function setRouteName(
        string $routeName
    ): UrlCheckAction {
        $this->routeName = $routeName;

        return $this;
    }
}
