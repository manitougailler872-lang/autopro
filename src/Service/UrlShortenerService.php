<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UrlShortenerService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $bitlyToken,
    ) {
    }

    public function shorten(string $longUrl): string
    {
        $response = $this->httpClient->request(  
            'POST',
            'https://api-ssl.bitly.com/v4/shorten',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bitlyToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'long_url' => $longUrl,
                ],
            ]
        );

        $data = $response->toArray();
        return $data['link'];
    }
}