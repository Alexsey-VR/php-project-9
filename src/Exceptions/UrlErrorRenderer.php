<?php

namespace Analyzer\Exceptions;

use Slim\Error\AbstractErrorRenderer;
use Slim\Views\PhpRenderer;
use Throwable;

use function get_class;
use function htmlentities;
use function sprintf;

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
        $message = htmlentities($exception->getMessage());

        $params = [
            'details' => $displayErrorDetails,
            'type' => isset($type) ? $type : '',
            'code' => isset($code) ? $code : '',
            'message' => $message,
            'file' => isset($file) ? $file : '',
            'line' => isset($line) ? $line : '',
            'trace' => isset($trace) ? $trace : ''
        ];

        return $this->renderer->fetch('/Exceptions/urlException.phtml', $params);
    }

    public function setRenderer(PhpRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }
}
