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

    public function __construct(
        Messages $flash,
        PhpRenderer $renderer
    ) {
        $this->flash = $flash;
        $this->renderer = $renderer;
    }

    public function __invoke(
        ServerRequestInterface $request,
        PsrResponseInterface $response,
    ): PsrResponseInterface {
        $messages = $this->flash->getMessages();

        $params = [
            'messages' => $messages,
            'errors' => []
        ];

        return $this->renderer->render($response, 'index.phtml', $params);
    }
}
