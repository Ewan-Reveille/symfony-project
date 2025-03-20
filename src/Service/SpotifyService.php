<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class SpotifyService
{
    private HttpClientInterface $client;
    private string $clientId;
    private string $clientSecret;
    private string $apiUrl;
    private ?string $accessToken = null;

    public function __construct(HttpClientInterface $client, ParameterBagInterface $params)
    {
        $this->client = $client;
        $this->clientId = $_ENV['SPOTIFY_CLIENT_ID'];
        $this->clientSecret = $_ENV['SPOTIFY_CLIENT_SECRET'];
        $this->apiUrl = $_ENV['SPOTIFY_API_URL'];
    }

    private function authenticate()
    {
        $response = $this->client->request('POST', 'https://accounts.spotify.com/api/token', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("{$this->clientId}:{$this->clientSecret}"),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $data = $response->toArray();
        $this->accessToken = $data['access_token'];
    }

    public function getArtist(string $artistName): array
    {
        if (!$this->accessToken) {
            $this->authenticate();
        }

        $response = $this->client->request('GET', "{$this->apiUrl}/search", [
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}"
            ],
            'query' => [
                'q' => $artistName,
                'type' => 'artist',
                'limit' => 5
            ]
        ]);

        return $response->toArray();
    }
    
}
