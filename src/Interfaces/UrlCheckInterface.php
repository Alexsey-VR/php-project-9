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
    public function setUrlId(int $id): void;
    public function getUrlId(): ?int;
    public function setStatus(int $status): void;
    public function getStatus(): ?int;
    public function setH1(string $h1): void;
    public function getH1(): ?string;
    public function setTitle(string $title): void;
    public function getTitle(): ?string;
    public function setDescription(string $description): void;
    public function getDescription(): ?string;
    public function setTimestamp(string $timestamp): void;
    public function getTimestamp(): ?string;
    public function exists(): bool;
    public function normalize(string $info): string;
    public function execute(): bool;
    public function getMessage(): string;
}
