<?php

namespace App\Service\ContentFragmentPopulator;

class DoNothingPopulator extends ContentFragmentPopulator
{
    
    public function populate(array $content, float &$totalDuration, float $previousContentDuration): array
    {
        return $content;
    }
}
