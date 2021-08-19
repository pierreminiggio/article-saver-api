<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;
use InvalidArgumentException;

class GenericContentPopulator extends ContentFragmentPopulator
{

    public function __construct(private string $token, private string $projectDir, private string $contentKey)
    {
    }


    public function populate(array $content, float &$totalDuration): array
    {
        $contentKey = $this->contentKey;

        $textContent = $content[$contentKey];

        if (! $textContent) {
            return $content;
        }

        $audioLink = $this->getAudioLink($textContent);
        $content['audio'] = $audioLink;
        $audioDuration = $this->getAudioDuration($audioLink);
        $totalDuration += $audioDuration;
        $content['audioDuration'] = $audioDuration;
        $content['audioCues'] = $this->getAudioCues($textContent);

        return $content;
    }

    protected function getAudioLink(string $textContent, bool $enchance = true, int $tries = 2): string
    {

        if ($tries <= 0) {
            throw new InvalidArgumentException('Tries must be >= 1');
        }

        $curl = curl_init('https://voice.ggio.fr/processed' . ($enchance ? '' : '?enhance=0'));
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

        return $this->getAudioLink($textContent, $enchance, $tries - 1);
    }

    protected function getAudioCues(string $textContent): array
    {
        $cues = [];

        /** key: pattern, value: cue name */
        $cueTypes = [
            'add' => 'add',
            'added' => 'add',
            'air' => 'air',
            'asking' => 'question',
            'automaker' => 'automaker',
            'bed' => 'ronflex',
            'button' => 'button',
            'california' => 'california',
            'camera' => 'camera',
            'clear' => 'remove',
            'comment' => 'comment',
            'download' => 'download',
            'electric' => 'electricity',
            'electricity' => 'electricity',
            'elon' => 'elon',
            'excited' => 'exciting',
            'exciting' => 'exciting',
            'excitation' => 'exciting',
            'exclamation' => 'exclamation',
            'exit' => 'exit',
            'fan' => 'fan',
            'full' => 'full',
            'help' => 'help',
            'interesting' => 'interesting',
            'launch' => 'rocket',
            'leave' => 'exit',
            'mars' => 'mars',
            'midnight' => 'midnight',
            'mind' => 'mind',
            'month' => 'month',
            'morning' => 'morning',
            'musk' => 'elon',
            'news' => 'news',
            'patient' => 'ronflex',
            'patience' => 'ronflex',
            'people' => 'people',
            'public' => 'people',
            'question' => 'question',
            'radar' => 'radar',
            'remove' => 'remove',
            'road' => 'road',
            'rocket' => 'rocket',
            'roll' => 'roll',
            'saturday' => 'saturday',
            'seeing' => 'vision',
            'self-driving' => 'self-driving',
            'send' => 'send',hel
            'sleep' => 'ronflex',
            'slow' => 'snail',
            'snail' => 'snail',
            'software' => 'software',
            'spend' => 'spend',
            'spent' => 'spend',
            'tech' => 'tech',
            'tesla' => 'tesla',
            'test' => 'test',
            'thousand' => 'thousand',
            'time' => 'time',
            'turning' => 'turn',
            'tweet' => 'twitter',
            'twitter' => 'twitter',
            'update' => 'update',
            'updating' => 'update',
            'vision' => 'vision',
            'wait' => 'time',
            'waiting' => 'time',
            'ways' => 'ways',
            'weekend' => 'weekend'
        ];
        
        foreach ($cueTypes as $cuePattern => $cueName) {
            $previousText = '';
            $explodedTexts = explode($cuePattern, strtolower($textContent));
            
            if (count($explodedTexts) === 1) {
                continue;
            }

            foreach ($explodedTexts as $explodedTextIndex => $explodedText) {

                $previousText .= $explodedText;

                if ($explodedTextIndex === 1) {
                    continue;
                }

                $time = $previousText
                    ? $this->getAudioDuration(
                        $this->getAudioLink($previousText, false)
                    )
                    : 0
                ;

                $cues[] = [
                    'time' => $time,
                    'name' => $cueName
                ];

                $previousText .= $cuePattern;
            }
        }

        return $cues;
    }

    protected function getAudioDuration(string $audioLink): float
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
        $baseLink = 'https://voice.ggio.fr/public/cache/processed/';
        $audioFileName = substr($audioLink, strlen($baseLink));
        
        $projectDir = $this->projectDir;
        $cacheFolder = $projectDir . 'cache' . DIRECTORY_SEPARATOR;

        if (! file_exists($cacheFolder)) {
            mkdir($cacheFolder);
        }

        $downloadedFilePath = $cacheFolder . $audioFileName;

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
