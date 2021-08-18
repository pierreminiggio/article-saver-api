<?php

namespace App\Query\Video;

use App\Entity\Render\VideoToRender;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class VideosToRenderQuery
{
    public function __construct(
        private DatabaseFetcher $fetcher
    )
    {
    }

    /**
     * @return VideoToRender[]
     */
    public function execute(): array
    {
        $orderedRenderStatusQuery = $this->fetcher->createQuery(
            $this->fetcher->createQuery(
                'video_render_status'
            )->select(
                'video_id',
                'max(id) as last_id'
            )->groupBy(
                'video_id'
            ),
            'lrs'
        )->select(
            'lrs.last_id as id',
            'lrs.video_id',
            'rs.finished_at',
            'rs.failed_at'
        )->join(
            'video_render_status as rs',
            'rs.id = lrs.last_id'
        );

        $queriedVideos = $this->fetcher->query(
            $this->fetcher->createQuery(
                'video_to_render',
                'v'
            )->join(
                '(' . $orderedRenderStatusQuery->build() . ') as crs',
                'crs.video_id = v.id'
            )->join(
                'article as a',
                'a.id = v.article_id'
            )->select(
                'v.id',
                'a.uuid',
                'v.remotion_props'
            )->where(<<<SQL
                v.remotion_props IS NOT NULL
                AND ((crs.finished_at IS NULL AND crs.failed_at IS NOT NULL) OR crs.id IS NULL)
            SQL)
        );

        return array_map(fn (array $queriedVideo) => new VideoToRender(
            (int) $queriedVideo['id'],
            $queriedVideo['uuid'],
            json_decode($queriedVideo['remotion_props'], true)['duration']
        ), $queriedVideos);
    }
}
