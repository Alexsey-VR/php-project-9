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
use Analyzer\Controllers\UrlsReadAction;
use PDO;
use PDOStatement;
use Analyzer\Exceptions\UrlsReadActionException;
use Analyzer\Exceptions\{AppException, UrlCheckRepositoryException};

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlsReadAction::class)]
#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(UrlsReadActionException::class)]
#[CoversClass(AppException::class)]
#[CoversClass(UrlCheckRepositoryException::class)]
class UrlsReadActionTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
    {
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

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');

        $urlsReadAction = new UrlsReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $slimRenderer,
            $messagesMock
        );

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('getStatusCode')->willReturn(200);

        $psrResponse = $urlsReadAction->__invoke(
            $serverRequestMock,
            $responseMock
        );

        $this->assertEquals($psrResponse->getStatusCode(), 200);
    }

    public function testException(): void
    {
        $urlMock = $this->createMock(Url::class);
        $urlMock->method('getId')->willReturn(null);
        $validatedUrlRepositoryMock = $this->createMock(ValidatedUrlRepository::class);
        $validatedUrlRepositoryMock->method('getEntities')->willReturn([$urlMock]);

        $urlCheckRepositoryMock = $this->createMock(UrlCheckRepository::class);
        $messagesMock = $this->createMock(Messages::class);
        $phpRendererMockBuilder = $this->getMockBuilder(PhpRenderer::class);
        $phpRendererMock = $phpRendererMockBuilder->getMock();

        $urlsReadAction = new UrlsReadAction(
            $validatedUrlRepositoryMock,
            $urlCheckRepositoryMock,
            $phpRendererMock,
            $messagesMock,
        );

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $this->expectException(UrlsReadActionException::class);

        $urlsReadAction->__invoke(
            $serverRequestMock,
            $responseMock
        );
    }
}
