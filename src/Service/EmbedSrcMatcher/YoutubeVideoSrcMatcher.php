<?php

namespace App\Service\EmbedSrcMatcher;

class YoutubeVideoSrcMatcher
{

    public function getYoutubeId(string $url): ?string
    {
        $youtubePrefixes = [
            'https://www.youtube.com/embed/',
            'http://www.youtube.com/embed/',
            'https://youtube.com/embed/',
            'http://youtube.com/embed/'
        ];

        foreach ($youtubePrefixes as $youtubePrefix) {
            if (str_starts_with($url, $youtubePrefix)) {
                return $this->removeGetParameters(substr($url, strlen($youtubePrefix)));
            }
        }

        return null;
    }

    protected function removeGetParameters(string $url): string
    {
        return explode('?', $url)[0];
    }
}
