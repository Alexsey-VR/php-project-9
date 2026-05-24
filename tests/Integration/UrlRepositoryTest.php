<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository};
use PDO;
use PDOStatement;
use Analyzer\Exceptions\UrlException;
use Analyzer\Tests\Fixtures\DatabaseInitHelper;

#[CoversClass(Url::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlException::class)]
#[CoversMethod(UrlRepository::class, 'create')]
#[CoversMethod(UrlRepository::class, 'update')]
#[CoversMethod(UrlRepository::class, 'save')]
#[CoversMethod(UrlRepository::class, 'find')]
#[CoversMethod(UrlRepository::class, 'delete')]
#[CoversMethod(UrlRepository::class, 'getEntities')]
#[CoversMethod(ValidatedUrlRepository::class, 'create')]
#[CoversMethod(ValidatedUrlRepository::class, 'update')]
#[CoversMethod(ValidatedUrlRepository::class, 'save')]
#[CoversMethod(ValidatedUrlRepository::class, 'find')]
#[CoversMethod(ValidatedUrlRepository::class, 'delete')]
#[CoversMethod(ValidatedUrlRepository::class, 'getEntities')]
class UrlRepositoryTest extends TestCase
{
    private \PDO $connection;

    private const string PDO_ERROR_FOR_ID = "PDO error: can't get a url check id";

    protected function setUp(): void
    {
        parent::setUp();
        $databaseUrl = getenv('DATABASE_URL');
        $databaseInfo = parse_url(
            htmlspecialchars(
                $databaseUrl ?: ''
            )
        );

        $dbPort = $databaseInfo['port'] ?? '';
        $dbHost = $databaseInfo['host'] ?? '';
        $dbParsedPath = $databaseInfo['path'] ?? '';
        $dbPath = ltrim($dbParsedPath, '/');
        $dbUser = $databaseInfo['user'] ?? '';
        $dbPasswd = $databaseInfo['pass'] ?? '';

        $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbPath};user={$dbUser};password={$dbPasswd}";
        $this->connection = new PDO($dsn);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testCreate(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );
        $urlRepository->save($url);
        $id = $url->getId();
        $urlTemp = $urlRepository->find(
            is_int($id) ? $id : throw new UrlException(self::PDO_ERROR_FOR_ID)
        );

        $this->assertTrue(isset($urlTemp) ? $urlTemp->exists() : false);
        $this->assertEquals($urlInfo['mail']['name'], $urlTemp->getUrl());
    }

    public function testCreateException(): void
    {
        $stmtStub = $this->createMock(PDOStatement::class);
        $stmtStub->method('bindParam')->willReturn(true);
        $stmtStub->method('execute')->willReturn(true);

        $connStub = $this->createConfiguredStub(
            $this->connection::class,
            [
                'prepare' => $stmtStub,
                'lastInsertId' => false
            ]
        );

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($connStub),
            $connStub
        );

        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository->save($url);

        $this->assertEquals($urlRepository->getMessage(), "PDO error: can't get last insert id");
    }

    public function testUpdate(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );
        $urlRepository->save($url);

        $id = $url->getId();
        $urlTemp = $urlRepository->find(
            is_int($id) ? $id : throw new UrlException(self::PDO_ERROR_FOR_ID)
        );

        if ($urlTemp instanceof Url) {
            $urlTemp->setUrl($urlInfo['yandex']['name']);
            $urlRepository->save($urlTemp);
        }
        $url = $urlRepository->find($id);

        $this->assertEquals(
            $urlInfo['yandex']['name'],
            ($url instanceof Url) ? $url->getUrl() : ''
        );
    }

    public function testUnique(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $sameUrl = Url::fromArray($urlInfo['mail']);

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );
        $urlRepository->save($url);
        $id = $url->getId();

        $urlRepository->save($sameUrl);
        $sameId = $url->getId();

        $this->assertEquals($id, $sameId);
        $this->assertEquals(
            $urlRepository->getMessage(),
            "Страница уже существует"
        );
    }

    public function testDelete(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );
        $urlRepository->save($url);
        $id = $url->getId();

        $urlFound = $urlRepository->find(
            is_int($id) ? $id : throw new UrlException(self::PDO_ERROR_FOR_ID)
        );
        $urlRepository->delete($id);
        $urlDeleted = $urlRepository->find($id);

        $this->assertTrue(
            (isset($urlFound) ? $urlFound->exists() : false) &&
            is_null($urlDeleted)
        );
    }

    public function testGetEntities(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlRepository->save($url);

        $entities = $urlRepository->getEntities();

        $this->assertEquals(
            $urlInfo['mail']['name'],
            $entities[0]->getUrl()
        );
    }
}
