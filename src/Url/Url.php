<?php

namespace Analyzer\Url;

use Analyzer\Interfaces\UrlInterface;

class Url implements UrlInterface
{
    private int|null $id;
    private string|null $url;
    private string|null $timestamp;

    public function __construct()
    {
        $this->id = null;
        $this->url = null;
        $this->timestamp = null;
    }

    public static function fromArray(array $urlInfo): UrlInterface
    {
        ['name' => $urlData] = $urlInfo;
        $url = new Url();

        $url->setUrl(
            is_string($urlData) ? $urlData : ''
        );
        

        return $url;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp(int $id): ?string
    {
        return $this->timestamp;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
