<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\UrlCheck\UrlCheck as UrlCheck;
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, UrlCheckRepository};
use PDO;
use Exception;

#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(UrlCheckRepository::class)]
#[CoversMethod(UrlCheckRepository::class, 'create')]
#[CoversMethod(UrlCheckRepository::class, 'update')]
#[CoversMethod(UrlCheckRepository::class, 'save')]
#[CoversMethod(UrlCheckRepository::class, 'find')]
#[CoversMethod(UrlCheckRepository::class, 'delete')]
#[CoversMethod(UrlCheckRepository::class, 'getEntities')]
class UrlCheckRepositoryTest extends TestCase
{
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
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $urlCheckSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryInit.sql"
        );
        $this->conn->query($urlCheckSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheckInfo['first']['urlId'] = intval($url->getId());
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->conn);
        $urlCheckRepository->save($urlCheck);
        $id = intval($urlCheck->getId());
        $urlCheckTemp = $urlCheckRepository->find($id);

        $urlCheckStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryStop.sql"
        );
        $this->conn->query($urlCheckStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertTrue($urlCheckTemp->exists());
        $this->assertEquals($urlCheckInfo['first']['status'], $urlCheckTemp->getStatus());
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

        $urlCheckRepository = new UrlCheckRepository($connStub);

        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $this->expectException(
            Exception::class
        );

        $urlCheckRepository->save($urlCheck);
    }

    public function testUpdate(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $urlCheckSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryInit.sql"
        );
        $this->conn->query($urlCheckSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheckInfo['first']['url_id'] = intval($url->getId());
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->conn);
        $urlCheckRepository->save($urlCheck);
        $id = $urlCheck->getId();
        $urlCheckTemp = $urlCheckRepository->find($id);

        $urlCheckTemp->setDescription($urlCheckInfo['first']['description']);
        $urlCheckRepository->save($urlCheckTemp);
        $urlCheck = $urlCheckRepository->find($id);

        $urlCheckStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryStop.sql"
        );
        $this->conn->query($urlCheckStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertEquals(
            $urlCheckInfo['first']['description'],
            $urlCheck->getDescription()
        );
    }

    public function testDelete(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $urlCheckSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryInit.sql"
        );
        $this->conn->query($urlCheckSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheckInfo['first']['url_id'] = intval($url->getId());
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->conn);
        $urlCheckRepository->save($urlCheck);
        $id = $urlCheck->getId();

        $urlCheckFound = $urlCheckRepository->find($id);
        $this->assertInstanceOf(UrlCheck::class, $urlCheckFound);

        $urlCheckRepository->delete($id);
        $urlCheckDeleted = $urlCheckRepository->find($id);

        $urlCheckStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryStop.sql"
        );
        $this->conn->query($urlCheckStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertTrue($urlCheckFound->exists() && is_null($urlCheckDeleted));
    }

    public function testGetEntities(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $urlCheckSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryInit.sql"
        );
        $this->conn->query($urlCheckSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $urlCheckInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlCheckInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $urlCheckInfo['first']['urlId'] = intval($url->getId());
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->conn);

        $urlCheckRepository->save($urlCheck);

        $entities = $urlCheckRepository->getEntities();

        $urlCheckStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlCheckRepositoryStop.sql"
        );
        $this->conn->query($urlCheckStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertEquals(
            $urlCheckInfo['first']['title'],
            $entities[0]->getTitle()
        );
    }
}
