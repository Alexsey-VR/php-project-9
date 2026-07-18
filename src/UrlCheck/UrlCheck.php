<?php

namespace Analyzer\UrlCheck;

use Analyzer\Interfaces\{UrlInterface, UrlCheckInterface};
use Analyzer\Exceptions\UrlCheckException;
use GuzzleHttp\Client as Client;
use Symfony\Component\DomCrawler\Crawler;

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

    private const int STORE_LEN = 200;
    private const string STRING_POSTFIX = "...";
    private const float CONNECTION_TIMEOUT_S = 3.0;

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

        $urlId = is_int($urlId) ? $urlId : throw new UrlCheckException(50001);
        $urlCheck->setUrlId($urlId);

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

        $urlId = is_int($urlId = $url->getId()) ? $urlId : throw new UrlCheckException(50001);
        $urlCheck->setUrlId($urlId);

        return $urlCheck;
    }

    public function execute(): bool
    {
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

        return mb_convert_encoding($subInfo, 'UTF-8', 'UTF-8');
    }
}
