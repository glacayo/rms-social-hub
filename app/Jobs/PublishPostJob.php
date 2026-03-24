<?php

namespace App\Jobs;

use App\Models\Post;
use App\Modules\Publisher\PublishService;
use App\Modules\Publisher\RetryPolicy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;      // RetryPolicy handles retries manually

    public int $timeout = 120;  // 2 minutes max per attempt

    public function __construct(
        private readonly Post $post,
    ) {
        $this->onQueue('publishing');
    }

    public function handle(PublishService $publishService, RetryPolicy $retryPolicy): void
    {
        Log::info('PublishPostJob: starting', ['post_id' => $this->post->id]);

        // Guard: skip if post is no longer in a publishable state
        $post = $this->post->fresh();
        if (! in_array($post->status, [Post::STATUS_SCHEDULED, Post::STATUS_SENDING])) {
            Log::warning('PublishPostJob: skipping — post not in publishable state', [
                'post_id' => $post->id,
                'status' => $post->status,
            ]);

            return;
        }

        try {
            $publishService->publish($post);
            Log::info('PublishPostJob: completed', ['post_id' => $post->id, 'status' => $post->fresh()->status]);
        } catch (\Throwable $e) {
            Log::error('PublishPostJob: exception', ['post_id' => $post->id, 'error' => $e->getMessage()]);
            $retryPolicy->handleFailure($post, $e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('PublishPostJob: job itself failed (queue error)', [
            'post_id' => $this->post->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
