<?php

namespace App\Command\Render;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class MarkAsFinishedCommand
{
    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function execute(int $renderStatusId, string $filePath): void
    {
        $this->fetcher->exec(
            $this->fetcher->createQuery(
                'video_render_status'
            )->update(
                'finished_at = NOW(), file_path = :file_path',
            )->where('id = :id'),
            ['id' => $renderStatusId, 'file_path' => $filePath]
        );
    }
}
