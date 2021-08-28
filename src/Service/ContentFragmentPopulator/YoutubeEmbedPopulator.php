<?php

namespace App\Service\ContentFragmentPopulator;

class YoutubeEmbedPopulator implements ContentFragmentPopulator
{

    public function populate(array $content, float &$totalDuration, float $previousContentDuration): array
    {
        $url = $content['url'] ?? null;

        if ($url === null) {
            return $content;
        }

        // TODO download video and get title

        return $content;
    }
}
