<?php

namespace App\Entity\Render;

class VideoToRender
{
    public function __construct(
        public int $id,
        public string $articleUuid,
        public float $durationInSeconds
    )
    {
    }
}
