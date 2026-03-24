<?php

namespace App\Modules\Facebook\DTOs;

class PublishResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $facebookPostId = null,
        public readonly ?string $errorMessage = null,
        public readonly ?int $errorCode = null,
    ) {}
}
