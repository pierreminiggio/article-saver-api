<?php

namespace App\Service\ContentFragmentPopulator;

use App\Service\EmbedSrcMatcher\YoutubeVideoSrcMatcher;

class YoutubeEmbedPopulator extends ContentFragmentPopulator
{

    public function __construct(
        private string $token,
        private string $projectDir,
        private YoutubeVideoSrcMatcher $youtubeVideoSrcMatcher
    )
    {
    }

    public function populate(array $content, float &$totalDuration, float $previousContentDuration): array
    {
        $url = $content['url'] ?? null;

        if ($url === null) {
            return $content;
        }

        $youtubeVideoId = $this->youtubeVideoSrcMatcher->getYoutubeId($url);

        if ($youtubeVideoId === null) {
            return $content;
        }

        $this->populateVideoClip($content, $youtubeVideoId);
        $this->populateVideoClipDuration($content, $youtubeVideoId);
        $this->populateVideoTitle($content, $youtubeVideoId);

        return $content;
    }

    protected function populateVideoClip(array &$content, string $youtubeVideoId): void
    {
        $clipApiUrl = 'https://youtube-video-random-clip-api.miniggiodev.fr/';
        $outputClipUrl = $clipApiUrl . 'public/video/';

        $videoClipCurl = curl_init($clipApiUrl . $youtubeVideoId);
        curl_setopt_array($videoClipCurl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json' , 'Authorization: Bearer ' . $this->token]
        ]);
        curl_exec($videoClipCurl);
        $httpCode = curl_getinfo($videoClipCurl)['http_code'];
        curl_close($videoClipCurl);

        $content['video_clip'] = $httpCode === 204 ? ($outputClipUrl . $youtubeVideoId) : null;
    }

    protected function populateVideoClipDuration(array &$content, string $youtubeVideoId): void
    {
        $content['video_clip_duration'] = null;
        $videoClipUrl = $content['video_clip'] ?? null;

        if ($videoClipUrl === null) {
            return;
        }

        $cacheFolder = $this->projectDir . 'cache' . DIRECTORY_SEPARATOR;

        $filename = $cacheFolder . $youtubeVideoId . '.mp4';

        if (! file_exists($filename)) {
            $fp = fopen($filename, 'w+');
            $ch = curl_init($videoClipUrl);
            curl_setopt($ch, CURLOPT_TIMEOUT, 50);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            fclose($fp);

            if ($httpCode !== 200) {
                unlink($videoClipUrl);
            }
        }
        if (! file_exists($filename)) {
            return;
        }

        $probedDuration = shell_exec(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($filename)
        );

        unlink($filename);

        $content['video_clip_duration'] = (float) $probedDuration;
    }

    protected function populateVideoTitle(array &$content, string $youtubeVideoId): void
    {
        $content['video_title'] = null;

        $videoInfoCurl = curl_init('https://youtube-video-infos-api.ggio.fr/' . $youtubeVideoId);
        curl_setopt($videoInfoCurl, CURLOPT_RETURNTRANSFER, true);

        $videoInfoResponse = curl_exec($videoInfoCurl);
        $videoInfoHttpCode = curl_getinfo($videoInfoCurl, CURLINFO_HTTP_CODE);

        curl_close($videoInfoCurl);

        if ($videoInfoHttpCode !== 200) {
            return;
        }

        if (! $videoInfoResponse) {
            return;
        }

        $videoInfoJsonResponse = json_decode($videoInfoResponse, true);

        if (! $videoInfoJsonResponse) {
            return;
        }

        $title = $videoInfoJsonResponse['title'] ?? null;

        if (! $title) {
            return;
        }

        $content['video_title'] = $title;
    }
}
