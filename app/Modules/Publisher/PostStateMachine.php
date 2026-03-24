<?php

namespace App\Modules\Publisher;

use App\Models\Post;
use RuntimeException;

class PostStateMachine
{
    /**
     * Valid transitions: [from => [allowed to states]]
     */
    private const TRANSITIONS = [
        Post::STATUS_DRAFT => [Post::STATUS_SCHEDULED, Post::STATUS_CANCELLED],
        Post::STATUS_SCHEDULED => [Post::STATUS_SENDING, Post::STATUS_CANCELLED],
        Post::STATUS_SENDING => [Post::STATUS_PUBLISHED, Post::STATUS_FAILED],
        Post::STATUS_FAILED => [Post::STATUS_SENDING],   // retry path
        Post::STATUS_PUBLISHED => [],                        // terminal
        Post::STATUS_CANCELLED => [],                        // terminal
    ];

    public function __construct(private readonly Post $post) {}

    /**
     * Check if a transition is allowed
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $allowed = self::TRANSITIONS[$this->post->status] ?? [];

        return in_array($newStatus, $allowed, true);
    }

    /**
     * Perform the transition, persist the new status
     */
    public function transitionTo(string $newStatus, array $attributes = []): Post
    {
        if (! $this->canTransitionTo($newStatus)) {
            throw new RuntimeException(
                "Invalid transition from [{$this->post->status}] to [{$newStatus}] for post #{$this->post->id}"
            );
        }

        $this->post->update(array_merge(['status' => $newStatus], $attributes));

        return $this->post->fresh();
    }

    /**
     * Convenience methods
     */
    public function schedule(\DateTimeInterface $scheduledAt): Post
    {
        return $this->transitionTo(Post::STATUS_SCHEDULED, [
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function startSending(): Post
    {
        return $this->transitionTo(Post::STATUS_SENDING);
    }

    public function markPublished(): Post
    {
        return $this->transitionTo(Post::STATUS_PUBLISHED, [
            'published_at' => now(),
        ]);
    }

    public function markFailed(string $reason): Post
    {
        return $this->transitionTo(Post::STATUS_FAILED, [
            'failed_reason' => $reason,
        ]);
    }

    public function retry(): Post
    {
        if (! $this->post->isRetryable()) {
            throw new RuntimeException(
                "Post #{$this->post->id} is not retryable (retry_count={$this->post->retry_count})"
            );
        }

        $this->post->incrementRetry();

        return $this->transitionTo(Post::STATUS_SENDING);
    }

    public function cancel(): Post
    {
        return $this->transitionTo(Post::STATUS_CANCELLED);
    }

    /**
     * Static factory for cleaner usage
     */
    public static function for(Post $post): static
    {
        return new static($post);
    }

    public function getAllowedTransitions(): array
    {
        return self::TRANSITIONS[$this->post->status] ?? [];
    }
}
