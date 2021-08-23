<?php

use App\Service\ContentFragmentPopulator\ContentFragmentPopulatorFactory;
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

$videosToUpload = $fetcher->query(
    $fetcher->createQuery(
        'article',
        'a'
    )->select(
        'a.title',
        'a.description',
        'a.link',
        'a.thumbnail',
        'vrs.file_path',
        'vtr.id as video_id',
        'ya.id as channel_id',
        'ya.youtube_id',
        'ya.google_client_id',
        'ya.google_client_secret',
        'ya.google_refresh_token',
        'ya.title as youtube_title',
        'ya.description as youtube_description',
        'ya.tags as youtube_tags'
    )->join(
        'video_to_render as vtr',
        'vtr.article_id = a.id AND vtr.remotion_props IS NOT NULL'
    )->join(
        'video_render_status as vrs',
        'vrs.video_id = vtr.id AND vrs.file_path IS NOT NULL'
    )->join(
        'upload_status as us',
        'us.video_id = vtr.id AND us.failed_at IS NULL'
    )->join(
        'channel_domain as cd',
        'a.link LIKE CONCAT(\'%\', cd.domain ,\'%\')'
    )->join(
        'youtube_account as ya',
        'ya.id = cd.youtube_id'
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

$maxYoutubeTitleLength = 100;

/** @var int[] */
$alreadyPostedChannelIds = [];

foreach ($videosToUpload as $videoToUpload) {

    $channelId = (int) $videoToUpload['channel_id'];
    
    if (in_array($channelId, $alreadyPostedChannelIds, true)) {
        continue;
    }

    $filePath = $videoToUpload['file_path'];
    
    // if (! file_exists($filePath)) {
    //     continue;
    // }

    $videoId = (int) $videoToUpload['video_id'];

    $fetchedUploads = $fetcher->query($alreadyUploadedOrUploadingQuery, ['video_id' => $videoId]);

    if ($fetchedUploads) {
        continue;
    }

    $youtubeChannelId = $videoToUpload['youtube_id'];
    $googleClientId = $videoToUpload['google_client_id'];
    $googleClientSecret = $videoToUpload['google_client_secret'];
    $googleRefreshToken = $videoToUpload['google_refresh_token'];

    if (! $youtubeChannelId || ! $googleClientId || ! $googleClientSecret || ! $googleRefreshToken) {
        continue;
    }

    // Build title
    $articleTitle = $videoToUpload['title'];
    $youtubeTitle = $videoToUpload['youtube_title'];
    $articleTitlePlaceHolder = '[title]';
    
    $youtubeTitleLength = strlen($youtubeTitle);
    $articlePlaceholderLength = strlen($articleTitlePlaceHolder);
    $youtubeTitleWithoutArticlePlaceholderLength = $youtubeTitleLength - $articlePlaceholderLength;

    $articleTitleLength = strlen($videoToUpload['title']);

    $maxArticleTitleLength = $maxYoutubeTitleLength - $youtubeTitleWithoutArticlePlaceholderLength;

    if ($articleTitleLength > $maxArticleTitleLength) {
        $articleTitle = substr($articleTitle, 0, $maxArticleTitleLength);
    }

    $videoTitle = str_replace($articleTitlePlaceHolder, $articleTitle, $youtubeTitle);

    // Build Tags
    $videoTags = [];
    $youtubeTags = $videoToUpload['youtube_tags'];
    
    if ($youtubeTags) {
        $jsonYoutubeTags = json_decode($youtubeTags, true);

        if ($jsonYoutubeTags) {
            $videoTags = $jsonYoutubeTags;
        }
    }
    
    array_unshift($videoTags, $videoTitle);

    // Build description
    $articleDescription = $videoToUpload['description'];
    $youtubeDescription = $videoToUpload['youtube_description'];
    $articleDescriptionPlaceHolder = '[description]';
    $articleTagsPlaceHolder = '[tags]';

    $videoDescription = str_replace(
        [$articleDescriptionPlaceHolder, $articleTagsPlaceHolder],
        [
            $articleDescription . (
                PHP_EOL . PHP_EOL . 'Source : ' . $videoToUpload['link']
            ),
            'Tags :' . PHP_EOL . implode(PHP_EOL, $videoTags)
        ],
        $youtubeDescription
    );

    // thumbnail, upload, and store


    // Add to already posted channels
    $alreadyPostedChannelIds[] = $channelId;
}
