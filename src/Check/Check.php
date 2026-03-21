<?php

namespace Analyzer\Check;

use Analyzer\Interfaces\CheckInterface;
use Exception;

class Check implements CheckInterface
{
    private int|null $id;
    private int|null $urlId;
    private int|null $status;
    private string|null $h1;
    private string|null $title;
    private string|null $description;
    private string|null $timestamp;

    public function __construct()
    {
        $this->id = null;
        $this->urlId = null;
        $this->status = null;
        $this->h1 = null;
        $this->title = null;
        $this->description = null;
        $this->timestamp = null;
    }

    public static function fromArray(array $checkInfo): CheckInterface
    {
        [
            'url_id' => $urlId,
            'status' => $status,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ] = $checkInfo;
        $check = new Check();

        $check->setUrlId(
            is_int($urlId) ? $urlId : throw new Exception('Internal error: URL ID has a wrong type')
        );
        $check->setStatus(
            is_int($status) ? $status : throw new Exception('Internal error: check status has a wrong type')
        );
        $check->setH1(
            is_string($h1) ? $h1 : throw new Exception('Internal error: h1 has a wrong type')
        );
        $check->setTitle(
            is_string($title) ? $title : throw new Exception('Internal error: title has a wrong type')
        );
        $check->setDescription(
            is_string($description) ? $description
            : throw new Exception('Internal error: description has a wrong type')
        );

        return $check;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setUrlId(int $urlId): void
    {
        $this->urlId = $urlId;
    }

    public function getUrlId(): ?int
    {
        return $this->urlId;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setH1(string $h1): void
    {
        $this->h1 = $h1;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setTimestamp(string $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }

    public function exists(): bool
    {
        return !is_null($this->getId());
    }
}
