<?php

namespace App\Service\ContentFragmentPopulator;

use App\Service\EmbedSrcMatcher\YoutubeVideoSrcMatcher;
use Exception;

class ContentFragmentPopulatorFactory
{

    /** @var string[] */
    protected static array $textContentTypes = [
        'block-quote',
        'text',
        'title'
    ];

    public function __construct(
        private string $token,
        private string $projectDir,
        private YoutubeVideoSrcMatcher $youtubeVideoSrcMatcher
    )
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

        if (in_array($contentType, self::$textContentTypes)) {
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
                'image'
            ])
        ) {
            return new ImageContentPopulator();
        }

        if (
            in_array($contentType, [
                'twitter'
            ])
        ) {
            return new TwitterContentPopulator($token, $projectDir);
        }

        if ($contentType === 'embed') {
            $url = $content['url'] ?? null;

            if ($url === null) {
                throw new Exception('WTF an embed with no URL ???');
            }

            if ($this->youtubeVideoSrcMatcher->getYoutubeId($url) !== null) {
                return new YoutubeEmbedPopulator();
            }
        }
        
        throw new Exception($contentType . ' not implemented');
    }
}
