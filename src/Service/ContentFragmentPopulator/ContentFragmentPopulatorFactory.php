<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;

class ContentFragmentPopulatorFactory
{
    public static function make(array $content, string $token): ContentFragmentPopulator
    {
        $contentType = $content['type'] ?? null;

        if (! $contentType) {
            return new DoNothingPopulator();
        }

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
