<?php

namespace Analyzer;

$path1 = __DIR__ . "/../vendor/autoload.php";
$path2 = __DIR__ . "/../../../autoload.php";
if (file_exists($path1)) {
    require_once $path1;
} else {
    require_once $path2;
}

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;

session_start();

$container = new Container();

$container->set(\PDO::class, function () {
    $databaseUrl = getenv('DATABASE_URL');
    $databaseInfo = parse_url(
        htmlspecialchars(
            $databaseUrl ? $databaseUrl : ''
        )
    );
    $dbPort = $databaseInfo['port'] ?? '';
    $dbHost = $databaseInfo['host'] ?? '';
    $dbParsedPath = $databaseInfo['path'] ?? '';
    $dbPath = ltrim($dbParsedPath, '/');
    $dbUser = $databaseInfo['user'] ?? '';
    $dbPasswd = $databaseInfo['pass'] ?? '';
    $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbPath};user={$dbUser};password={$dbPasswd}";
    $conn = new \PDO($dsn);
    $conn->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

    return $conn;
});

$container->set('renderer', function () {
    // As a parameter the base directory is used to contain a templates
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$conn = $container->get(\PDO::class);

$app->get('/', function ($request, $response) use ($conn) {
    $serverInfo = $conn->getAttribute(\PDO::ATTR_DRIVER_NAME);

    $param = [
        'greeting' => 'Hello, Render!',
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $param);
})->setName('mainPage');

$app->run();
