<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Interfaces\UrlInterface;
use Analyzer\Exceptions\UrlException;

class UrlReadAction
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
                    $this->template,
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
    }

    public function setTemplate(string $template): UrlReadAction
    {
        $this->template = $template;

        return $this;
    }

    public function setRenderer(PhpRenderer $renderer): UrlReadAction
    {
        $this->renderer = $renderer;

        return $this;
    }
}
