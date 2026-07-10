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

    private const array ERROR_CODES_INFO = [
        "50001" => "URL ID имеет не корректный тип данных",
        "50002" => "URL Timestamp имеет не корректный тип данных",
        "50003" => "PDO не возвращает ID последнего сохранённого элемента",
        "50004" => "Не возможно получить объект UrlInterface в проверке"
    ];

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($displayErrorDetails) {
            $type = get_class($exception);
            $code = $exception->getCode();
        }

        $params = [
            'details' => $displayErrorDetails,
            'message' => "Ошибка уже в обработке. Приносим извинения за неудобства.",
        ];
        if ($displayErrorDetails) {
            $debugMessage = htmlentities($exception->getMessage());
            $params = [
                'details' => $displayErrorDetails,
                'code' => intval(mb_substr($code, 0, 3)),
                'message' => self::ERROR_CODES_INFO[$code] ?? 'неизвестная ошибка',
            ];
        }

        return $this->renderer->fetch('/Exceptions/urlException.phtml', $params);
    }

    public function setRenderer(PhpRenderer $renderer): void
    {
        $this->renderer = $renderer;
    }
}
