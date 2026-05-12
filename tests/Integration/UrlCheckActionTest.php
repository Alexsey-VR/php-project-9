<?php

namespace Analyzer\Tests\Integration;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Slim\Flash\Messages;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Http\Interfaces\ResponseInterface as SlimResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Analyzer\Repository\{ValidatedUrlRepository, UrlRepository, UrlCheckRepository};
use Analyzer\Url\Url;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Exceptions\UrlException;
use PDO;
use Analyzer\Controllers\UrlCheckAction;
use Analyzer\Tests\Fixtures\DatabaseInitHelper;

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlCheckAction::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(Url::class)]
class UrlCheckActionTest extends TestCase
{
    private PDO $connection;

    protected function setUp(): void
    {
        parent::setUp();
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

    public function testRouter(): void
    {
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

        $urlCheckAction = new UrlCheckAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $testRoute = 'testRoute';
        $urlCheckAction = $urlCheckAction->setRouter($phpRouterMock)
                                       ->setRouteName($testRoute);

        $result = $urlCheckAction->getRouter();

        $this->assertTrue($result->urlFor('testRoute') === '');
        $this->assertTrue($urlCheckAction->getRouteName() === $testRoute);
    }

    public function testSuccessInvoke(): void
    {
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

        $urlCheckAction = new UrlCheckAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlCheckAction = $urlCheckAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

        $psrResponse = $urlCheckAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => "{$urlId}"]
        );

        $this->assertTrue($psrResponse->getStatusCode() === 200);
    }

    public function testWrongInvoke(): void
    {
        $validatedUrlRepository = new ValidatedUrlRepository(
            new UrlRepository($this->connection),
            $this->connection
        );

        $wrongUrlInfo = ['name' => 'https://wrong.test'];

        $wrongUrl = Url::fromArray($wrongUrlInfo);
        $validatedUrlRepository->save($wrongUrl);
        $wrongUrlId = $wrongUrl->getId();

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMock = $this->createMock(Messages::class);
        $messagesMock->method('getMessages')->willReturn(['OK']);

        $urlCheckAction = new UrlCheckAction(
            $validatedUrlRepository,
            $urlCheckRepository,
            $messagesMock
        );

        $serverRequestMockBuilder = $this->getMockBuilder(ServerRequestInterface::class);
        $serverRequestMock = $serverRequestMockBuilder->getMock();

        $app = AppFactory::create();
        $response = $app->getResponseFactory()->CreateResponse();

        $responseMockBuilder = $this->getMockBuilder(SlimResponseInterface::class);
        $responseMock = $responseMockBuilder->getMock();
        $responseMock->method('withRedirect')->willReturn($response);

        $phpRouterMockBuilder = $this->getMockBuilder(RouteParserInterface::class);
        $phpRouterMock = $phpRouterMockBuilder->getMock();

        $urlCheckAction = $urlCheckAction->setRouter($phpRouterMock)
                                        ->setRouteName('testRoute');

        $wrongPsrResponse = $urlCheckAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => "{$wrongUrlId}"]
        );

        $this->assertTrue($wrongPsrResponse->getStatusCode() === 200);
    }
}
