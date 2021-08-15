<?php

namespace App\Service\ContentFragmentPopulator;

use Exception;
use InvalidArgumentException;

class GenericContentPopulator extends ContentFragmentPopulator
{

    public function __construct(private string $token, private string $contentKey)
    { 
    }


    public function populate(array $content): array
    {
        $contentKey = $this->contentKey;

        $textContent = $content[$contentKey];

        if (! $textContent) {
            return $content;
        }

        $audioLink = $this->getAudioLink($textContent);
        $content['audio'] = $audioLink;

        return $content;
    }

    protected function getAudioLink(string $textContent, int $tries = 2): string
    {

        if ($tries <= 0) {
            throw new InvalidArgumentException('Tries must be >= 1');
        }

        $curl = curl_init('https://voice.ggio.fr/processed');
        curl_setopt_array($curl, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $this->token],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => json_encode([
                'text' => $textContent,
                'lang' => 'en',
                'tld' => 'com'
            ])
        ]);

        $curlResponse = curl_exec($curl);
        curl_close($curl);

        $audioLink = null;

        if ($curlResponse) {
            $jsonCurlResponse = json_decode($curlResponse, true);
            $audioLink = $jsonCurlResponse['url'] ?? null;
        }

        if ($audioLink) {
            return $audioLink;
        }

        if ($tries === 1) {
            throw new Exception('Could not get audio');
        }

        return $this->getAudioLink($textContent, $tries - 1);
    }
}
