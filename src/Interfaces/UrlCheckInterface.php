<?php

namespace Analyzer\Interfaces;

use Analyzer\Interfaces\UrlInterface;

interface UrlCheckInterface
{
    /**
     * @param array<mixed> $urlCheckInfo
     */
    public static function fromArray(array $urlCheckInfo): UrlCheckInterface;
    public static function fromUrl(UrlInterface $url): UrlCheckInterface;
    public function setId(int $id): void;
    public function getId(): ?int;
    public function setCheckInfo(
        int $status,
        string $h1,
        string $title,
        string $description
    ): void;
    public function setUrlId(int $id): void;
    public function getUrlId(): ?int;
    public function getStatus(): ?int;
    public function getH1(): ?string;
    public function getTitle(): ?string;
    public function getDescription(): ?string;
    public function setTimestamp(string $timestamp): void;
    public function getTimestamp(): ?string;
    public function exists(): bool;
    public function normalize(string $info): string;
    public function execute(): bool;
}
