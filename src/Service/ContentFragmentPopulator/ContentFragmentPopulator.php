<?php

namespace App\Service\ContentFragmentPopulator;

abstract class ContentFragmentPopulator
{
    abstract public function populate(array $content, float &$totalDuration): array;
}
