<?php

namespace App\Command;

use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class SaveContentCommand
{

    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    public function __invoke(
        string $uuid,
        string $content
    ): void
    {
        $this->fetcher->exec(
            $this->fetcher->createQuery(
                'article'
            )->update(
                'content = :content'
            )->where(
                'uuid = :uuid'
            ),
            [
                'uuid' => $uuid,
                'content' => $content
            ]
        );
    }
}
