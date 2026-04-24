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

#[CoversClass(UrlCheckRepository::class)]
#[CoversClass(UrlRepository::class)]
#[CoversClass(ValidatedUrlRepository::class)]
#[CoversClass(UrlCheckAction::class)]
#[CoversClass(UrlCheck::class)]
#[CoversClass(Url::class)]
class UrlCheckActionTest extends TestCase
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
            new UrlRepository($this->connection)
        );

        $urlInfo = ['name' => 'https://ru.hexlet.io'];
        $wrongUrlInfo = ['name' => 'https://wrong.test'];

        $url = Url::fromArray($urlInfo);
        $wrongUrl = Url::fromArray($wrongUrlInfo);
        $validatedUrlRepository->save($url);
        $urlId = $url->getId();
        $validatedUrlRepository->save($wrongUrl);
        $wrongUrlId = $wrongUrl->getId();

        $urlCheckRepository = new UrlCheckRepository($this->connection);

        $messagesMockBuilder = $this->getMockBuilder(Messages::class);
        $messagesMock = $messagesMockBuilder->getMock();
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

        $wrongPsrResponse = $urlCheckAction->__invoke(
            $serverRequestMock,
            $responseMock,
            ['id' => "{$wrongUrlId}"]
        );

        $this->assertTrue($wrongPsrResponse->getStatusCode() === 200);
    }
}
