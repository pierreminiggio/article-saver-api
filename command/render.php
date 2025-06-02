<?php

use App\Command\Render\MarkAsFailedCommand;
use App\Command\Render\MarkAsFinishedCommand;
use App\Command\Render\MarkAsRenderingCommand;
use App\Query\Render\CurrentRenderStatusForVideoQuery;
use App\Query\Video\VideosToRenderQuery;
use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRenderer;
use PierreMiniggio\GithubActionRemotionRenderer\GithubActionRemotionRendererException;

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

$query = new VideosToRenderQuery($fetcher);
$videosToRender = $query->execute();

$currentRenderStatusQuery = new CurrentRenderStatusForVideoQuery($fetcher);
$markAsRenderingCommand = new MarkAsRenderingCommand($fetcher);

$renderer = new GithubActionRemotionRenderer();
$rendererProjects = $config['rendererProjects'];

$markAsFailedCommand = new MarkAsFailedCommand($fetcher);
$markAsFinishedCommand = new MarkAsFinishedCommand($fetcher);

foreach ($videosToRender as $videoToRender) {
    $videoIdToRender = $videoToRender->id;

    if (! $videoToRender->durationInSeconds) {
        continue;
    }
    
    $renderStatus = $currentRenderStatusQuery->execute($videoIdToRender);

    $isAlreadyRendering = $renderStatus !== null && $renderStatus->failedAt === null;
    if ($isAlreadyRendering) {
        continue;
    }

    $markAsRenderingCommand->execute($videoIdToRender);
    $renderStatus = $currentRenderStatusQuery->execute($videoIdToRender);

    if ($renderStatus === null) {
        // Mark as rendering failed ?
        continue;
    }

    $rendererProject = $rendererProjects[array_rand($rendererProjects)];
    
    try {
        $videoFile = $renderer->render(
            $rendererProject['token'],
            $rendererProject['account'],
            $rendererProject['project'],
            3600,
            1,
            [
                'uuid' => $videoToRender->articleUuid,
                'durationInSeconds' => (string) $videoToRender->durationInSeconds
            ]
        );
    } catch (GithubActionRemotionRendererException $e) {
        $markAsFailedCommand->execute(
            $renderStatus->id,
            json_encode([
                'message' => $e->getMessage(),
                'trace' => $e->getTrace()
            ])
        );
        continue;
    }

    $markAsFinishedCommand->execute($renderStatus->id, $videoFile);
}
