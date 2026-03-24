<?php

namespace Tests\Unit\Modules\Publisher;

use App\Jobs\PublishPostJob;
use App\Models\FacebookPage;
use App\Models\Post;
use App\Models\PostPage;
use App\Models\User;
use App\Modules\Publisher\SchedulerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SchedulerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_jobs_for_due_posts(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute(), // already due
        ]);
        PostPage::create(['post_id' => $post->id, 'page_id' => $page->id, 'status' => 'pending']);

        $service = new SchedulerService;
        $count = $service->dispatchDuePosts();

        $this->assertEquals(1, $count);
        Queue::assertPushed(PublishPostJob::class);
    }

    public function test_does_not_dispatch_future_posts(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(), // future
        ]);
        PostPage::create(['post_id' => $post->id, 'page_id' => $page->id, 'status' => 'pending']);

        $count = (new SchedulerService)->dispatchDuePosts();

        $this->assertEquals(0, $count);
        Queue::assertNotPushed(PublishPostJob::class);
    }

    public function test_cancels_posts_with_no_target_pages(): void
    {
        Queue::fake();

        $user = User::factory()->create(['role' => 'editor']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->subMinute(),
            // No PostPage records
        ]);

        (new SchedulerService)->dispatchDuePosts();

        $this->assertEquals(Post::STATUS_CANCELLED, $post->fresh()->status);
        Queue::assertNotPushed(PublishPostJob::class);
    }
}
