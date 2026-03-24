<?php

namespace Tests\Unit\Modules\Publisher;

use App\Jobs\PublishPostJob;
use App\Models\Post;
use App\Modules\Publisher\RetryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetryPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function makeFailedPost(int $retryCount = 0): Post
    {
        return Post::factory()->create([
            'status' => Post::STATUS_FAILED,
            'retry_count' => $retryCount,
        ]);
    }

    public function test_retries_post_when_under_max(): void
    {
        Queue::fake();

        $post = $this->makeFailedPost(0);
        $policy = app(RetryPolicy::class);

        $policy->handleFailure($post, new \RuntimeException('API error'));

        Queue::assertPushed(PublishPostJob::class);
        $this->assertEquals(1, $post->fresh()->retry_count);
    }

    public function test_does_not_retry_when_max_reached(): void
    {
        Queue::fake();

        $post = $this->makeFailedPost(3);
        $policy = app(RetryPolicy::class);

        $policy->handleFailure($post, new \RuntimeException('API error'));

        Queue::assertNotPushed(PublishPostJob::class);
    }

    public function test_is_terminal_when_max_retries_reached(): void
    {
        $post = $this->makeFailedPost(3);
        $policy = app(RetryPolicy::class);

        $this->assertTrue($policy->isTerminal($post));
    }
}
