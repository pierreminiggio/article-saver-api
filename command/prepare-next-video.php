<?php

use App\Service\ContentFragmentPopulator\ContentFragmentPopulatorFactory;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

$projectDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

require $projectDir . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$config = (new ConfigProvider($projectDir))->get();

$dbConfig = $config['db'];
$fetcher = new DatabaseFetcher(new DatabaseConnection(
    $dbConfig['host'],
    $dbConfig['database'],
    $dbConfig['username'],
    $dbConfig['password'],
    DatabaseConnection::UTF8_MB4
));

$token = $config['token'];

$fetchedArticles = $fetcher->query(
    $fetcher->createQuery(
        'article',
        'a'
    )->select(
        'a.id',
        'a.content'
    )->join(
        'video_to_render as vtr',
        'vtr.article_id = a.id'
    )->where(
        'vtr.id IS NULL AND a.content IS NOT NULL'
    )->limit(
        1
    )
);

if (! $fetchedArticles) {
    exit;
}

$fetchedArticle = $fetchedArticles[0];

$articleId = (int) $fetchedArticle['id'];

// Mark as starting preparing
$fetcher->exec(
    $fetcher->createQuery(
        'video_to_render'
    )->insertInto(
        'article_id',
        ':article_id'
    ),
    ['article_id' => $articleId]
);

$articleContent = $fetchedArticle['content'];

if (! $articleContent) {
    exit;
}

$jsonArticleContent = json_decode($articleContent, true);

$contentProps = [];
$totalDuration = 0.0;

$contentPopulatorFactory = new ContentFragmentPopulatorFactory($token, $projectDir);

foreach ($jsonArticleContent as $contentFragment) {
    $contentProps[] = $contentPopulatorFactory->make($contentFragment)->populate($contentFragment, $totalDuration);
}

$remotionProps = [
    'duration' => $totalDuration,
    'content' => $contentProps
];

// Save props
$fetcher->query(
    $fetcher->createQuery(
        'video_to_render'
    )->update(
        'remotion_props = :remotion_props'
    )->where(
        'article_id = :article_id'
    ),
    [
        'article_id' => $articleId,
        'remotion_props' => json_encode($remotionProps)
    ]
);
