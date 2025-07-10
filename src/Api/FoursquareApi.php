<?php

namespace ElioTools\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class FoursquareApi {
    private string $baseUrl = "https://api.foursquare.com/";
    private string $authUrl = "https://foursquare.com/oauth2/authenticate";
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken;
    private Client $httpClient;

    public function __construct(string $clientId, string $clientSecret) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 10.0,
        ]);
    }

    public function getAuthenticationUrl(string $redirectUri): string {
        $params = [
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
        ];
        
        return $this->authUrl . '?' . http_build_query($params);
    }

    public function getToken(string $code, string $redirectUri): string {
        try {
            $response = $this->httpClient->post('oauth2/access_token', [
                'form_params' => [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => $redirectUri,
                    'code' => $code,
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['access_token'] ?? '';
        } catch (GuzzleException $e) {
            throw new \RuntimeException('Failed to get access token: ' . $e->getMessage());
        }
    }

    public function setAccessToken(string $token): void {
        $this->accessToken = $token;
    }

    public function getPrivate(string $endpoint, array $params = []): array {
        if (!$this->accessToken) {
            throw new \RuntimeException('No access token set');
        }

        try {
            $response = $this->httpClient->get("v2/$endpoint", [
                'query' => array_merge($params, [
                    'oauth_token' => $this->accessToken,
                    'v' => date('Ymd')
                ])
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (GuzzleException $e) {
            throw new \RuntimeException('API request failed: ' . $e->getMessage());
        }
    }
}
