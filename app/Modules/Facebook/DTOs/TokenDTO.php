<?php

namespace App\Modules\Facebook\DTOs;

class TokenDTO
{
    public function __construct(
        public readonly string $token,
        public readonly \DateTimeImmutable $expiresAt,
        public readonly string $tokenType = 'bearer',
    ) {}
}
