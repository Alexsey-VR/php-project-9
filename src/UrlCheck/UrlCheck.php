<?php

namespace Analyzer\UrlCheck;

use Analyzer\Interfaces\{UrlInterface, UrlCheckInterface};
use Exception;
use GuzzleHttp\Client as Client;

class UrlCheck implements UrlCheckInterface
{
    private Client $client;
    private int|null $id;
    private int|null $urlId;
    private int|null $status;
    private string|null $h1;
    private string|null $title;
    private string|null $description;
    private string|null $timestamp;
    private string $message;

    private const int STORE_LEN = 200;
    private const float CONNECTION_TIMEOUT_S = 2.0;
    private const string SUCCESS_MESSAGE = "Страница успешно проверена";
    private const string ERROR_MESSAGE = "Произошла ошибка при проверке, не удалось подключиться";

    public function __construct()
    {
        $this->id = null;
        $this->urlId = null;
        $this->status = null;
        $this->h1 = null;
        $this->title = null;
        $this->description = null;
        $this->timestamp = null;
        $this->message = self::SUCCESS_MESSAGE;
    }

    public static function fromArray(array $urlCheckInfo): UrlCheckInterface
    {
        [
            'url_id' => $urlId,
            'status' => $status,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ] = $urlCheckInfo;
        $urlCheck = new UrlCheck();

        $urlCheck->setUrlId(
            is_int($urlId) ? $urlId : throw new Exception('Internal error: URL ID has a wrong type')
        );
        $urlCheck->setStatus(
            is_int($status) ? $status : throw new Exception('Internal error: check status has a wrong type')
        );
        $urlCheck->setH1(
            is_string($h1) ? $h1 : throw new Exception('Internal error: h1 has a wrong type')
        );
        $urlCheck->setTitle(
            is_string($title) ? $title : throw new Exception('Internal error: title has a wrong type')
        );
        $urlCheck->setDescription(
            is_string($description) ? $description
            : throw new Exception('Internal error: description has a wrong type')
        );

        return $urlCheck;
    }

    public static function fromUrl(UrlInterface $url): UrlCheckInterface
    {
        $urlCheck = new UrlCheck();
        $urlCheck->client = new Client(
            [
                'base_uri' => $url->getUrl(),
                'timeout' => self::CONNECTION_TIMEOUT_S
            ]
        );

        $urlId = $url->getId();
        $urlCheck->setUrlId(
            is_int($urlId) ? $urlId : throw new Exception('Internal error: URL ID has a wrong type')
        );

        return $urlCheck;
    }

    public function execute(): bool
    {
        try {
            $status = $this->client->request('GET')->getStatusCode();
            $this->message = self::SUCCESS_MESSAGE;
        } catch (Exception $e) {
            $status = 0;
            $this->message = self::ERROR_MESSAGE;

            return false;
        };

        $this->setStatus($status);

        $this->setH1(
            $this->normalize("...")
        );
        $this->setTitle(
            $this->normalize("...")
        );
        $this->setDescription(
            $this->normalize("...")
        );

        return true;
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
        $this->h1 = $this->normalize($h1);
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function setTitle(string $title): void
    {
        $this->title = $this->normalize($title);
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setDescription(string $description): void
    {
        $this->description = $this->normalize($description);
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

    public function normalize(string $info): string
    {
        $subInfo = $info;
        if (strlen($info) > self::STORE_LEN) {
            $subInfo = substr($info, 0, self::STORE_LEN);
            return "{$subInfo}...";
        }
        return $subInfo;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
