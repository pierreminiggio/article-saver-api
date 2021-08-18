<?php

namespace App\Command\Render;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class MarkAsRenderingCommand
{
    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function execute(int $videoId): void
    {
        $this->fetcher->exec(
            $this->fetcher->createQuery(
                'video_render_status'
            )->insertInto(
                'video_id',
                ':video_id'
            ),
            ['video_id' => $videoId]
        );
    }
}
