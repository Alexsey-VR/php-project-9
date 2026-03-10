<?php

namespace Analyzer\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Analyzer\Url\Url;
use Analyzer\Repository\UrlRepository;
use PDO;

#[CoversClass(Url::class)]
#[CoversClass(UrlRepository::class)]
#[CoversMethod(UrlRepository::class, 'create')]
#[CoversMethod(UrlRepository::class, 'update')]
#[CoversMethod(UrlRepository::class, 'save')]
#[CoversMethod(UrlRepository::class, 'find')]
#[CoversMethod(UrlRepository::class, 'delete')]
#[CoversMethod(UrlRepository::class, 'getEntities')]
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
        $this->conn = new \PDO($dsn);
        $this->conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    public function testCreate(): void
    {
        $sql = "CREATE TABLE urls ( " .
            "id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY," .
            "name VARCHAR(255) unique," .
            "created_at VARCHAR(50)" .
        ")";
        $this->conn->query($sql);

        $nameToSave = 'https://mail.ru';
        $urlInfo = ['name' => $nameToSave];
        $url = Url::fromArray($urlInfo);

        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);
        $id = $url->getId();
        $urlTemp = $urlRepository->find($id);

        $sql = "DROP TABLE urls";
        $this->conn->query($sql);

        $this->assertTrue($urlTemp->exists());
        $this->assertEquals($nameToSave, $urlTemp->getUrl());
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

        $urlRepository = new UrlRepository($connStub);

        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);

        $this->expectException(
            \Exception::class
        );

        $urlRepository->save($url);
    }

    public function testUpdate(): void
    {
        $sql = "CREATE TABLE urls ( " .
            "id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY," .
            "name VARCHAR(255) unique," .
            "created_at VARCHAR(50)" .
        ")";
        $this->conn->query($sql);

        $nameToSave = 'https://mail.ru';
        $urlInfo = ['name' => $nameToSave];
        $url = Url::fromArray($urlInfo);

        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);
        $id = $url->getId();
        $urlTemp = $urlRepository->find($id);

        $nameToUpdate = 'https://yandex.ru';
        $urlTemp->setUrl($nameToUpdate);
        $urlRepository->save($urlTemp);
        $url = $urlRepository->find($id);

        $sql = "DROP TABLE urls";
        $this->conn->query($sql);

        $this->assertEquals($nameToUpdate, $url->getUrl());
    }

    public function testUnique(): void
    {
        $sql = "CREATE TABLE urls ( " .
            "id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY," .
            "name VARCHAR(255) unique," .
            "created_at VARCHAR(50)" .
        ")";
        $this->conn->query($sql);

        $nameToSave = 'https://mail.ru';
        $urlInfo = ['name' => $nameToSave];
        $url = Url::fromArray($urlInfo);
        $sameUrl = Url::fromArray($urlInfo);

        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);
        $id = $url->getId();

        $urlRepository->save($sameUrl);
        $sameId = $url->getId();

        $sql = "DROP TABLE urls";
        $this->conn->query($sql);

        $this->assertEquals($id, $sameId);
    }

    public function testDelete(): void
    {
        $sql = "CREATE TABLE urls ( " .
            "id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY," .
            "name VARCHAR(255) unique," .
            "created_at VARCHAR(50)" .
        ")";
        $this->conn->query($sql);

        $urlInfo = ['name' => 'https://mail.ru'];
        $url = Url::fromArray($urlInfo);

        $urlRepository = new UrlRepository($this->conn);
        $urlRepository->save($url);
        $id = $url->getId();

        $urlFound = $urlRepository->find($id);
        $urlRepository->delete($id);
        $urlDeleted = $urlRepository->find($id);

        $sql = "DROP TABLE urls";
        $this->conn->query($sql);

        $this->assertTrue($urlFound->exists() && is_null($urlDeleted));
    }

    public function testGetEntities(): void
    {
        $sql = "CREATE TABLE urls ( " .
            "id BIGINT PRIMARY KEY GENERATED ALWAYS AS IDENTITY," .
            "name VARCHAR(255) unique," .
            "created_at VARCHAR(50)" .
        ")";
        $this->conn->query($sql);

        $urlInfo = ['name' => 'https://mail.ru'];
        $urlFirst = Url::fromArray($urlInfo);
        $urlInfo = ['name' => 'https://yandex.ru'];
        $urlSecond = Url::fromArray($urlInfo);

        $urlRepository = new UrlRepository($this->conn);

        $urlRepository->save($urlFirst);
        $urlRepository->save($urlSecond);

        $entities = $urlRepository->getEntities();

        $sql = "DROP TABLE urls";
        $this->conn->query($sql);

        $this->assertTrue(count($entities) === 2);
    }
}
