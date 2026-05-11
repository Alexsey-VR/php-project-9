<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Analyzer\Controllers\UrlsCreateAction;
use Slim\Flash\Messages;
use Psr\Http\Message\StreamFactoryInterface;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Views\PhpRenderer;
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Slim\Http\Response as SlimResponse;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Http\ServerRequest;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Url\Url;
use Analyzer\Exceptions\UrlException;
use Analyzer\Tests\Fixtures\DatabaseInitHelper;
use PDO;

#[CoversClass(UrlsCreateAction::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(Url::class)]
class UrlsCreateActionTest extends TestCase
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

    public function testTemplate(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );
        $urlsCreateAction->setTemplate('index.phtml');

        $this->assertTrue($urlsCreateAction->getTemplate() === 'index.phtml');
    }

    public function testRoute(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );
        $urlsCreateAction->setRouteName('urlInfo');

        $this->assertTrue(mb_strpos($urlsCreateAction->getRouteName(), 'urlInfo') !== false);
    }

    public function testRouter(): void
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

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $testRoute = 'testRoute';
        $urlsCreateAction = $urlsCreateAction->setRouter($phpRouterMock)
                                       ->setRouteName($testRoute);

        $result = $urlsCreateAction->getRouter();

        $this->assertTrue($result->urlFor('testRoute') === '');
        $this->assertTrue($urlsCreateAction->getRouteName() === $testRoute);
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

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);
        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );
        $templatePath = __DIR__ . '/../../templates';
        $slimRenderer = new PhpRenderer($templatePath);
        $result = $urlsCreateAction->setRenderer($slimRenderer);

        $this->assertTrue(
            mb_strpos($result->getRenderer()->getTemplatePath(), $templatePath) !== false
        );
    }

    public function testInvokeWithCreate(): void
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

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlsCreateAction = $urlsCreateAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'https://github.io']);

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        if ($psrResponse instanceof PsrResponseInterface) {
            $this->assertTrue($psrResponse->getStatusCode() === 200);
        }
    }

    public function testInvokeWithExists(): void
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

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlsCreateAction = $urlsCreateAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'https://github.io']);

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlsCreateAction->setRenderer($slimRenderer);
        $urlsCreateAction->setTemplate('index.phtml');

        $responseMock->method('withStatus')->willReturnSelf();

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'wrong.url']);

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        if ($psrResponse instanceof PsrResponseInterface) {
            $this->assertTrue($psrResponse->getStatusCode() === 200);
        }
    }

    public function testNotValidUrl(): void
    {
        foreach ($this->initSqlCommands as $sqlCommand) {
            $this->connection->query($sqlCommand);
        }

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'wrong.url'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlsCreateAction = new UrlsCreateAction(
            $validatedUrlRepository,
            $messagesMock
        );

        $serverRequestInterfaceMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestInterfaceMock = $serverRequestInterfaceMockBuilder->getMock();
        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequest::class);
        $serverRequestMockBuilder->setConstructorArgs([$serverRequestInterfaceMock]);
        $serverRequestMock = $serverRequestMockBuilder->getMock();
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'wrong.url']);

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlsCreateAction->setRenderer($slimRenderer);
        $urlsCreateAction->setTemplate('index.phtml');

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        if ($psrResponse instanceof PsrResponseInterface) {
            $this->assertTrue($psrResponse->getStatusCode() === 0);
        }
    }
}
