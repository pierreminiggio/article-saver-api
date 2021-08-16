<?php

namespace App\Service\ContentFragmentPopulator;

class DoNothingPopulator extends ContentFragmentPopulator
{
    
    public function populate(array $content, float &$totalDuration): array
    {
        return $content;
    }
}
