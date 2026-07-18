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

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args
    ): PsrResponseInterface {
        $id = is_numeric($args['id']) ? intval($args['id']) : throw new UrlException(50001);

        $url = $this->urlRepository->find($id);
        if (is_null($url)) {
            $params = [
                'title' => "Ошибка",
                'details' => false,
                'code' => 404,
                'message' => 'Not found',
            ];

            return $this->renderer->render(
                $response->withStatus(404),
                'exceptions/urlException.phtml',
                $params
            );
        }

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
            'title' => "Информация о сайте",
            'name' => $url->getUrl(),
            'id' => $url->getId(),
            'timestamp' => $url->getTimestamp(),
            'messages' => $messages,
            'checks' => $checkItems
        ];

        return $this->renderer
            ->render(
                $response,
                'urls/url.phtml',
                $params
            );
    }
}
