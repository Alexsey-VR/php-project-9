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
use PDO;
use PDOStatement;
use Analyzer\Exceptions\UrlReadActionException;
use Analyzer\Exceptions\{AppException, UrlException, UrlRepositoryException};

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlReadAction::class)]
#[CoversClass(Url::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(AppException::class)]
#[CoversClass(UrlException::class)]
#[CoversClass(UrlReadActionException::class)]
#[CoversClass(UrlRepositoryException::class)]
class UrlReadActionTest extends TestCase
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

    public function testReadAction(): void
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

        $urlCheckInfo['first']['url_id'] = $urlId;
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');

        $urlReadAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $slimRenderer,
            $messagesMock
        );

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('getStatusCode')->willReturn(200);

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

        $this->assertEquals($psrResponse->getStatusCode(), 200);
    }

    public function testWrongUrlId(): void
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

        $urlCheckInfo['first']['url_id'] = $urlId;
        $urlCheck = UrlCheck::fromArray($urlCheckInfo['first']);

        $urlCheckRepository = new UrlCheckRepository($this->connection);
        $urlCheckRepository->save($urlCheck);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');

        $urlReadAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $slimRenderer,
            $messagesMock
        );

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'https://vesti.ru']);

        $responseMock->expects($this->once())
                    ->method('withStatus')
                    ->with($this->equalTo(404));

        $psrResponse = $urlReadAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => $urlId + 1]
        );
    }

    public function testException(): void
    {
        $pdoStatementMock = $this->createMock(PDOStatement::class);
        $pdoStatementMock->method('fetch')->willReturn([
            'id' => null,
            'name' => 'urlMock',
            'created_at' => date('Y-m-d H:i:s')
        ]);
        $pdoMock = $this->createMock(PDO::class);
        $pdoMock->method('prepare')->willReturn($pdoStatementMock);
        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($pdoMock),
            $pdoMock
        );

        $urlCheckRepositoryMock = $this->createMock(UrlCheckRepository::class);
        $messagesMock = $this->createMock(Messages::class);
        $phpRendererMockBuilder = $this->getMockBuilder(PhpRenderer::class);
        $phpRendererMock = $phpRendererMockBuilder->getMock();

        $urlReadAction = new UrlReadAction(
            $validatedUrlRepository,
            $urlCheckRepositoryMock,
            $phpRendererMock,
            $messagesMock,
        );

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $this->expectException(UrlReadActionException::class);

        $urlReadAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => 0]
        );
    }
}
