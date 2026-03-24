<?php

namespace App\Modules\Facebook\Services;

use App\Modules\Facebook\DTOs\PageDTO;
use App\Modules\Facebook\DTOs\TokenDTO;
use Illuminate\Support\Facades\Http;

class OAuthService
{
    private const SCOPES = [
        'pages_manage_posts',
        'pages_read_engagement',
        'pages_show_list',
        'business_management',
    ];

    public function __construct(
        private readonly string $appId,
        private readonly string $appSecret,
        private readonly string $redirectUri,
    ) {}

    public function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => $this->appId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => implode(',', self::SCOPES),
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);

        return 'https://www.facebook.com/dialog/oauth?' . $params;
    }

    public function handleCallback(string $code): array
    {
        // Step 1: Exchange code for short-lived token
        $shortTokenResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id'     => $this->appId,
            'client_secret' => $this->appSecret,
            'redirect_uri'  => $this->redirectUri,
            'code'          => $code,
        ]);

        if ($shortTokenResponse->failed()) {
            throw new \RuntimeException('Failed to exchange code: ' . $shortTokenResponse->body());
        }

        $shortToken = $shortTokenResponse->json('access_token');

        // Step 2: Exchange for long-lived token
        $longTokenResponse = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $this->appId,
            'client_secret'     => $this->appSecret,
            'fb_exchange_token' => $shortToken,
        ]);

        if ($longTokenResponse->failed()) {
            throw new \RuntimeException('Failed to get long-lived token: ' . $longTokenResponse->body());
        }

        $expiresIn = $longTokenResponse->json('expires_in', 5184000); // default 60 days
        $tokenDTO = new TokenDTO(
            token: $longTokenResponse->json('access_token'),
            expiresAt: new \DateTimeImmutable('+' . $expiresIn . ' seconds'),
        );

        // Step 3: Fetch pages
        $pagesResponse = Http::get('https://graph.facebook.com/v21.0/me/accounts', [
            'access_token' => $tokenDTO->token,
            'fields'       => 'id,name,access_token,category',
        ]);

        if ($pagesResponse->failed()) {
            throw new \RuntimeException('Failed to fetch pages: ' . $pagesResponse->body());
        }

        $pages = collect($pagesResponse->json('data', []))->map(fn($p) => new PageDTO(
            pageId: $p['id'],
            pageName: $p['name'],
            accessToken: $p['access_token'],
            category: $p['category'] ?? '',
        ))->all();

        return [
            'token' => $tokenDTO,
            'pages' => $pages,
        ];
    }
}
