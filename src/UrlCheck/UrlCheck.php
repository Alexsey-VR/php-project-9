<?php

namespace Analyzer\UrlCheck;

use Analyzer\Interfaces\{UrlInterface, UrlCheckInterface};
use Analyzer\Exceptions\UrlException;
use GuzzleHttp\Client as Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

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
    private const string STRING_POSTFIX = "...";
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
            is_int($urlId) ? $urlId : throw new UrlException('Internal error: URL ID has a wrong type')
        );

        $urlCheck->setCheckInfo($status, $h1, $title, $description);

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
            is_int($urlId) ? $urlId : throw new UrlException('Internal error: URL ID has a wrong type')
        );

        return $urlCheck;
    }

    public function execute(): bool
    {
        $status = 200;
        $h1 = "";
        $title = "";
        $description = "";
        try {
            $response = $this->client->request('GET');
            $status = $response->getStatusCode();
            $bodyContent = $response->getBody()->getContents();
            $crawler = new Crawler();
            $crawler->addHTMLContent($bodyContent, 'UTF-8');

            $h1 = $crawler->filterXPath("//h1")->text('', false);
            $title = $crawler->filterXPath("//title")->text('', false);
            $description = "";
            $content = $crawler->filterXPath('//meta[contains(@name, "description")]')->evaluate('@content');
            $description = ($content instanceof Crawler) ? $content->text('', false) : '';

            $this->message = self::SUCCESS_MESSAGE;
        } catch (ConnectException | RequestException $e) {
            $this->message = self::ERROR_MESSAGE;

            return false;
        };

        $this->setCheckInfo($status, $h1, $title, $description);

        return true;
    }

    public function setCheckInfo(
        int $status,
        string $h1,
        string $title,
        string $description
    ): void {
        $this->status = $status;
        $this->h1 = $this->normalize($h1);
        $this->title = $this->normalize($title);
        $this->description = $this->normalize($description);
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

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getH1(): ?string
    {
        return $this->h1;
    }

    public function getTitle(): ?string
    {
        return $this->title;
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
        if (mb_strlen($info) > self::STORE_LEN) {
            $subInfo = mb_substr($info, 0, self::STORE_LEN);
            $postfix = self::STRING_POSTFIX;
            return "{$subInfo}{$postfix}";
        }

        $subInfoUTF8 = mb_convert_encoding($subInfo, 'UTF-8', 'UTF-8');

        return $subInfoUTF8;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
