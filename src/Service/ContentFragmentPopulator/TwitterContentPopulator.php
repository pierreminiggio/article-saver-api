<?php

namespace App\Service\ContentFragmentPopulator;

class TwitterContentPopulator extends ContentFragmentPopulator
{

    public function __construct(private string $token, private string $projectDir)
    {
    }

    public function populate(array $content): array
    {
        $token = $this->token;
        $projectDir = $this->projectDir;

        $main = $content['main'];
        $content['main'] = (new GenericContentPopulator($token, $projectDir, 'content'))->populate($main);

        $reply = $content['reply'] ?? null;

        if ($reply !== null) {
            $content['reply'] = (new GenericContentPopulator($token, $projectDir, 'content'))->populate($reply);
        }

        return $content;
    }
}
