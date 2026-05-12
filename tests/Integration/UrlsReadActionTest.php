<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Slim\Flash\Messages;
use Slim\Http\Interfaces\ResponseInterface;
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository, UrlCheckRepository};
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Slim\Http\ServerRequest;
use Analyzer\Exceptions\UrlException;
use Analyzer\Url\Url;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Controllers\UrlsReadAction;
use Analyzer\Tests\Fixtures\DatabaseInitHelper;
use PDO;

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlsReadAction::class)]
#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
class UrlsReadActionTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
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

        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $this->connection->rollBack();
        parent::tearDown();
    }

    public function testRenderer(): void
    {
        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $urlsReadAction = new UrlsReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );
        $templatePath = __DIR__ . '/../../templates';
        $slimRenderer = new PhpRenderer($templatePath);
        $result = $urlsReadAction->setRenderer($slimRenderer);

        $this->assertTrue(
            mb_strpos($result->getRenderer()->getTemplatePath(), $templatePath) !== false
        );
    }

    public function testTemplate(): void
    {
        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $urlsReadAction = new UrlsReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );
        $urlsReadAction->setTemplate('/urls/urls.phtml');

        $this->assertTrue($urlsReadAction->getTemplate() === '/urls/urls.phtml');
    }

    public function testUrlsReadAction(): void
    {
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

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckInfo['first']['url_id'] = $urlId;
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlsReadAction = new UrlsReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlsReadAction->setRenderer($slimRenderer);
        $urlsReadAction->setTemplate('Urls/urls.phtml');

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $psrResponse = $urlsReadAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        $this->assertEquals($psrResponse->getStatusCode(), 0);
    }
}
