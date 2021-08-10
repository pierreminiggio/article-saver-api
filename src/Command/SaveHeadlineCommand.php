<?php

namespace App\Command;

use App\Exception\AlreadyExistsException;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class SaveHeadlineCommand
{

    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function __invoke(
        string $uuid,
        string $title,
        string $description,
        string $link,
        string $thumbnail,
        string $pubDateString
    ): void
    {
        $fetchedIds = $this->fetcher->query(
            $this->fetcher->createQuery(
                'article'
            )->select(
                'id'
            )->where(
                'uuid = :uuid'
            ),
            ['uuid' => $uuid]
        );

        if ($fetchedIds) {
            throw new AlreadyExistsException();
        }

        $this->fetcher->exec(
            $this->fetcher->createQuery(
                'article'
            )->insertInto(
                'uuid, title, description, link, thumbnail, pub_date_string',
                ':uuid, :title, :description, :link, :thumbnail, :pub_date_string'
            ),
            [
                'uuid' => $uuid,
                'title' => $title,
                'description' => $description,
                'link' => $link,
                'thumbnail' => $thumbnail,
                'pub_date_string' => $pubDateString
            ]
        );
    }
}
