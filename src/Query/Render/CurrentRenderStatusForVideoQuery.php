<?php

namespace App\Query\Render;

use App\Entity\Render\RenderStatus;
use DateTime;
use NeutronStars\Database\Query;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class CurrentRenderStatusForVideoQuery
{
    public function __construct(
        private DatabaseFetcher $fetcher
    )
    {
    }

    public function execute(int $videoId): ?RenderStatus
    {
        $fetchedStatuses = $this->fetcher->query(
            $this->fetcher->createQuery(
                'video_render_status'
            )->select(
                'id',
                'finished_at',
                'file_path',
                'failed_at',
                'fail_reason'
            )->where(
                'video_id = :video_id'
            )->orderBy(
                'created_at',
                Query::ORDER_BY_DESC
            )->limit(1),
            ['video_id' => $videoId]
        );

        if (! $fetchedStatuses) {
            return null;
        }

        $fetchedStatus = $fetchedStatuses[0];

        return new RenderStatus(
            (int) $fetchedStatus['id'],
            $fetchedStatus['finished_at'] ? new DateTime($fetchedStatus['finished_at']) : null,
            $fetchedStatus['file_path'],
            $fetchedStatus['failed_at'] ? new DateTime($fetchedStatus['failed_at']) : null,
            $fetchedStatus['fail_reason']
        );
    }
}
