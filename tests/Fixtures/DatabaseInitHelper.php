<?php

namespace Analyzer\Tests\Fixtures;

class DatabaseInitHelper
{
    /**
     * @return array<int, string>
     */
    public static function getSQLCommands(string $sqlData): array
    {
        return array_filter(
            array_map(
                fn ($item) => mb_ereg_replace("\n", "", $item),
                explode(';', $sqlData)
            ),
            fn ($item) => $item !== "" && $item !== null && $item !== false
        );
    }
}
