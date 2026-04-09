<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use PHPUnit\Framework\MockObject\Stub;
use Analyzer\UrlCheck\UrlCheck as UrlCheck;
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, UrlCheckRepository};
use PDO;
use Analyzer\Exceptions\UrlException;

#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlException::class)]
#[CoversMethod(UrlCheckRepository::class, 'create')]
#[CoversMethod(UrlCheckRepository::class, 'update')]
#[CoversMethod(UrlCheckRepository::class, 'save')]
#[CoversMethod(UrlCheckRepository::class, 'find')]
#[CoversMethod(UrlCheckRepository::class, 'delete')]
#[CoversMethod(UrlCheckRepository::class, 'getEntities')]
class UrlCheckRepositoryTest extends TestCase
{
    private PDO $conn;
    private const string PDO_ERROR_FOR_ID = "PDO error: can't get a url check id";

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
        if ($urlSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($urlSqlInit);
        }

        if ($urlCheckSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryInit.sql")) {
            $this->conn->query($urlCheckSqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode($urlInfoData, flags:JSON_OBJECT_AS_ARRAY);

            $url = Url::fromArray(
                is_array($urlInfo) && is_array($urlInfo['mail']) ? $urlInfo['mail'] : []
            );
            $urlRepository = new UrlRepository($this->conn, $isTest);
            $urlRepository->save($url);
        }

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }

        if (isset($url) && isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first'])) {
            $urlCheckInfo['first']['urlId'] = intval($url->getId());
            $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

            $urlCheckRepository = new UrlCheckRepository($this->conn, $isTest);
            $urlCheckRepository->save($urlCheck);
            $id = intval($urlCheck->getId());
            $urlCheckTemp = $urlCheckRepository->find($id);
        }

        if ($urlCheckStopSql = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryStop.sql")) {
            $this->conn->query($urlCheckStopSql);
        }

        if ($urlStopSql = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($urlStopSql);
        }

