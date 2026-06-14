<?php

namespace Analyzer\Interfaces;

use Throwable;

interface AppExceptionInterface extends Throwable
{
    public function getErrorCode(): int;
}
