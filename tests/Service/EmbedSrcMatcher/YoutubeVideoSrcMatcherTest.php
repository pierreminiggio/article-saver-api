<?php

namespace Test\Service\EmbedSrcMatcher;

use App\Service\EmbedSrcMatcher\YoutubeVideoSrcMatcher;
use PHPUnit\Framework\TestCase;

class YoutubeVideoSrcMatcherTest extends TestCase
{

    /**
     * @dataProvider provideValidLinks
     */
    public function testValidLinks(string $link, ?string $expectedId): void
    {
        $videoSrcMatcher = new YoutubeVideoSrcMatcher();
        self::assertSame($expectedId, $videoSrcMatcher->getYoutubeId($link));
    }

    public function provideValidLinks(): array
    {
        return [
            ['https://www.youtube.com/embed/RJYsZAen7dw', 'RJYsZAen7dw'],
            ['http://www.youtube.com/embed/RJYsZAen7dw', 'RJYsZAen7dw'],
            ['https://youtube.com/embed/RJYsZAen7dw', 'RJYsZAen7dw'],
            ['http://youtube.com/embed/RJYsZAen7dw', 'RJYsZAen7dw'],
            ['https://www.youtube.com/embed/9bZGWOrDBpg', '9bZGWOrDBpg']
        ];
    }
}
