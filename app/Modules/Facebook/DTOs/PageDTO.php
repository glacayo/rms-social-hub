<?php

namespace App\Modules\Facebook\DTOs;

class PageDTO
{
    public function __construct(
        public readonly string $pageId,
        public readonly string $pageName,
        public readonly string $accessToken,
        public readonly string $category = '',
    ) {}
}
