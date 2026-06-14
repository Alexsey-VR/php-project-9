<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\{CoversClass, CoversMethod};
use Analyzer\UrlCheck\UrlCheck as UrlCheck;
use Analyzer\Url\Url as Url;
use Analyzer\Repository\{UrlRepository, UrlCheckRepository};
use Analyzer\Exceptions\UrlException;
use Analyzer\Exceptions\UrlCheckRepositoryException;
use PDO;
use PDOStatement;

#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlCheckRepositoryException::class)]
#[CoversMethod(UrlCheckRepository::class, 'create')]
#[CoversMethod(UrlCheckRepository::class, 'update')]
#[CoversMethod(UrlCheckRepository::class, 'save')]
#[CoversMethod(UrlCheckRepository::class, 'find')]
#[CoversMethod(UrlCheckRepository::class, 'delete')]
#[CoversMethod(UrlCheckRepository::class, 'getEntities')]
class UrlCheckRepositoryTest extends TestCase
{
    private PDO $connection;

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

        $url = Url::fromArray(
            is_array($urlInfo) && is_array($urlInfo['mail']) ? $urlInfo['mail'] : []
        );
        $urlRepository = new UrlRepository($this->connection);
        $urlRepository->save($url);

        $urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json");
        $urlCheckInfo = json_decode(
            $urlCheckInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $urlCheckInfo['first']['url_id'] = intval($url->getId());
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);
        $id = intval($urlCheck->getId());
        $urlCheckTemp = $urlCheckRepository->find($id);

        $this->assertTrue(
            ($urlCheckTemp instanceof UrlCheck) ? $urlCheckTemp->exists() : false
        );
        $this->assertEquals($urlCheckInfo['first']['status'], $urlCheckTemp->getStatus());
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

        $urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json");
        $urlCheckInfo = json_decode(
            $urlCheckInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $this->expectException(
            UrlCheckRepositoryException::class
        );

        $urlCheckRepository = new UrlCheckRepository($connStub);
        $urlCheckRepository->save($urlCheck);
    }

    public function testUpdate(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $url = Url::fromArray($urlInfo['mail']);

        $urlRepository = new UrlRepository($this->connection);
        $urlRepository->save($url);

        $urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json");
        $urlCheckInfo = json_decode(
            $urlCheckInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $urlCheckInfo['first']['url_id'] = intval($url->getId());

        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);
        $id = $urlCheck->getId();
        $urlCheckTemp = $urlCheckRepository->find(
            is_int($id) ? $id : throw new UrlCheckRepositoryException(50001)
        );

        if ($urlCheckTemp instanceof UrlCheck) {
            $urlCheckTemp->setCheckInfo(
                $urlCheckInfo['first']['status'],
                $urlCheckInfo['first']['h1'],
                $urlCheckInfo['first']['title'],
                $urlCheckInfo['first']['description']
            );
            $urlCheckRepository->save($urlCheckTemp);
        }
        $urlCheck = $urlCheckRepository->find($id);

        $this->assertEquals(
            is_string($urlCheckInfo['first']['description']) ? $urlCheckInfo['first']['description'] : "",
            isset($urlCheck) ? $urlCheck->getDescription() : ""
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
        $urlRepository = new UrlRepository($this->connection);
        $urlRepository->save($url);

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }

        $urlCheckDeleted = null;
        $urlCheckInfo['first']['url_id'] = intval($url->getId());

        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);
        $id = $urlCheck->getId();

        $urlCheckFound = $urlCheckRepository->find(
            is_int($id) ? $id : throw new UrlCheckRepositoryException(50001)
        );
        $this->assertInstanceOf(UrlCheck::class, $urlCheckFound);

        $urlCheckRepository->delete($id);
        $urlCheckDeleted = $urlCheckRepository->find($id);

        $this->assertTrue($urlCheckFound->exists() && is_null($urlCheckDeleted));
    }

    public function testGetEntities(): void
    {
        $urlInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlInfo.json");
        $urlInfo = json_decode(
            $urlInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $url = Url::fromArray(
            is_array($urlInfo) && is_array($urlInfo['mail']) ? $urlInfo['mail'] : []
        );
        $urlRepository = new UrlRepository($this->connection);
        $urlRepository->save($url);

        $urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json");
        $urlCheckInfo = json_decode(
            $urlCheckInfoData ?: '',
            flags:JSON_OBJECT_AS_ARRAY
        );

        $urlCheckInfo['first']['url_id'] = intval($url->getId());

        $urlCheck = UrlCheck::fromArray(
            isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first']) ?
                $urlCheckInfo['first'] : []
        );

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $urlCheckRepository->save($urlCheck);

        $entities = $urlCheckRepository->getEntities();

        $this->assertEquals(
            isset($urlCheckInfo) && is_array($urlCheckInfo) && is_array($urlCheckInfo['first']) ?
                $urlCheckInfo['first']['title'] : "",
            $entities[0]->getTitle()
        );
    }
}
