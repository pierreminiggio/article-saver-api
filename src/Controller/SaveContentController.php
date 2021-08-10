<?php

namespace App\Controller;

use App\Command\SaveContentCommand;

class SaveContentController
{
    public function __construct(private SaveContentCommand $command)
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

        if (! isset($jsonBody['content'])) {
            http_response_code(400);

            return;
        }

        $command = $this->command;
        $command($jsonBody['uuid'], json_encode($jsonBody['content']));
    }
}
