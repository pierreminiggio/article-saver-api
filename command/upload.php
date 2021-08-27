<?php

use NeutronStars\Database\Query;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GoogleTokenRefresher\AccessTokenProvider;
use PierreMiniggio\YoutubeThumbnailUploader\ThumbnailUploader;
use PierreMiniggio\YoutubeVideoUpdater\VideoUpdater;

$projectDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

require $projectDirectory . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$cacheFolder = $projectDirectory . 'cache' . DIRECTORY_SEPARATOR;

if (! file_exists($cacheFolder)) {
    mkdir($cacheFolder);
}

$configProvider = new ConfigProvider(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
$config = $configProvider->get();

$uploadApi = $config['upload_api'];

$apiUrl = $uploadApi['url'];
$apiToken = $uploadApi['token'];
$authHeader = ['Content-Type: application/json' , 'Authorization: Bearer ' . $apiToken];

$dbConfig = $config['db'];
$fetcher = new DatabaseFetcher(new DatabaseConnection(
    $dbConfig['host'],
    $dbConfig['database'],
    $dbConfig['username'],
    $dbConfig['password'],
    DatabaseConnection::UTF8_MB4
));

$markAsfailed = function (int $uploadStatusId, string $reason) use ($fetcher): void {
    $fetcher->exec(
        $fetcher->createQuery(
            'upload_status'
        )->update(
            'failed_at = :failed_at, fail_reason = :fail_reason'
        )->where(
            'id = :id'
        ),
        [
            'failed_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'fail_reason' => $reason,
            'id' => $uploadStatusId
        ]
    );
};

$provider = new AccessTokenProvider();
$updater = new VideoUpdater();
$uploader = new ThumbnailUploader();

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
    
    if (! file_exists($filePath)) {
        continue;
    }

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

    // Thumbnail
    $thumbnailName = $videoId . '.png';
    $thumbnailPath = $cacheFolder . $thumbnailName;

    if (! file_exists($thumbnailPath)) {
        $seconds = 3;
        shell_exec('ffmpeg -i ' . $filePath . ' -vframes 1 -an -s 1600x900 -ss ' . $seconds . ' ' . $thumbnailPath);
    }

    if (! file_exists($thumbnailPath)) {
        var_dump('failed to create thumbnail for ' . $videoId);
        continue;
    }

    $uploadStatusesQuery = [
        $fetcher->createQuery(
            'upload_status'
        )->select(
            'id',
            'finished_at',
            'failed_at'
        )->where(
            'video_id = :video_id'
        )->orderBy(
            'id',
            Query::ORDER_BY_DESC
        )->limit(
            1
        ),
        ['video_id' => $videoId]
    ];

    // Create upload status line
    $fetchedUploadStatuses = $fetcher->query(...$uploadStatusesQuery);

    if ($fetchedUploadStatuses) {
        $fetchedUploadStatus = $fetchedUploadStatuses[0];

        if ($fetchedUploadStatus['finished_at'] !== null) {
            var_dump($videoId . ' already rendered');
            continue;
        }

        if ($fetchedUploadStatus['failed_at'] === null) {
            var_dump($videoId . ' already rendering');
            continue;
        }
    }

    $fetcher->exec(
        $fetcher->createQuery(
            'upload_status'
        )->insertInto(
            'video_id, channel_id',
            ':video_id, :channel_id'
        ),
        ['video_id' => $videoId, 'channel_id' => $channelId]
    );

    $fetchedUploadStatuses = $fetcher->query(...$uploadStatusesQuery);

    if (! $fetchedUploadStatuses) {
        var_dump($videoId . ' upload creation failed');
        continue;
    }

    $fetchedUploadStatus = $fetchedUploadStatuses[0];

    $uploadStatusId = $fetchedUploadStatus['id'];

    // Upload
    $curl = curl_init($apiUrl . '/' . $youtubeChannelId);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $authHeader,
        CURLOPT_POST => 1,
        CURLOPT_POSTFIELDS => json_encode([
            'video_url' => 'https://article-saver-api.ggio.fr/render/' . $videoId,
            'title' => 'Article video ' . $videoId,
            'description' => 'Super Article Description'
        ])
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode !== 200) {
        $markAsfailed($uploadStatusId, 'Posting failed : ' . $response);
        continue;
    }

    if (! $response) {
        $markAsfailed($uploadStatusId, 'API returned an empty response');
        continue;
    }

    $jsonResponse = json_decode($response, true);

    if (! $jsonResponse) {
        $markAsfailed($uploadStatusId, 'API returned a bad json response : ' . $response);
        continue;
    }

    if (empty($jsonResponse['id'])) {
        $markAsfailed($uploadStatusId, 'API returned a bad json response, "id" missing : ' . $jsonResponse);
        continue;
    }

    $youtubeVideoId = $jsonResponse['id'];

    $accessToken = $provider->get($googleClientId, $googleClientSecret, $googleRefreshToken);

    // Update video data
    $updater->update(
        $accessToken,
        $youtubeVideoId,
        $videoTitle,
        $videoDescription,
        $videoTags,
        27,
        false
    );

    // Upload thumnail
    try {
        $uploader->upload(
            $accessToken,
            $youtubeVideoId,
            $thumbnailPath
        );
    } catch (Exception $e) {
        var_dump('Could not upload thumbnail for video ' . $videoId . ': ' . $e->getMessage());
    }

    // Update status line
    $fetcher->exec(
        $fetcher->createQuery(
            'upload_status'
        )->update(
            'finished_at = :finished_at, youtube_id = :youtube_id'
        )->where(
            'id = :id'
        ),
        [
            'finished_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'youtube_id' => $youtubeVideoId,
            'id' => $uploadStatusId
        ]
    );

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    if (file_exists($thumbnailPath)) {
        unlink($thumbnailPath);
    }

    // Add to already posted channels
    $alreadyPostedChannelIds[] = $channelId;
}
