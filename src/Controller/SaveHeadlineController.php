<?php

namespace App\Controller;

use App\Command\SaveHeadlineCommand;
use App\Exception\AlreadyExistsException;

class SaveHeadlineController
{
    public function __construct(private SaveHeadlineCommand $command)
    {
    }

    public function __invoke(?string $body): void
    {
        if (! $body) {
            http_response_code(400);

            return;
        }
        
        $jsonBody = json_decode($body, true);

        if (! $jsonBody) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['uuid'])) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['title'])) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['description'])) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['link'])) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['thumbnail'])) {
            http_response_code(400);

            return;
        }

        if (! isset($jsonBody['pubDate'])) {
            http_response_code(400);

            return;
        }

        try {
            $command = $this->command;
            $command(
                $jsonBody['uuid'],
                $jsonBody['title'],
                $jsonBody['description'],
                $jsonBody['link'],
                $jsonBody['thumbnail'],
                $jsonBody['pubDate']
            );
        } catch (AlreadyExistsException) {
            http_response_code(409);
        }
    }
}
