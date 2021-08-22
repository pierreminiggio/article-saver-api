<?php

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

require __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$configProvider = new ConfigProvider(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
$config = $configProvider->get();

$dbConfig = $config['db'];
$fetcher = new DatabaseFetcher(new DatabaseConnection(
    $dbConfig['host'],
    $dbConfig['database'],
    $dbConfig['username'],
    $dbConfig['password'],
    DatabaseConnection::UTF8_MB4
));

$renderedVideos = $fetcher->query(
    $fetcher->createQuery(
        'article',
        'a'
    )->select(
        'a.title',
        'a.description',
        'a.link',
        'a.thumbnail',
        'vrs.file_path',
        'vtr.id as video_id'
    )->join(
        'video_to_render as vtr',
        'vtr.article_id = a.id AND vtr.remotion_props IS NOT NULL'
    )->join(
        'video_render_status as vrs',
        'vrs.video_id = vtr.id AND vrs.file_path IS NOT NULL'
    )->join(
        'upload_status as us',
        'us.video_id = vtr.id AND us.failed_at IS NULL'
    )->where(<<<SQL
        vtr.id IS NOT NULL
        AND vrs.id IS NOT NULL
        AND us.id IS NULL
SQL)
);

$alreadyUploadedOrUploadingQuery = $fetcher->createQuery(
    'upload_status'
)->select(
    'id'
)->where(
    'video_id = :video_id AND failed_at IS NULL'
);

foreach ($renderedVideos as $renderedVideo) {
    $filePath = $renderedVideo['file_path'];
    
    if (! file_exists($filePath)) {
        continue;
    }

    $videoId = (int) $renderedVideo['video_id'];

    $fetchedUploads = $fetcher->query($alreadyUploadedOrUploadingQuery, ['video_id' => $videoId]);

    if ($fetchedUploads) {
        continue;
    }

    var_dump($renderedVideo);
}
