<?php

namespace App\Modules\Facebook\Contracts;

use App\Modules\Facebook\DTOs\PageDTO;
use App\Modules\Facebook\DTOs\PublishResponseDTO;
use App\Modules\Facebook\DTOs\TokenDTO;

interface FacebookApiClientInterface
{
    public function getLongLivedToken(string $shortLivedToken): TokenDTO;

    /** @return PageDTO[] */
    public function getPages(string $userToken): array;

    public function publishPhoto(string $pageToken, string $pageId, array $data): PublishResponseDTO;

    public function publishVideo(string $pageToken, string $pageId, array $data): PublishResponseDTO;

    public function refreshToken(string $token): TokenDTO;
}
