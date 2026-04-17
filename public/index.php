<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;
use Slim\Flash\Messages;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Analyzer\Url\Url;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository, UrlCheckRepository};
use Analyzer\Interfaces\UrlInterface as UrlInterface;
use Analyzer\Exceptions\UrlErrorRenderer;
use Analyzer\Exceptions\UrlErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
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
$container->set('flash', function () {
    return new Messages();
});

$container->set(PDO::class, function () {
    $databaseUrl = getenv('DATABASE_URL');
    $databaseInfo = parse_url(
        htmlspecialchars(
            $databaseUrl ?? ''
        )
    );
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

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();

    $params = [
        'messages' => $messages,
        'errors' => []
    ];

    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('mainPage');

$app->post('/urls', function ($request, $response) use ($router) {
    $urlRepo = $this->get(ValidatedUrlRepository::class);

    ['name' => $urlName] = $request->getParsedBodyParam("url");
    $urlInfo = ['name' => htmlspecialchars(
        is_string($urlName) ? $urlName : ''
    )];

    $url = Url::fromArray($urlInfo);
    $urlRepo->save($url);

    if ($urlRepo->isValid()) {
        $this->get('flash')->addMessage(
            'success',
            $urlRepo->getMessage()
        );

        $toUrlInfo = $router->urlFor('urlInfo', ['id' => "{$url->getId()}"]);
        return $response->withRedirect($toUrlInfo);
    }

    $toMainPage = $router->urlFor('mainPage');
    if ($url->exists()) {
        $this->get('flash')->addMessage(
            'error',
            $urlRepo->getMessage()
        );

        $toUrlInfo = $router->urlFor('urlInfo', ['id' => "{$url->getId()}"]);
        $response = $response->withStatus(422);

        return $response->withRedirect($toUrlInfo);
    }

    $params = [
        'messages' => ['error' => [$urlRepo->getMessage()]],
        'errors' => ['url' => ['name' => $url->getUrl()]]
    ];
    $response = $response->withStatus(422);

    return $this->get('renderer')->render(
        $response,
        'index.phtml',
        $params
    );
})->setName('saveUrl');

$app->get('/urls', function ($request, $response) {
    $urlRepo = $this->get(ValidatedUrlRepository::class);
    $urlCheckRepo = $this->get(UrlCheckRepository::class);
    $urls = $urlRepo->getEntities();
    $urlItems = [];
    foreach ($urls as $url) {
        $id = $url->getId();
        $urlChecks = $urlCheckRepo->getEntitiesByUrlId($id);
        $urlItems[] = [
            'id' => $id,
            'name' => $url->getUrl(),
            'timestamp' => (count($urlChecks) > 0) ? $urlChecks[0]->getTimestamp() : '',
            'status' => (count($urlChecks) > 0) ? $urlChecks[0]->getStatus() : ''
        ];
    }

    $messages = $this->get('flash')->getMessages();
    $params = [
        'urls' => $urlItems,
        'urlCheckRepo' => $urlCheckRepo,
        'messages' => $messages
    ];

    return $this->get('renderer')
                ->render(
                    $response,
                    'Urls/urls.phtml',
                    $params
                );
})->setName('urlsList');

$app->get('/urls/{id}', function ($request, $response, array $args) {
    $urlRepo = $this->get(ValidatedUrlRepository::class);
    $urlCheckRepo = $this->get(UrlCheckRepository::class);
    $id = $args['id'];

    $url = $urlRepo->find($id);
    $messages = $this->get('flash')->getMessages();
    $checks = $urlCheckRepo->getEntitiesByUrlId($id);
    $checkItems = [];
    foreach ($checks as $check) {
        $checkItems[] = [
            'id' => $check->getId(),
            'status' => $check->getStatus(),
            'h1' => $check->getH1(),
            'title' => $check->getTitle(),
            'description' => $check->getDescription(),
            'timestamp' => $check->getTimestamp()
        ];
    }

    $params = [
        'name' => $url->getUrl(),
        'id' => $url->getId(),
        'timestamp' => $url->getTimestamp(),
        'messages' => $messages,
        'checks' => $checkItems
    ];

    if (!is_null($url)) {
        return $this->get('renderer')
            ->render(
                $response,
                'Urls/url.phtml',
                $params
            );
    }

    return $response->withStatus(400);
})->setName('urlInfo');

$app->post('/urls/{id}/checks', function ($request, $response, array $args) use ($router, $container) {
    $urlRepo = $this->get(ValidatedUrlRepository::class);
    $id = intval(
        is_string($args['id']) ? $args['id'] : null
    );

    $url = $urlRepo->find($id);
    $urlCheckRepo = $this->get(UrlCheckRepository::class);

    $urlCheck = UrlCheck::fromUrl(
        ($url instanceof UrlInterface) ?
            $url : throw new Exception("Internal error: can't get a url interface on checks")
    );

    $errorRenderer = $container->get(UrlErrorRenderer::class);
    $payload = [
        'url' => $url,
        'name' => $url->getUrl(),
        'id' => $url->getId(),
        'timestamp' => $url->getTimestamp()
    ];
    $errorRenderer->setPayload($payload);

    if ($urlCheck->execute()) {
        $urlCheckRepo->save($urlCheck);

        $timestamp = $urlCheck->getTimestamp();
        $url->setTimestamp(
            is_string($timestamp) ?
                $timestamp : throw new Exception("Internal error: can't get a timestamp on checks")
        );
        $urlRepo->save($url);

        $this->get('flash')->addMessage('success', $urlCheck->getMessage());
    } else {
        $this->get('flash')->addMessage('error', $urlCheck->getMessage());
    }

    $toUrlInfo = $router->urlFor('urlInfo', ['id' => "{$url->getId()}"]);

    return $response->withRedirect($toUrlInfo);
});

$app->run();
