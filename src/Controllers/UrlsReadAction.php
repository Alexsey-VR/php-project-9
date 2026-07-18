<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Exceptions\UrlCheckRepositoryException;

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
        $checkEntities = $this->urlCheckRepository->getLastEntities();
        foreach ($urls as $url) {
            $id = $url->getId() ?? throw new UrlCheckRepositoryException(50001);
            $currentCheck = null;
            foreach ($checkEntities as $check) {
                if ($check->getUrlId() === $id) {
                    $currentCheck = $check;
                } else {
                    continue;
                }
            }
            $urlItems[] = [
                'id' => $id,
                'name' => $url->getUrl(),
                'timestamp' => !is_null($currentCheck) ? $currentCheck->getTimestamp() : '',
                'status' => !is_null($currentCheck) ? $currentCheck->getStatus() : ''
            ];
        }

        $messages = $this->flash->getMessages();
        $params = [
            'title' => "Список сайтов",
            'urls' => $urlItems,
            'messages' => $messages
        ];

        return $this->renderer->render(
            $response,
            'urls/urls.phtml',
            $params
        );
    }
}
