<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;
use InvalidArgumentException;

class GenericContentPopulator extends ContentFragmentPopulator
{

    public function __construct(private string $token, private string $projectDir, private string $contentKey)
    {
    }


    public function populate(array $content): array
    {
        $contentKey = $this->contentKey;

        $textContent = $content[$contentKey];

        if (! $textContent) {
            return $content;
        }

        $audioLink = $this->getAudioLink($textContent);
        $content['audio'] = $audioLink;
        $content['audio_duration'] = $this->getAudioDuration($audioLink);
        var_dump($content['audio_duration']);
        $content['audio_cues'] = $this->getAudioCues($textContent);

        return $content;
    }

    protected function getAudioLink(string $textContent, int $tries = 2): string
    {

        if ($tries <= 0) {
            throw new InvalidArgumentException('Tries must be >= 1');
        }

        $curl = curl_init('https://voice.ggio.fr/processed');
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $textContent,
                'lang' => 'en',
                'tld' => 'com'
            ])
        ]);

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $audioLink = null;

        if ($curlResponse) {
            $jsonCurlResponse = json_decode($curlResponse, true);
            $audioLink = $jsonCurlResponse['url'] ?? null;
        }

        if ($audioLink) {
            return $audioLink;
        }

        if ($tries === 1) {
            throw new Exception('Could not get audio');
        }

        return $this->getAudioLink($textContent, $tries - 1);
    }

    protected function getAudioCues(string $textContent): array
    {
        $cues = [];

        return $cues;
    }

    protected function getAudioDuration(string $audioLink): mixed
    {
        $downloadedFilePath = $this->downloadAudioFileIfNeeded($audioLink);

        $probedDuration = shell_exec(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '
            . escapeshellarg($downloadedFilePath)
        );

        unlink($downloadedFilePath);

        return (float) $probedDuration;
    }

    protected function downloadAudioFileIfNeeded(string $audioLink): string
    {
        $baseLink = 'https://voice.ggio.fr/processed/';
        $audioUuid = substr($audioLink, strlen($baseLink));
        
        $projectDir = $this->projectDir;
        $cacheFolder = $projectDir . 'cache' . DIRECTORY_SEPARATOR;

        if (! file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        $downloadedFilePath = $cacheFolder . $audioUuid . '.mp3';

        if (file_exists($downloadedFilePath)) {
            return $downloadedFilePath;
        }

        $fp = fopen($downloadedFilePath, 'w+');
        $ch = curl_init($audioLink);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if ($httpCode !== 200 || ! file_exists($downloadedFilePath)) {
            throw new Exception('Could not download audio file');
        }

        return $downloadedFilePath;
    }
}