        if (
            isset($urlCheckTemp) &&
            ($urlCheckTemp instanceof UrlCheck) &&
            isset($urlCheckInfo) &&
            is_array($urlCheckInfo) &&
            is_array($urlCheckInfo['first'])
        ) {
            $this->assertTrue($urlCheckTemp->exists());
            $this->assertEquals($urlCheckInfo['first']['status'], $urlCheckTemp->getStatus());
        }
    }

    public function testCreateException(): void
    {
        $sql = "FALSE REQUEST";
        $stmt = $this->conn->prepare($sql);

        $isTest = true;
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

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);

            $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

            $this->expectException(
                UrlException::class
            );

            $urlCheckRepository = new UrlCheckRepository(
                isset($connStub) ? $connStub : throw new UrlException("Internal error: can't get a mock"),
                $isTest
            );
            $urlCheckRepository->save($urlCheck);
        }
    }

    public function testUpdate(): void
    {
        if ($urlSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($urlSqlInit);
        }
        if ($urlCheckSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryInit.sql")) {
            $this->conn->query($urlCheckSqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode($urlInfoData, flags:JSON_OBJECT_AS_ARRAY);

            $url = Url::fromArray($urlInfo['mail']);

            $urlRepository = new UrlRepository($this->conn, $isTest);
            $urlRepository->save($url);
        }

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }
        if (isset($urlCheckInfo) && is_array($urlCheckInfo['first']) && isset($url)) {
            $urlCheckInfo['first']['url_id'] = intval($url->getId());

            $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

            $urlCheckRepository = new UrlCheckRepository($this->conn, $isTest);
            $urlCheckRepository->save($urlCheck);
            $id = $urlCheck->getId();
            $urlCheckTemp = $urlCheckRepository->find(
                is_int($id) ? $id : throw new UrlException(self::PDO_ERROR_FOR_ID)
            );
        }

        if (
            isset($urlCheckInfo) &&
            isset($urlCheckTemp) &&
            ($urlCheckTemp instanceof UrlCheck) &&
            isset($urlCheckRepository) &&
            isset($id)
        ) {
            $urlCheckTemp->setDescription($urlCheckInfo['first']['description']);
            $urlCheckRepository->save($urlCheckTemp);
            $urlCheck = $urlCheckRepository->find($id);
        }

        if ($urlCheckStopSql = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryStop.sql")) {
            $this->conn->query($urlCheckStopSql);
        }
        if ($urlStopSql = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($urlStopSql);
        }

        if (isset($urlCheckInfo)) {
            $this->assertEquals(
                is_string($urlCheckInfo['first']['description']) ? $urlCheckInfo['first']['description'] : "",
                isset($urlCheck) ? $urlCheck->getDescription() : ""
            );
        }
    }

    public function testDelete(): void
    {
        if ($urlSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($urlSqlInit);
        }
        if ($urlCheckSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryInit.sql")) {
            $this->conn->query($urlCheckSqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode($urlInfoData, flags:JSON_OBJECT_AS_ARRAY);

            $url = Url::fromArray($urlInfo['mail']);
            $urlRepository = new UrlRepository($this->conn, $isTest);
            $urlRepository->save($url);
        }

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }

        $urlCheckDeleted = null;
        if (isset($url) && isset($urlCheckInfo) && is_array($urlCheckInfo['first'])) {
            $urlCheckInfo['first']['url_id'] = intval($url->getId());

            $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

            $urlCheckRepository = new UrlCheckRepository($this->conn, $isTest);
            $urlCheckRepository->save($urlCheck);
            $id = $urlCheck->getId();

            $urlCheckFound = $urlCheckRepository->find(
                is_int($id) ? $id : throw new UrlException(self::PDO_ERROR_FOR_ID)
            );
            $this->assertInstanceOf(UrlCheck::class, $urlCheckFound);

            $urlCheckRepository->delete($id);
            $urlCheckDeleted = $urlCheckRepository->find($id);
        }

        if ($urlCheckStopSql = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryStop.sql")) {
            $this->conn->query($urlCheckStopSql);
        }
        if ($urlStopSql = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($urlStopSql);
        }

        $this->assertTrue(isset($urlCheckFound) && $urlCheckFound->exists() && is_null($urlCheckDeleted));
    }

    public function testGetEntities(): void
    {
        if ($urlSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryInit.sql")) {
            $this->conn->query($urlSqlInit);
        }

        if ($urlCheckSqlInit = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryInit.sql")) {
            $this->conn->query($urlCheckSqlInit);
        }

        $isTest = true;
        if ($urlInfoData = file_get_contents(__DIR__ . "/../fixtures/urlInfo.json")) {
            $urlInfo = json_decode(
                $urlInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );

            $url = Url::fromArray(
                is_array($urlInfo) && is_array($urlInfo['mail']) ? $urlInfo['mail'] : []
            );
            $urlRepository = new UrlRepository($this->conn, $isTest);
            $urlRepository->save($url);
        }

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode(
                $urlCheckInfoData,
                flags:JSON_OBJECT_AS_ARRAY
            );
        }
        if (isset($url) && isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first'])) {
            $urlCheckInfo['first']['urlId'] = intval($url->getId());
        }
        $urlCheck = UrlCheck::fromArray(
            isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first']) ?
                $urlCheckInfo['first'] : []
        );

        $urlCheckRepository = new UrlCheckRepository($this->conn, $isTest);

        $urlCheckRepository->save($urlCheck);

        $entities = $urlCheckRepository->getEntities();

        if ($urlCheckStopSql = file_get_contents(__DIR__ . "/../fixtures/urlCheckRepositoryStop.sql")) {
            $this->conn->query($urlCheckStopSql);
        }
        if ($urlStopSql = file_get_contents(__DIR__ . "/../fixtures/urlRepositoryStop.sql")) {
            $this->conn->query($urlStopSql);
        }

        $this->assertEquals(
            isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first']) ?
                $urlCheckInfo['first']['title'] : "",
            $entities[0]->getTitle()
        );
    }
}
