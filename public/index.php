<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Interfaces\RouteParserInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository};
use Analyzer\Exceptions\UrlErrorRenderer;
use Analyzer\Exceptions\UrlErrorHandler;
use Analyzer\Controllers\UrlCheckAction;
use Analyzer\Controllers\UrlReadAction;
use Analyzer\Controllers\UrlsReadAction;
use Analyzer\Controllers\UrlsCreateAction;
use Analyzer\Controllers\MainAction;

session_start();

/**
 * @var \DI\Container
 */
$container = new Container();
$container->set(PhpRenderer::class, function () {
    // As a parameter the base directory is used to contain a templates
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set(PDO::class, function () {
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
    $connection = new PDO($dsn);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $connection;
});

$container->set(ValidatedUrlRepository::class, function ($container) {
    return new ValidatedUrlRepository(
        $container->get(UrlRepository::class),
        $container->get(PDO::class)
    );
});

$container->set(Logger::class, function () {
    $logger = new Logger('app');

    $logDir = __DIR__ . '/../log';
    $logFilePath = $logDir . '/errors.log';

    $logFileHandler = new RotatingFileHandler(
        $logFilePath,
        maxFiles: 7,
        level: Logger::ERROR,
        filePermission: 644,
        useLocking: true
    );

    $logger->pushHandler($logFileHandler);

    return $logger;
});

$app = AppFactory::createFromContainer($container);

$app->addRoutingMiddleware();

$container->set(RouteParserInterface::class, function () use ($app) {
    return $app->getRouteCollector()->getRouteParser();
});

$urlErrorHandler = new UrlErrorHandler(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $container->get(Logger::class)
);
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler($urlErrorHandler);
$urlErrorRenderer = $container->get(UrlErrorRenderer::class);
$urlErrorRenderer->setRenderer(
    $container->get(PhpRenderer::class)
);
$urlErrorHandler->registerErrorRenderer('text/html', $urlErrorRenderer);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', MainAction::class)->setName('mainPage');
$app->post('/urls', UrlsCreateAction::class)->setName('createUrl');
$app->get('/urls', UrlsReadAction::class)->setName('urlsList');
$app->get('/urls/{id: [0-9]{1,9}}', UrlReadAction::class)->setName('urlInfo');
$app->post('/urls/{id: [0-9]{1,9}}/checks', UrlCheckAction::class);

$app->run();
