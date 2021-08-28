<?php

namespace App\Service\ContentFragmentPopulator;

class ImageContentPopulator implements ContentFragmentPopulator
{
    
    public function populate(array $content, float &$totalDuration, float $previousContentDuration): array
    {
        $totalDuration += $previousContentDuration;

        return $content;
    }
}
