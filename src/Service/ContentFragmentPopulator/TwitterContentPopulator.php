<?php

namespace App\Service\ContentFragmentPopulator;

class TwitterContentPopulator extends ContentFragmentPopulator
{

    public function __construct(private string $token)
    {
    }

    public function populate(array $content): array
    {
        $token = $this->token;

        $main = $content['main'];
        $content['main'] = (new GenericContentPopulator($token, 'content'))->populate($main);

        $reply = $content['reply'] ?? null;

        if ($reply !== null) {
            $content['reply'] = (new GenericContentPopulator($token, 'content'))->populate($reply);
        }

        return $content;
    }
}
