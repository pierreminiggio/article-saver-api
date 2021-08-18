<?php

namespace App\Entity\Render;

use DateTimeInterface;

class RenderStatus
{
    public function __construct(
        public int $id,
        public ?DateTimeInterface $finishedAt,
        public ?string $filePath,
        public ?DateTimeInterface $failedAt,
        public ?string $failedReason
    )
    {
    }

    public function hasRenderedFile(): bool
    {
        return $this->finishedAt !== null && $this->filePath !== null;
    }
}
