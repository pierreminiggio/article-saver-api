<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;

class ContentFragmentPopulatorFactory
{

    public function __construct(private string $token, private string $projectDir)
    {
    }

    public function make(array $content): ContentFragmentPopulator
    {
        $contentType = $content['type'] ?? null;

        if (! $contentType) {
            return new DoNothingPopulator();
        }

        $token = $this->token;
        $projectDir = $this->projectDir;

        if (
            in_array($contentType, [
                'block-quote',
                'text'
            ])
        ) {
            return new GenericContentPopulator($token, $projectDir, 'content');
        }

        if (
            in_array($contentType, [
                'captioned-image'
            ])
        ) {
            return new GenericContentPopulator($token, $projectDir, 'caption');
        }

        if (
            in_array($contentType, [
                'title'
            ])
        ) {
            return new GenericContentPopulator($token, $projectDir, 'title');
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
            return new TwitterContentPopulator($token, $projectDir);
        }
        
        throw new Exception('Not implemented');
    }
}
