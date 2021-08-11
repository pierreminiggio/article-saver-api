<?php

namespace App\Entity;

class Article
{
    
    public function __construct(
        public string $uuid,
        public string $title,
        public string $description,
        public string $link,
        public string $thumbnail,
        public ?array $content
    )
    {
    }
}
