<?php

namespace Tests\Fakes;

use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Modules\Facebook\DTOs\PageDTO;
use App\Modules\Facebook\DTOs\PublishResponseDTO;
use App\Modules\Facebook\DTOs\TokenDTO;

class FakeFacebookApi implements FacebookApiClientInterface
{
    private bool $shouldFail = false;

    private ?string $failMessage = null;

    private array $publishedCalls = [];

    public function shouldFail(string $message = 'Fake API error'): static
    {
        $this->shouldFail = true;
        $this->failMessage = $message;

        return $this;
    }

    public function getLongLivedToken(string $shortLivedToken): TokenDTO
    {
        if ($this->shouldFail) {
            throw new \RuntimeException($this->failMessage);
        }

        return new TokenDTO(
            token: 'fake-long-lived-token-'.uniqid(),
            expiresAt: new \DateTimeImmutable('+60 days'),
        );
    }

    /** @return PageDTO[] */
    public function getPages(string $userToken): array
    {
        if ($this->shouldFail) {
            throw new \RuntimeException($this->failMessage);
        }

        return [
            new PageDTO(pageId: 'fake-page-1', pageName: 'Test Page', accessToken: 'fake-page-token'),
        ];
    }

    public function publishPhoto(string $pageToken, string $pageId, array $data): PublishResponseDTO
    {
        $this->publishedCalls[] = ['type' => 'photo', 'pageId' => $pageId, 'data' => $data];

        if ($this->shouldFail) {
            return new PublishResponseDTO(success: false, errorMessage: $this->failMessage, errorCode: 500);
        }

        return new PublishResponseDTO(success: true, facebookPostId: 'fake-post-'.uniqid());
    }

    public function publishVideo(string $pageToken, string $pageId, array $data): PublishResponseDTO
    {
        $this->publishedCalls[] = ['type' => 'video', 'pageId' => $pageId, 'data' => $data];

        if ($this->shouldFail) {
            return new PublishResponseDTO(success: false, errorMessage: $this->failMessage, errorCode: 500);
        }

        return new PublishResponseDTO(success: true, facebookPostId: 'fake-video-'.uniqid());
    }

    public function refreshToken(string $token): TokenDTO
    {
        if ($this->shouldFail) {
            throw new \RuntimeException($this->failMessage);
        }

        return new TokenDTO(
            token: 'fake-refreshed-token-'.uniqid(),
            expiresAt: new \DateTimeImmutable('+60 days'),
        );
    }

    public function getPublishedCalls(): array
    {
        return $this->publishedCalls;
    }

    public function wasPublished(): bool
    {
        return count($this->publishedCalls) > 0;
    }

    public function publishedCount(): int
    {
        return count($this->publishedCalls);
    }
}
