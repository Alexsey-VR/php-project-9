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
    private string $template;

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
                'timestamp' => (count($urlChecks) > 0) ? $urlChecks[0]->getTimestamp() : '',
                'status' => (count($urlChecks) > 0) ? $urlChecks[0]->getStatus() : ''
            ];
        }

        $messages = $this->flash->getMessages();
        $params = [
            'urls' => $urlItems,
            'messages' => $messages
        ];

        return $this->renderer->render(
            $response,
            $this->template,
            $params
        );
    }

    public function setRenderer(PhpRenderer $renderer): UrlsReadAction
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function setTemplate(string $template): UrlsReadAction
    {
        $this->template = $template;

        return $this;
    }
}
