<?php

namespace App\Modules\Publisher;

use App\Models\Post;
use App\Models\PostPage;
use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Modules\Facebook\DTOs\PublishResponseDTO;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PublishService
{
    public function __construct(
        private readonly FacebookApiClientInterface $apiClient,
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * Publish a post to all its assigned pages.
     * Updates post_pages status individually.
     * Post-level status is determined after all pages are attempted.
     */
    public function publish(Post $post): void
    {
        $fsm = PostStateMachine::for($post);
        $fsm->startSending();

        $postPages = $post->postPages()->with('page')->get();
        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($postPages as $postPage) {
            $page = $postPage->page;

            if ($page->token_status === 'expired') {
                $this->markPageFailed($postPage, 'Page token is expired');
                $failCount++;

                continue;
            }

            try {
                $response = $this->publishToPage($post, $page, $page->access_token);

                if ($response->success) {
                    $postPage->update([
                        'status' => PostPage::STATUS_PUBLISHED,
                        'facebook_post_id' => $response->facebookPostId,
                        'published_at' => now(),
                    ]);

                    $this->auditLogger->log(
                        'post.published',
                        user: $post->user,
                        pageId: $page->id,
                        postId: $post->id,
                        metadata: ['facebook_post_id' => $response->facebookPostId]
                    );

                    $successCount++;
                } else {
                    $this->markPageFailed($postPage, $response->errorMessage ?? 'Unknown error');
                    $errors[] = $response->errorMessage;
                    $failCount++;
                }

            } catch (\Throwable $e) {
                Log::error('PublishService: exception publishing to page', [
                    'post_id' => $post->id,
                    'page_id' => $page->id,
                    'error' => $e->getMessage(),
                ]);
                $this->markPageFailed($postPage, $e->getMessage());
                $errors[] = $e->getMessage();
                $failCount++;
            }
        }

        // Determine post-level outcome
        if ($successCount > 0) {
            // At least one page succeeded — mark post as published
            PostStateMachine::for($post->fresh())->markPublished();

            $post->fresh()->user->notify(new \App\Notifications\PostPublishedNotification($post));
        } else {
            // All pages failed
            $reason = implode('; ', array_unique($errors));
            PostStateMachine::for($post->fresh())->markFailed($reason);

            $this->auditLogger->log(
                'post.failed',
                user: $post->user,
                postId: $post->id,
                metadata: ['errors' => $errors, 'retry_count' => $post->retry_count]
            );

            $freshPost = $post->fresh();
            if ($freshPost->retry_count >= 3) {
                $freshPost->user->notify(new \App\Notifications\PostFailedNotification($freshPost));

                // Also notify all admins
                \App\Models\User::where('role', 'admin')->get()
                    ->each(fn ($admin) => $admin->notify(new \App\Notifications\PostFailedNotification($freshPost)));
            }
        }
    }

    private function publishToPage(Post $post, $page, string $pageToken): PublishResponseDTO
    {
        if ($post->media_type === 'video') {
            $mediaPath = $post->media_paths[0] ?? null;

            return $this->apiClient->publishVideo($pageToken, $page->page_id, [
                'file_path' => $mediaPath ? storage_path('app/'.$mediaPath) : null,
                'description' => $post->content,
                'post_type' => $post->post_type,
            ]);
        }

        if ($post->media_type === 'image') {
            $mediaPath = $post->media_paths[0] ?? null;

            return $this->apiClient->publishPhoto($pageToken, $page->page_id, [
                'message' => $post->content,
                'url' => $mediaPath ? asset('storage/'.$mediaPath) : null,
            ]);
        }

        // Text-only post via feed
        $response = Http::post(
            "https://graph.facebook.com/v21.0/{$page->page_id}/feed",
            ['access_token' => $pageToken, 'message' => $post->content]
        );

        return new PublishResponseDTO(
            success: $response->successful(),
            facebookPostId: $response->json('id'),
            errorMessage: $response->failed() ? $response->body() : null,
        );
    }

    private function markPageFailed(PostPage $postPage, string $reason): void
    {
        $postPage->update([
            'status' => PostPage::STATUS_FAILED,
            'failed_reason' => $reason,
        ]);
    }
}
