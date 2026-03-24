<?php

namespace App\Modules\Facebook\Services;

use App\Models\FacebookPage;
use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;

class TokenManager
{
    public function __construct(
        private readonly FacebookApiClientInterface $apiClient,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function refresh(FacebookPage $page): void
    {
        try {
            $tokenDTO = $this->apiClient->refreshToken($page->access_token);

            $page->update([
                'access_token' => $tokenDTO->token,
                'token_expires_at' => $tokenDTO->expiresAt,
                'token_status' => 'active',
            ]);

            $this->auditLogger->log('token.refreshed', pageId: $page->id, metadata: [
                'expires_at' => $tokenDTO->expiresAt->format('Y-m-d H:i:s'),
            ]);

        } catch (\Throwable $e) {
            Log::error('Token refresh failed', [
                'page_id' => $page->id,
                'page_name' => $page->page_name,
                'error' => $e->getMessage(),
            ]);

            $this->markExpired($page);
        }
    }

    public function markExpired(FacebookPage $page): void
    {
        $page->update(['token_status' => 'expired']);

        // Notify all admins about the expired token
        \App\Models\User::where('role', 'admin')->get()
            ->each(fn ($admin) => $admin->notify(new \App\Notifications\TokenExpiredNotification($page)));

        // Cancel all scheduled/draft posts targeting this page
        $affectedPostIds = \App\Models\PostPage::where('page_id', $page->id)
            ->whereHas('post', fn ($q) => $q->whereIn('status', [
                \App\Models\Post::STATUS_DRAFT,
                \App\Models\Post::STATUS_SCHEDULED,
            ]))
            ->pluck('post_id');

        foreach ($affectedPostIds as $postId) {
            $post = \App\Models\Post::find($postId);
            if ($post && in_array($post->status, [\App\Models\Post::STATUS_DRAFT, \App\Models\Post::STATUS_SCHEDULED])) {
                try {
                    \App\Modules\Publisher\PostStateMachine::for($post)->cancel();
                    $this->auditLogger->log(
                        'post.cancelled_token_expired',
                        pageId: $page->id,
                        postId: $post->id,
                        metadata: ['reason' => 'Page token expired']
                    );
                } catch (\Throwable $e) {
                    Log::warning('TokenManager: could not cancel post', [
                        'post_id' => $postId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        $this->auditLogger->log('token.expired', pageId: $page->id, metadata: [
            'expired_at' => now()->toISOString(),
        ]);
    }
}
