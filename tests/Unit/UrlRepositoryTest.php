<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository};
use PDO;
use Exception;

#[CoversClass(Url::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
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
    private \PDO $conn;

    public function setUp(): void
    {
        $databaseUrl = getenv('DATABASE_URL');
        $databaseInfo = parse_url(
            htmlspecialchars(
                $databaseUrl ? $databaseUrl : ''
            )
        );

        $dbScheme = $databaseInfo['scheme'] ?? '';
        $dbPort = $databaseInfo['port'] ?? '';
        $dbHost = $databaseInfo['host'] ?? '';
        $dbParsedPath = $databaseInfo['path'] ?? '';
        $dbPath = ltrim($dbParsedPath, '/');
        $dbUser = $databaseInfo['user'] ?? '';
        $dbPasswd = $databaseInfo['pass'] ?? '';

        $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbPath};user={$dbUser};password={$dbPasswd}";
        $this->conn = new PDO($dsn);
        $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function testCreate(): void
    {
        $sqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($sqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->conn, $isTest),
            $isTest
        );
        $urlRepository->save($url);
        $id = $url->getId();
        $urlTemp = $urlRepository->find($id);

        $sqlStop = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($sqlStop);

        $this->assertTrue($urlTemp->exists());
        $this->assertEquals($urlInfo['mail']['name'], $urlTemp->getUrl());
    }

    public function testCreateException(): void
    {
        $sql = "FALSE REQUEST";
        $stmt = $this->conn->prepare($sql);
        $stmtStub = $this->createConfiguredStub(
            $stmt::class,
            [
                'bindParam' => true,
                'execute' => true
            ]
        );

        $connStub = $this->createConfiguredStub(
            $this->conn::class,
            [
                'prepare' => $stmtStub,
                'lastInsertId' => false
            ]
        );

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($connStub, $isTest),
            $isTest
        );

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $this->expectException(
            Exception::class
        );

        $urlRepository->save($url);
    }

    public function testUpdate(): void
    {
        $sqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($sqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->conn, $isTest),
            $isTest
        );
        $urlRepository->save($url);
        $id = $url->getId();
        $urlTemp = $urlRepository->find($id);

        $urlTemp->setUrl($urlInfo['yandex']['name']);
        $urlRepository->save($urlTemp);
        $url = $urlRepository->find($id);

        $sqlStop = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($sqlStop);

        $this->assertEquals(
            $urlInfo['yandex']['name'],
            $url->getUrl()
        );
    }

    public function testUnique(): void
    {
        $sqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($sqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $sameUrl = Url::fromArray($urlInfo['mail']);

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->conn, $isTest),
            $isTest
        );
        $urlRepository->save($url);
        $id = $url->getId();

        $urlRepository->save($sameUrl);
        $sameId = $url->getId();

        $sqlStop = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($sqlStop);

        $this->assertEquals($id, $sameId);
    }

    public function testDelete(): void
    {
        $sqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($sqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->conn, $isTest),
            $isTest
        );
        $urlRepository->save($url);
        $id = $url->getId();

        $urlFound = $urlRepository->find($id);
        $urlRepository->delete($id);
        $urlDeleted = $urlRepository->find($id);

        $sqlStop = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($sqlStop);

        $this->assertTrue($urlFound->exists() && is_null($urlDeleted));
    }

    public function testGetEntities(): void
    {
        $sqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($sqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);

        $isTest = true;
        $urlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->conn, $isTest),
            $isTest
        );

        $urlRepository->save($url);

        $entities = $urlRepository->getEntities();

        $sqlStop = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($sqlStop);

        $this->assertEquals(
            $urlInfo['mail']['name'],
            $entities[0]->getUrl()
        );
    }
}
