<?php

namespace App\Controller;

use App\Query\ArticleQuery;
use OutOfBoundsException;

class ShowArticleController
{
    public function __construct(private ArticleQuery $query)
    {
    }

    public function __invoke(string $uuid): void
    {
        try {
            $query = $this->query;
            $article = $query($uuid);
        } catch (OutOfBoundsException) {
            http_response_code(404);

            return;
        }

        http_response_code(200);
        echo json_encode($article);

        return;
    }
}

