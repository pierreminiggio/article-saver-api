<?php

namespace App\Query;

use App\Entity\Article;
use OutOfBoundsException;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class ArticleQuery
{

    public function __construct(private DatabaseFetcher $fetcher)
    {
    }

    /**
     * @throws OutOfBoundsException
     */
    public function __invoke(string $uuid): Article
    {
        $fetchedArticles = $this->fetcher->query(
            $this->fetcher->createQuery(
                'article'
            )->select(
                'uuid',
                'title',
                'description',
                'link',
                'thumbnail',
                'content'
            )->where(
                'uuid = :uuid'
            ),
            ['uuid' => $uuid]
        );

        if (! $fetchedArticles) {
            throw new OutOfBoundsException();
        }

        $fetchedArticle = $fetchedArticles[0];

        return new Article(
            $fetchedArticle['uuid'],
            $fetchedArticle['title'],
            $fetchedArticle['description'],
            $fetchedArticle['link'],
            $fetchedArticle['thumbnail'],
            $fetchedArticle['content'] !== null ? json_decode($fetchedArticle['content'], true) : null
        );
    }
}
