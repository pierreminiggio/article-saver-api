<?php

namespace App;

use App\Command\SaveContentCommand;
use App\Command\SaveHeadlineCommand;
use App\Controller\SaveContentController;
use App\Controller\SaveHeadlineController;
use App\Controller\ShowArticleController;
use App\Controller\ShowRemotionPropsController;
use App\Query\ArticleQuery;
use App\Query\RemotionPropsQuery;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use RuntimeException;

class App
{
    public function run(
        string $path,
        ?string $queryParameters,
        ?string $authHeader,
        ?string $origin,
        ?string $accessControlRequestHeaders
    ): void
    {

        if ($origin) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');

        if ($accessControlRequestHeaders) {
            header('Access-Control-Allow-Headers: ' . $accessControlRequestHeaders);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        $projectDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
        $config = (new ConfigProvider($projectDir))->get();

        $dbConfig = $config['db'];
        $fetcher = new DatabaseFetcher(new DatabaseConnection(
            $dbConfig['host'],
            $dbConfig['database'],
            $dbConfig['username'],
            $dbConfig['password'],
            DatabaseConnection::UTF8_MB4
        ));

        $articleUrlPrefix = '/article/';
        $remotionUrlPrefix = '/remotion/';

        if ($path === '/headline' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->protectUsingToken($authHeader, $config);
            (new SaveHeadlineController(
                new SaveHeadlineCommand($fetcher)
            ))(file_get_contents('php://input'));
            exit;
        } elseif ($path === '/content' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->protectUsingToken($authHeader, $config);
            (new SaveContentController(
                new SaveContentCommand($fetcher)
            ))(file_get_contents('php://input'));
            exit;
        } elseif (str_starts_with($path, $articleUrlPrefix)) {
            (new ShowArticleController(
                new ArticleQuery($fetcher)
            ))(explode('?', substr($path, strlen($articleUrlPrefix)))[0]);
            exit;
        } elseif (str_starts_with($path, $remotionUrlPrefix)) {
            (new ShowRemotionPropsController(
                new RemotionPropsQuery($fetcher)
            ))(explode('?', substr($path, strlen($remotionUrlPrefix)))[0]);
            exit;
        }

        http_response_code(404);
        exit;
    }

    protected function protectUsingToken(?string $authHeader, array $config): void
    {
        if (! isset($config['token'])) {
            throw new RuntimeException('bad config, no token');
        }

        $token = $config['token'];

        if (! $authHeader || $authHeader !== 'Bearer ' . $token) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }
}
