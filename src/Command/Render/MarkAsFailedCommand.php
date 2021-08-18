<?php

namespace App\Command\Render;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class MarkAsFailedCommand
{
    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function execute(int $renderStatusId, string $failReason): void
    {
        $this->fetcher->exec(
            $this->fetcher->createQuery(
                'video_render_status'
            )->update(
                'failed_at = NOW(), fail_reason = :fail_reason',
            )->where('id = :id'),
            ['id' => $renderStatusId, 'fail_reason' => $failReason]
        );
    }
}
