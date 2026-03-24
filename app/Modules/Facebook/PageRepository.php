<?php

namespace App\Modules\Facebook;

use App\Models\FacebookPage;
use App\Models\User;
use App\Modules\Facebook\DTOs\PageDTO;
use App\Modules\Facebook\DTOs\TokenDTO;

class PageRepository
{
    public function findAll(): \Illuminate\Database\Eloquent\Collection
    {
        return FacebookPage::withTrashed(false)->get();
    }

    public function findById(int $id): ?FacebookPage
    {
        return FacebookPage::find($id);
    }

    public function findByPageId(string $pageId): ?FacebookPage
    {
        return FacebookPage::where('page_id', $pageId)->first();
    }

    public function saveFromDTO(PageDTO $pageDTO, TokenDTO $tokenDTO, User $linkedBy): FacebookPage
    {
        return FacebookPage::updateOrCreate(
            ['page_id' => $pageDTO->pageId],
            [
                'page_name'           => $pageDTO->pageName,
                'access_token'        => $pageDTO->accessToken, // encrypted by model mutator
                'token_expires_at'    => $tokenDTO->expiresAt,
                'token_status'        => 'active',
                'linked_by_user_id'   => $linkedBy->id,
                'deleted_at'          => null, // restore if soft-deleted
            ]
        );
    }

    public function delete(FacebookPage $page): void
    {
        $page->delete(); // soft delete
    }
}
