<?php

namespace Analyzer\Controllers;

use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class MainAction
{
    private Messages $flash;
    private PhpRenderer $renderer;
    private string $template;

    public function __construct(Messages $flash)
    {
        $this->flash = $flash;
    }

    /**
     * @param array<string,mixed> $args
     */
    public function __invoke(
        ServerRequestInterface $request,
        PsrResponseInterface $response,
        array $args
    ): PsrResponseInterface {
        $messages = $this->flash->getMessages();

        $params = [
            'messages' => $messages,
            'errors' => []
        ];

        return $this->renderer->render($response, $this->template, $params);
    }

    public function setTemplate(string $template): MainAction
    {
        $this->template = $template;

        return $this;
    }

    public function setRenderer(PhpRenderer $renderer): MainAction
    {
        $this->renderer = $renderer;

        return $this;
    }
}
