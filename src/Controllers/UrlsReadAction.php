<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Exceptions\UrlException;

class UrlsReadAction
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

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): PsrResponseInterface {
        $urls = $this->urlRepository->getEntities();
        $urlItems = [];
        foreach ($urls as $url) {
            $id = $url->getId();
            $urlChecks = $this->urlCheckRepository->getEntitiesByUrlId(
                is_int($id) ? $id : throw new UrlException("PDO error: can't get url ID")
            );
            $urlItems[] = [
                'id' => $id,
                'name' => $url->getUrl(),
                'timestamp' => !empty($urlChecks) ? $urlChecks[0]->getTimestamp() : '',
                'status' => !empty($urlChecks) ? $urlChecks[0]->getStatus() : ''
            ];
        }

        $messages = $this->flash->getMessages();
        $params = [
            'urls' => $urlItems,
            'messages' => $messages
        ];

        return $this->renderer->render(
            $response,
            'Urls/urls.phtml',
            $params
        );
    }
}
