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
use Analyzer\Repository\{UrlRepository, ValidatedUrlRepository};

session_start();

$container = new Container();
$container->set('renderer', function () {
    // As a parameter the base directory is used to contain a templates
    return new PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Messages();
});

$container->set('repo', function () {
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

    return new ValidatedUrlRepository(
        new UrlRepository($conn)
    );
});


$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();

    $jsonErrors = $request->getCookieParam('errors', json_encode([]));
    $errors = json_decode($jsonErrors, JSON_OBJECT_AS_ARRAY);

    $param = [
        'messages' => $messages,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'index.phtml', $param);
})->setName('mainPage');

$app->post('/', function ($request, $response) use ($router) {
    $repo = $this->get('repo');
    $urlInfo = $request->getParsedBodyParam("url");

    $url = Url::fromArray($urlInfo);
    $repo->save($url);

    if ($repo->isValid()) {
        $this->get('flash')->addMessage(
            'success',
            $repo->getMessage()
        );

        $toUrlInfo = $router->urlFor('urlInfo', ['id' => $url->getId()]);
        return $response->withRedirect($toUrlInfo);
    }

    $toMainPage = $router->urlFor('mainPage');
    $this->get('flash')->addMessage(
        'error',
        $repo->getMessage()
    );
    return $response->withRedirect($toMainPage);
})->setName('saveUrl');

$app->get('/urls', function ($request, $response) {
    $repo = $this->get('repo');
    $urls = $repo->getEntities();
    $params = [
        'urls' => $urls
    ];

    return $this->get('renderer')
                ->render(
                    $response,
                    'Urls/urls.phtml',
                    $params
                );
})->setName('urlsList');

$app->get('/urls/{id}', function ($request, $response, array $args) {
    $repo = $this->get('repo');
    $id = $args['id'];

    $url = $repo->find($id);
    $messages = $this->get('flash')->getMessages();
    $params = [
        'url' => $url,
        'messages' => $messages
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

$app->post('/urls/{id}', function ($request, $response, array $args) {
    // ...
    return $response->withRedirect('/');
});

$app->run();
