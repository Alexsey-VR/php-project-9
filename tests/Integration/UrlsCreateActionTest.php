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
use PDO;

#[CoversClass(UrlsCreateAction::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(Url::class)]
class UrlsCreateActionTest extends TestCase
{
    private PDO $connection;

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
    }

    public function testInvoke(): void
    {
        session_start();

        exec('make init');

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $messagesMockBuilder = $this->getMockBuilder(Messages::class);
        $messagesMock = $messagesMockBuilder->getMock();
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

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlsCreateAction = $urlsCreateAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        if ($psrResponse instanceof PsrResponseInterface) {
            $this->assertTrue($psrResponse->getStatusCode() === 200);
        }

        $slimRenderer = new PhpRenderer(__DIR__ . '/../../templates');
        $urlsCreateAction->setRenderer($slimRenderer);
        $urlsCreateAction->setTemplate('index.phtml');

        $responseMock->method('withStatus')->willReturnSelf();

        $psrResponse = $urlsCreateAction->__invoke(
            $serverRequestMock,
            $responseMock,
            []
        );

        if ($psrResponse instanceof PsrResponseInterface) {
            $this->assertTrue($psrResponse->getStatusCode() === 200);
        }

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
                session_start();

        exec('make init');

        $databaseinfo = [];
        if ($databaseUrl = getenv('DATABASE_URL')) {
            $databaseInfo = parse_url(
                htmlspecialchars($databaseUrl)
            );
        }
        $dbPort = $databaseInfo['port'] ?? '';
        $dbHost = $databaseInfo['host'] ?? '';
        $dbParsedPath = $databaseInfo['path'] ?? '';
        $dbPath = ltrim($dbParsedPath, '/');
        $dbUser = $databaseInfo['user'] ?? '';
        $dbPasswd = $databaseInfo['pass'] ?? '';
        $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbPath};user={$dbUser};password={$dbPasswd}";

        $pdo = new PDO($dsn);

        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($pdo),
            $pdo
        );

        $urlInfo = ['name' => 'wrong.url'];

        $url = Url::fromArray($urlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();

        $messagesMockBuilder = $this->getMockBuilder(Messages::class);
        $messagesMock = $messagesMockBuilder->getMock();
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
        $serverRequestMock->method('getParsedBodyParam')->willReturn(['name' => 'wrong.url']);

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);

        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlsCreateAction = $urlsCreateAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

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
