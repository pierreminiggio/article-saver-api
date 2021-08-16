<?php

namespace App\Query;

use OutOfBoundsException;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class RemotionPropsQuery
{

    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    /**
     * @throws OutOfBoundsException
     */
    public function __invoke(string $uuid): array
    {
        $fetchedArticles = $this->fetcher->query(
            $this->fetcher->createQuery(
                'article',
                'a'
            )->select(
                'a.uuid',
                'a.title',
                'a.description',
                'a.thumbnail',
                'vtr.remotion_props'
            )->join(
                'video_to_render as vtr',
                'vtr.article_id = a.id'
            )->where(
                'uuid = :uuid'
            ),
            ['uuid' => $uuid]
        );

        if (! $fetchedArticles) {
            throw new OutOfBoundsException();
        }

        $fetchedArticle = $fetchedArticles[0];

        $remotionProps = json_decode($fetchedArticle['remotion_props'], true);

        if ($remotionProps === null) {
            throw new OutOfBoundsException();
        }

        return array_merge([
            'uuid' => $fetchedArticle['uuid'],
            'title' => $fetchedArticle['title'],
            'description' => $fetchedArticle['description'],
            'thumbnail' => $fetchedArticle['thumbnail'],
        ], $remotionProps);
    }
}
