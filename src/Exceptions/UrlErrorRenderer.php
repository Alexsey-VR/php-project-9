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
        if ($displayErrorDetails) {
            $type = get_class($exception);
            $code = $exception->getCode();
            $file = $exception->getFile();
            $line = $exception->getLine();
            $trace = htmlentities($exception->getTraceAsString());
        }

        $params = [
            'details' => $displayErrorDetails,
            'message' => "Ошибка уже в обработке. Приносим извинения за неудобства.",
        ];
        if ($displayErrorDetails) {
            $debugMessage = htmlentities($exception->getMessage());
            $params = [
                'details' => $displayErrorDetails,
                'type' => $type,
                'code' => $code,
                'message' => $debugMessage,
                'file' => $file,
                'line' => $line,
                'trace' => $trace
            ];
        }

        return $this->renderer->fetch('/Exceptions/urlException.phtml', $params);
    }

    public function setRenderer(PhpRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }
}
