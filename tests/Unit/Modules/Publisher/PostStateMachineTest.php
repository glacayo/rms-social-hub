<?php

namespace Tests\Unit\Modules\Publisher;

use App\Models\Post;
use App\Modules\Publisher\PostStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private function makePost(string $status = Post::STATUS_DRAFT): Post
    {
        return Post::factory()->create(['status' => $status, 'retry_count' => 0]);
    }

    public function test_draft_can_transition_to_scheduled(): void
    {
        $post = $this->makePost(Post::STATUS_DRAFT);
        $fsm = PostStateMachine::for($post);

        $result = $fsm->schedule(new \DateTime('+1 hour'));

        $this->assertEquals(Post::STATUS_SCHEDULED, $result->status);
        $this->assertNotNull($result->scheduled_at);
    }

    public function test_draft_can_be_cancelled(): void
    {
        $post = $this->makePost(Post::STATUS_DRAFT);
        $result = PostStateMachine::for($post)->cancel();
        $this->assertEquals(Post::STATUS_CANCELLED, $result->status);
    }

    public function test_scheduled_transitions_to_sending(): void
    {
        $post = $this->makePost(Post::STATUS_SCHEDULED);
        $result = PostStateMachine::for($post)->startSending();
        $this->assertEquals(Post::STATUS_SENDING, $result->status);
    }

    public function test_sending_transitions_to_published(): void
    {
        $post = $this->makePost(Post::STATUS_SENDING);
        $result = PostStateMachine::for($post)->markPublished();
        $this->assertEquals(Post::STATUS_PUBLISHED, $result->status);
        $this->assertNotNull($result->published_at);
    }

    public function test_sending_transitions_to_failed(): void
    {
        $post = $this->makePost(Post::STATUS_SENDING);
        $result = PostStateMachine::for($post)->markFailed('Meta API error');
        $this->assertEquals(Post::STATUS_FAILED, $result->status);
        $this->assertEquals('Meta API error', $result->failed_reason);
    }

    public function test_failed_can_retry(): void
    {
        $post = $this->makePost(Post::STATUS_FAILED);
        $result = PostStateMachine::for($post)->retry();
        $this->assertEquals(Post::STATUS_SENDING, $result->status);
        $this->assertEquals(1, $result->retry_count);
    }

    public function test_invalid_transition_throws_exception(): void
    {
        $post = $this->makePost(Post::STATUS_PUBLISHED);
        $this->expectException(\RuntimeException::class);
        PostStateMachine::for($post)->cancel();
    }

    public function test_retry_fails_when_max_retries_reached(): void
    {
        $post = Post::factory()->create(['status' => Post::STATUS_FAILED, 'retry_count' => 3]);
        $this->expectException(\RuntimeException::class);
        PostStateMachine::for($post)->retry();
    }
}
