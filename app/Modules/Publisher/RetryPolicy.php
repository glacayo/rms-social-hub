<?php

namespace App\Modules\Publisher;

use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Services\AuditLogger;

class RetryPolicy
{
    // Backoff delays in minutes per retry attempt (1-indexed)
    private const BACKOFF_MINUTES = [
        1 => 5,
        2 => 15,
        3 => 30,
    ];

    private const MAX_RETRIES = 3;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function handleFailure(Post $post, \Throwable $exception): void
    {
        $post->refresh();

        if ($post->retry_count >= self::MAX_RETRIES) {
            // Terminal failure — already marked failed by PublishService
            $this->auditLogger->log(
                'post.failed_terminal',
                user: $post->user,
                postId: $post->id,
                metadata: [
                    'final_error' => $exception->getMessage(),
                    'retry_count' => $post->retry_count,
                ]
            );

            return;
        }

        // Re-queue with backoff
        $delayMinutes = self::BACKOFF_MINUTES[$post->retry_count + 1] ?? 30;

        $this->auditLogger->log(
            'post.retried',
            user: $post->user,
            postId: $post->id,
            metadata: [
                'retry_number' => $post->retry_count + 1,
                'delay_minutes' => $delayMinutes,
                'error' => $exception->getMessage(),
            ]
        );

        PostStateMachine::for($post)->retry(); // increments retry_count, sets status=sending
        PublishPostJob::dispatch($post)->delay(now()->addMinutes($delayMinutes))->onQueue('publishing');
    }

    public function isTerminal(Post $post): bool
    {
        return $post->retry_count >= self::MAX_RETRIES;
    }
}
