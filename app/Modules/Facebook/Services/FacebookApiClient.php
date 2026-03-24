<?php

namespace App\Modules\Facebook\Services;

use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Modules\Facebook\DTOs\PageDTO;
use App\Modules\Facebook\DTOs\PublishResponseDTO;
use App\Modules\Facebook\DTOs\TokenDTO;
use Illuminate\Support\Facades\Http;

class FacebookApiClient implements FacebookApiClientInterface
{
    private const API_VERSION = 'v21.0';

    private const BASE_URL = 'https://graph.facebook.com';

    public function __construct(
        private readonly string $appId,
        private readonly string $appSecret,
    ) {}

    public function getLongLivedToken(string $shortLivedToken): TokenDTO
    {
        $response = Http::get($this->baseUrl().'/oauth/access_token', [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to get long-lived token: '.$response->body());
        }

        $expiresIn = $response->json('expires_in', 5184000);

        return new TokenDTO(
            token: $response->json('access_token'),
            expiresAt: new \DateTimeImmutable('+'.$expiresIn.' seconds'),
        );
    }

    /** @return PageDTO[] */
    public function getPages(string $userToken): array
    {
        $response = Http::get($this->baseUrl().'/me/accounts', [
            'access_token' => $userToken,
            'fields' => 'id,name,access_token,category',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch pages: '.$response->body());
        }

        return collect($response->json('data', []))->map(fn ($p) => new PageDTO(
            pageId: $p['id'],
            pageName: $p['name'],
            accessToken: $p['access_token'],
            category: $p['category'] ?? '',
        ))->all();
    }

    public function publishPhoto(string $pageToken, string $pageId, array $data): PublishResponseDTO
    {
        // data should contain: message (text), url or source (image)
        $response = Http::post($this->baseUrl().'/'.$pageId.'/photos', array_merge(
            ['access_token' => $pageToken],
            $data
        ));

        if ($response->failed()) {
            $error = $response->json();

            return new PublishResponseDTO(
                success: false,
                errorMessage: $error['error']['message'] ?? $response->body(),
                errorCode: $error['error']['code'] ?? $response->status(),
            );
        }

        return new PublishResponseDTO(
            success: true,
            facebookPostId: $response->json('post_id') ?? $response->json('id'),
        );
    }

    public function publishVideo(string $pageToken, string $pageId, array $data): PublishResponseDTO
    {
        // data should contain: file_path (local path), description (text), post_type (reel/story)
        $filePath = $data['file_path'] ?? null;
        if (! $filePath || ! file_exists($filePath)) {
            return new PublishResponseDTO(success: false, errorMessage: 'Video file not found');
        }

        $fileSize = filesize($filePath);

        // Phase 1: Start upload session
        $startResponse = Http::post($this->baseUrl().'/'.$pageId.'/videos', [
            'access_token' => $pageToken,
            'upload_phase' => 'start',
            'file_size' => $fileSize,
        ]);

        if ($startResponse->failed()) {
            return new PublishResponseDTO(success: false, errorMessage: 'Failed to start upload: '.$startResponse->body());
        }

        $uploadSessionId = $startResponse->json('upload_session_id');
        $videoId = $startResponse->json('video_id');

        // Phase 2: Transfer chunks
        $chunkSize = 1024 * 1024 * 5; // 5MB chunks
        $offset = 0;
        $handle = fopen($filePath, 'rb');

        while (! feof($handle)) {
            $chunk = fread($handle, $chunkSize);
            $transferResponse = Http::attach('video_file_chunk', $chunk, 'chunk')
                ->post($this->baseUrl().'/'.$pageId.'/videos', [
                    'access_token' => $pageToken,
                    'upload_phase' => 'transfer',
                    'upload_session_id' => $uploadSessionId,
                    'start_offset' => $offset,
                ]);

            if ($transferResponse->failed()) {
                fclose($handle);

                return new PublishResponseDTO(success: false, errorMessage: 'Chunk upload failed at offset '.$offset);
            }

            $offset = $transferResponse->json('start_offset', $offset + strlen($chunk));
        }
        fclose($handle);

        // Phase 3: Finish and publish
        $finishPayload = [
            'access_token' => $pageToken,
            'upload_phase' => 'finish',
            'upload_session_id' => $uploadSessionId,
            'description' => $data['description'] ?? '',
        ];

        if (($data['post_type'] ?? 'video') === 'reel') {
            $finishPayload['reel_to_video'] = true;
        }

        $finishResponse = Http::post($this->baseUrl().'/'.$pageId.'/videos', $finishPayload);

        if ($finishResponse->failed()) {
            return new PublishResponseDTO(success: false, errorMessage: 'Failed to finish upload: '.$finishResponse->body());
        }

        return new PublishResponseDTO(
            success: true,
            facebookPostId: $videoId,
        );
    }

    public function refreshToken(string $token): TokenDTO
    {
        // For Facebook, refreshing = exchanging current long-lived token for a new one
        return $this->getLongLivedToken($token);
    }

    private function baseUrl(): string
    {
        return self::BASE_URL.'/'.self::API_VERSION;
    }
}
