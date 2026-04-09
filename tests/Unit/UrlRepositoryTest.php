<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository};
use PDO;
use Analyzer\Exceptions\UrlException;

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
    private \PDO $conn;
    private const string PDO_ERROR_FOR_ID = "PDO error: can't get a url check id";
    private const string INTERNAL_ERROR_QUERY = "Internal error: can't get query from file";

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
        if ($sqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($sqlInit);
        }

        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $isTest = true;
            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($this->conn, $isTest),
                $isTest
            );
            $urlRepository->save($url);
            $id = $url->getId();
            $urlTemp = $urlRepository->find(
                is_int($id) ? $id : throw new Exception(self::PDO_ERROR_FOR_ID)
            );
        }

        if ($sqlStop = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($sqlStop);
        }

        if (
            isset($urlTemp) &&
            isset($urlInfo)
        ) {
            $this->assertTrue($urlTemp->exists());
            $this->assertEquals($urlInfo['mail']['name'], $urlTemp->getUrl());
        }
    }

    public function testCreateException(): void
    {
        $sql = "FALSE REQUEST";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $builder = $this->getMockBuilder($stmt::class);
            $stmtStub = $builder->getMock();
            $stmtStub->method('bindParam')->willReturn(true);
            $stmtStub->method('execute')->willReturn(true);

            $connStub = $this->createConfiguredStub(
                $this->conn::class,
                [
                    'prepare' => $stmtStub,
                    'lastInsertId' => false
                ]
            );
        }

        $isTest = true;
        if (isset($connStub)) {
            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($connStub, $isTest),
                $isTest
            );
        }

        if (
            isset($urlRepository) &&
            $urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")
        ) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $urlRepository->save($url);
        }

        if (isset($urlRepository)) {
            $this->assertEquals($urlRepository->getMessage(), "PDO error: can't get last insert id");
        }
    }

    public function testUpdate(): void
    {
        if ($sqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($sqlInit);
        }

        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $isTest = true;
            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($this->conn, $isTest),
                $isTest
            );
            $urlRepository->save($url);
        }

        if (isset($url) && isset($urlRepository)) {
            $id = $url->getId();
            $urlTemp = $urlRepository->find(
                is_int($id) ? $id : throw new Exception(self::PDO_ERROR_FOR_ID)
            );
        }
        if (isset($urlTemp) && isset($urlInfo) && isset($urlRepository) && isset($id)) {
            $urlTemp->setUrl($urlInfo['yandex']['name']);
            $urlRepository->save($urlTemp);
            $url = $urlRepository->find($id);
        }
        if ($sqlStop = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($sqlStop);
        }

        if (isset($urlInfo) && isset($url)) {
            $this->assertEquals(
                $urlInfo['yandex']['name'],
                $url->getUrl()
            );
        }
    }

    public function testUnique(): void
    {
        if ($sqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($sqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);
            $sameUrl = Url::fromArray($urlInfo['mail']);

            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($this->conn, $isTest),
                $isTest
            );
            $urlRepository->save($url);
            $id = $url->getId();
        }

        if (isset($url) && isset($sameUrl) && isset($urlRepository)) {
            $urlRepository->save($sameUrl);
            $sameId = $url->getId();
        }

        if ($sqlStop = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($sqlStop);
        }

        if (isset($id) && isset($sameId) && isset($urlRepository)) {
            $this->assertEquals($id, $sameId);
            $this->assertEquals(
                $urlRepository->getMessage(),
                "Страница уже существует"
            );
        }
    }

    public function testDelete(): void
    {
        if ($sqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($sqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($this->conn, $isTest),
                $isTest
            );
            $urlRepository->save($url);
            $id = $url->getId();
        }

        if (isset($urlRepository) && isset($id)) {
            $urlFound = $urlRepository->find($id);
            $urlRepository->delete($id);
            $urlDeleted = $urlRepository->find($id);

            $sqlStop = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql");
            $this->conn->query(
                is_string($sqlStop) ? $sqlStop : throw new Exception(self::INTERNAL_ERROR_QUERY)
            );

            $this->assertTrue(
                (isset($urlFound) ? $urlFound->exists() : false) &&
                is_null($urlDeleted)
            );
        }
    }

    public function testGetEntities(): void
    {
        if ($sqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($sqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
            $url = Url::fromArray($urlInfo['mail']);

            $urlRepository = new ValidatedUrlRepository(
                new UrlRepository($this->conn, $isTest),
                $isTest
            );

            $urlRepository->save($url);
        }

        if (isset($urlRepository)) {
            $entities = $urlRepository->getEntities();
        }

        if ($sqlStop = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($sqlStop);
        }

        if (isset($urlInfo) && isset($entities)) {
            $this->assertEquals(
                $urlInfo['mail']['name'],
                $entities[0]->getUrl()
            );
        }
    }
}
