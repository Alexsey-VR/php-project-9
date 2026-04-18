<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
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
use PDO;

session_start();

/**
 * @var \DI\Container
 */
$container = new Container();
$container->set('renderer', function () {
    // As a parameter the base directory is used to contain a templates
    return new PhpRenderer(__DIR__ . '/../templates');
});

$container->set(PDO::class, function () {
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
    $connection = new PDO($dsn);
    $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $connection;
});

$container->set(ValidatedUrlRepository::class, function ($container) {
    return new ValidatedUrlRepository(
        $container->get(UrlRepository::class)
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

$urlErrorHandler = new UrlErrorHandler(
    $app->getCallableResolver(),
    $app->getResponseFactory(),
    $container->get(Logger::class)
);
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler($urlErrorHandler);
$urlErrorRenderer = $container->get(UrlErrorRenderer::class);
$urlErrorRenderer->setRenderer(
    $container->get('renderer')
);
$urlErrorHandler->registerErrorRenderer('text/html', $urlErrorRenderer);

$router = $app->getRouteCollector()->getRouteParser();

$mainAction = $container->get(MainAction::class);
$app->get(
    '/',
    $mainAction->setRenderer(
        $container->get('renderer')
    )->setTemplate('index.phtml')
)->setName('mainPage');

$urlsCreateAction = $container->get(UrlsCreateAction::class);
$app->post(
    '/urls',
    $urlsCreateAction->setRenderer(
        $container->get('renderer')
    )->setTemplate('index.phtml')
    ->setRouter(
        $app->getRouteCollector()->getRouteParser()
    )->setRouteName('urlInfo')
)->setName('createUrl');

$urlsReadAction = $container->get(UrlsReadAction::class);
$app->get(
    '/urls',
    $urlsReadAction->setRenderer(
        $container->get('renderer')
    )->setTemplate(template: 'Urls/urls.phtml')
)->setName('urlsList');

$urlReadAction = $container->get(UrlReadAction::class);
$app->get(
    '/urls/{id}',
    $urlReadAction->setRenderer(
        $container->get('renderer')
    )->setTemplate(template: 'Urls/url.phtml')
)->setName('urlInfo');

$urlCheckAction = $container->get(UrlCheckAction::class);
$app->post(
    '/urls/{id}/checks',
    $urlCheckAction->setRouter(
        $app->getRouteCollector()->getRouteParser()
    )->setRouteName(routeName: 'urlInfo')
);

$app->run();
