<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Slim\Flash\Messages;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository, UrlCheckRepository};
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Slim\Http\ServerRequest;
use Analyzer\Url\Url;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Controllers\UrlReadAction;
use Analyzer\Tests\Fixtures\DatabaseInitHelper;
use PDO;

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlReadAction::class)]
#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
class UrlReadActionTest extends TestCase
{
    private PDO $connection;

    /**
     * @var array<int,string>
     */
    private array $initSqlCommands;

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
        $this->connection = new PDO($dsn);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $sqlData = file_get_contents(__DIR__ . '/../../database.sql');
        $this->initSqlCommands = DatabaseInitHelper::getSQLCommands($sqlData !== false ? $sqlData : "");
    }

    public function testRenderer(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $readAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );
        $templatePath = __DIR__ . '/../../templates';
        $slimRenderer = new PhpRenderer($templatePath);
        $result = $readAction->setRenderer($slimRenderer);

        $this->assertTrue(
            mb_strpos($result->getRenderer()->getTemplatePath(), $templatePath) !== false
        );
    }

    public function testFetchTemplate(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $readAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $templatePath = __DIR__ . '/../../templates';
        $slimRenderer = new PhpRenderer($templatePath);
        $result = $readAction->setRenderer($slimRenderer)
                            ->setTemplate('Urls/url.phtml');

        $this->assertTrue(
            mb_strpos(
                $result->getRenderer()->fetch(
                    $result->getTemplate(),
                    ['id' => '']
                ),
                $urlInfo['name']
            ) !== false
        );
    }

    public function testReadAction(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }

        $urlCheckInfo['first']['urlId'] = $urlId;
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlReadAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlReadAction->setRenderer($slimRenderer);
        $urlReadAction->setTemplate('Urls/url.phtml');

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'https://vesti.ru']);

        $psrResponse = $urlReadAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => $urlId]
        );

        $this->assertEquals($psrResponse->getStatusCode(), 0);
    }

    public function testWrongUrlId(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        if ($urlCheckInfoData = file_get_contents(__DIR__ . "/../Fixtures/urlCheckInfo.json")) {
            $urlCheckInfo = json_decode($urlCheckInfoData, flags:JSON_OBJECT_AS_ARRAY);
        }

        $urlCheckInfo['first']['urlId'] = $urlId;
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlReadAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlReadAction->setRenderer($slimRenderer);
        $urlReadAction->setTemplate('Urls/url.phtml');

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'https://vesti.ru']);

        $psrResponse = $urlReadAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => $urlId + 1]
        );

        $this->assertEquals($psrResponse->getStatusCode(), 0);
    }
}
