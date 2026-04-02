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
use Valitron\Validator;
use Analyzer\Url\Url;
use Analyzer\UrlCheck\UrlCheck;
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository, UrlCheckRepository};

session_start();

$container = new Container();
$container->set('renderer', function () {
    // As a parameter the base directory is used to contain a templates
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$container->set('conn', function () {
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

$container->set('urlRepo', function ($container) {
    $conn = $container->get('conn');
    return new ValidatedUrlRepository(
        new UrlRepository($conn)
    );
});

$container->set('urlCheckRepo', function ($container) {
    $conn = $container->get('conn');

    return new UrlCheckRepository($conn);
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();

    $params = [
        'messages' => $messages,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $params);
})->setName('mainPage');

$app->post('/', function ($request, $response) use ($router) {
    $urlRepo = $this->get('urlRepo');
    ['name' => $urlName] = $request->getParsedBodyParam("url");
    $urlInfo = ['name' => htmlspecialchars($urlName)];

    $url = Url::fromArray($urlInfo);
    $urlRepo->save($url);

    if ($urlRepo->isValid()) {
        $this->get('flash')->addMessage(
            'success',
            $urlRepo->getMessage()
        );

        $toUrlInfo = $router->urlFor('urlInfo', ['id' => $url->getId()]);
        return $response->withRedirect($toUrlInfo);
    }

    $toMainPage = $router->urlFor('mainPage');
    $this->get('flash')->addMessage(
        'error',
        $urlRepo->getMessage()
    );

    if ($url->exists()) {
        $toUrlInfo = $router->urlFor('urlInfo', ['id' => $url->getId()]);
        return $response->withRedirect($toUrlInfo);
    }
/*
    $this->get('flash')->addMessage(
        'error',
        $urlRepo->getMessage()
    );
*/
    $toUrlsList = $router->urlFor('urlsList');
    $response = $response->withStatus(422);
    return $response->withRedirect($toUrlsList);
})->setName('saveUrl');

$app->get('/urls', function ($request, $response) {
    $urlRepo = $this->get('urlRepo');
    $urlCheckRepo = $this->get('urlCheckRepo');
    $urls = $urlRepo->getEntities();
    $messages = $this->get('flash')->getMessages();
    $params = [
        'urls' => $urls,
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
    $urlRepo = $this->get('urlRepo');
    $urlCheckRepo = $this->get('urlCheckRepo');
    $id = $args['id'];

    $url = $urlRepo->find($id);
    $messages = $this->get('flash')->getMessages();
    $checks = $urlCheckRepo->getEntitiesByUrlId($id);
    $params = [
        'url' => $url,
        'messages' => $messages,
        'checks' => $checks
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

$app->post('/urls/{id}/checks', function ($request, $response, array $args) use ($router) {
    $urlRepo = $this->get('urlRepo');
    $id = intval($args['id']);

    $url = $urlRepo->find($id);
    $urlCheckRepo = $this->get('urlCheckRepo');

    $urlCheck = UrlCheck::fromUrl($url);
    if ($urlCheck->execute()) {
        $urlCheckRepo->save($urlCheck);

        $url->setTimestamp(
            $urlCheck->getTimestamp()
        );
        $urlRepo->save($url);

        $this->get('flash')->addMessage('success', $urlCheck->getMessage());
    } else {
        $this->get('flash')->addMessage('error', $urlCheck->getMessage());
    }
    $toUrlInfo = $router->urlFor('urlInfo', ['id' => $url->getId()]);

    return $response->withRedirect($toUrlInfo);
});

$app->run();
