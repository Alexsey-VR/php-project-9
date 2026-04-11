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
            $exceptionFragment = '<p>The application could not run because of the following error:</p>';
            $exceptionFragment .= '<h2>Details</h2>';
            $exceptionFragment .= $this->renderExceptionFragment($exception);
        } else {
            $exceptionFragment = "<p>{$this->getErrorDescription($exception)}</p>";
        }

        $params = [
            'exceptionFragment' => $exceptionFragment
        ];

        return $this->renderer->fetch('/Exceptions/urlException.phtml', $params);
    }

    private function renderExceptionFragment(Throwable $exception): string
    {
        $html = sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));

        $code = $exception->getCode();
        $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);

        $html .= sprintf('<div><strong>Message:</strong> %s</div>', htmlentities($exception->getMessage()));

        $html .= sprintf('<div><strong>File:</strong> %s</div>', $exception->getFile());

        $html .= sprintf('<div><strong>Line:</strong> %s</div>', $exception->getLine());

        $html .= '<h2>Trace</h2>';
        $html .= sprintf('<pre>%s</pre>', htmlentities($exception->getTraceAsString()));

        return $html;
    }

    public function setRenderer(PhpRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }
}
