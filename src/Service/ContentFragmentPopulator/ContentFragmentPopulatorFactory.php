<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;

class ContentFragmentPopulatorFactory
{

    public function __construct(private string $token)
    {
    }

    public function make(array $content): ContentFragmentPopulator
    {
        $contentType = $content['type'] ?? null;

        if (! $contentType) {
            return new DoNothingPopulator();
        }

        $token = $this->token;

        if (
            in_array($contentType, [
                'block-quote',
                'text'
            ])
        ) {
            return new GenericContentPopulator($token, 'content');
        }

        if (
            in_array($contentType, [
                'captioned-image'
            ])
        ) {
            return new GenericContentPopulator($token, 'caption');
        }

        if (
            in_array($contentType, [
                'title'
            ])
        ) {
            return new GenericContentPopulator($token, 'title');
        }

        if (
            in_array($contentType, [
                'image'
            ])
        ) {
            return new DoNothingPopulator();
        }

        if (
            in_array($contentType, [
                'twitter'
            ])
        ) {
            return new TwitterContentPopulator($token);
        }
        
        throw new Exception('Not implemented');
    }
}
