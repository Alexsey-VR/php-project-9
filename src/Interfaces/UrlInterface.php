<?php

namespace Analyzer\Interfaces;

interface UrlInterface
{
    /**
     * @param array<mixed> $urlInfo
     */
    public static function fromArray(array $urlInfo): UrlInterface;
    public function setId(int $id): void;
    public function getId(): ?int;
    public function setUrl(string $url): void;
    public function getUrl(): ?string;
    public function setTimestamp(string $timestamp): void;
    public function getTimestamp(int $id): ?string;
    public function exists(): bool;
}