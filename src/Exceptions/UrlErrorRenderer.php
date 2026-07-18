<?php

namespace Analyzer\Exceptions;

use Slim\Error\AbstractErrorRenderer;
use Slim\Views\PhpRenderer;
use Throwable;

use function get_class;
use function htmlentities;

class UrlErrorRenderer extends AbstractErrorRenderer
{
    private PhpRenderer $renderer;

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $type = null;
        $code = 500;
        if ($displayErrorDetails) {
            $type = get_class($exception);
            $code = $exception->getCode();
        }

        $params = [
            'details' => $displayErrorDetails,
            'type' => $type,
            'code' => $code,
            'message' => "Ошибка уже в обработке. Приносим извинения за неудобства.",
        ];
        if ($displayErrorDetails) {
            $debugMessage = htmlentities($exception->getMessage());
            $params = [
                'details' => $displayErrorDetails,
                'code' => $code,
                'message' => $debugMessage
            ];
        }

        $content = $this->renderer->fetch('/exceptions/urlException.phtml', $params);
    
        return $this->renderer->fetch('layout.phtml', ['content' => $content, 'title' => 'Ошибка']);
    }

    public function setRenderer(PhpRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }
}
