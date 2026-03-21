<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\Check\Check as Check;
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, CheckRepository};
use PDO;
use Exception;

#[CoversClass(Url::class)]
#[CoversClass(Check::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(CheckRepository::class)]
#[CoversMethod(CheckRepository::class, 'create')]
#[CoversMethod(CheckRepository::class, 'update')]
#[CoversMethod(CheckRepository::class, 'save')]
#[CoversMethod(CheckRepository::class, 'find')]
#[CoversMethod(CheckRepository::class, 'delete')]
#[CoversMethod(CheckRepository::class, 'getEntities')]
class CheckRepositoryTest extends TestCase
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
        $checkSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryInit.sql"
        );
        $this->conn->query($checkSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $checkInfo['first']['urlId'] = intval($url->getId());
        $check = Check::fromArray($checkInfo['first']);

        $checkRepository = new CheckRepository($this->conn);
        $checkRepository->save($check);
        $id = intval($check->getId());
        $checkTemp = $checkRepository->find($id);

        $checkStopSql = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryStop.sql"
        );
        $this->conn->query($checkStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertTrue($checkTemp->exists());
        $this->assertEquals($checkInfo['first']['status'], $checkTemp->getStatus());
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

        $checkRepository = new CheckRepository($connStub);

        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $check = Check::fromArray($checkInfo['first']);

        $this->expectException(
            Exception::class
        );

        $checkRepository->save($check);
    }

    public function testUpdate(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $checkSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryInit.sql"
        );
        $this->conn->query($checkSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $checkInfo['first']['url_id'] = intval($url->getId());
        $check = Check::fromArray($checkInfo['first']);

        $checkRepository = new CheckRepository($this->conn);
        $checkRepository->save($check);
        $id = $check->getId();
        $checkTemp = $checkRepository->find($id);

        $checkTemp->setDescription($checkInfo['first']['description']);
        $checkRepository->save($checkTemp);
        $check = $checkRepository->find($id);

        $checkStopSql = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryStop.sql"
        );
        $this->conn->query($checkStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertEquals(
            $checkInfo['first']['description'],
            $check->getDescription()
        );
    }

    public function testDelete(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $checkSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryInit.sql"
        );
        $this->conn->query($checkSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $checkInfo['first']['url_id'] = intval($url->getId());
        $check = Check::fromArray($checkInfo['first']);

        $checkRepository = new CheckRepository($this->conn);
        $checkRepository->save($check);
        $id = $check->getId();

        $checkFound = $checkRepository->find($id);
        $this->assertInstanceOf(Check::class, $checkFound);

        $checkRepository->delete($id);
        $checkDeleted = $checkRepository->find($id);

        $checkStopSql = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryStop.sql"
        );
        $this->conn->query($checkStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertTrue($checkFound->exists() && is_null($checkDeleted));
    }

    public function testGetEntities(): void
    {
        $urlSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryInit.sql"
        );
        $this->conn->query($urlSqlInit);
        $checkSqlInit = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryInit.sql"
        );
        $this->conn->query($checkSqlInit);

        $urlInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/urlInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $url = Url::fromArray($urlInfo['mail']);
        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);

        $checkInfo = json_decode(
            file_get_contents(__DIR__ . "/../fixtures/checkInfo.json"),
            JSON_OBJECT_AS_ARRAY
        );
        $checkInfo['first']['urlId'] = intval($url->getId());
        $check = Check::fromArray($checkInfo['first']);

        $checkRepository = new CheckRepository($this->conn);

        $checkRepository->save($check);

        $entities = $checkRepository->getEntities();

        $checkStopSql = file_get_contents(
            __DIR__ . "/../fixtures/checkRepositoryStop.sql"
        );
        $this->conn->query($checkStopSql);
        $urlStopSql = file_get_contents(
            __DIR__ . "/../fixtures/urlRepositoryStop.sql"
        );
        $this->conn->query($urlStopSql);

        $this->assertEquals(
            $checkInfo['first']['title'],
            $entities[0]->getTitle()
        );
    }
}
