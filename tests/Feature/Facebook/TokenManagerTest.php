<?php

namespace Tests\Feature\Facebook;

use App\Models\FacebookPage;
use App\Models\Post;
use App\Models\PostPage;
use App\Models\User;
use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Modules\Facebook\Services\TokenManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeFacebookApi;
use Tests\TestCase;

class TokenManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_updates_token_and_marks_active(): void
    {
        $fakeApi = new FakeFacebookApi;
        $this->app->instance(FacebookApiClientInterface::class, $fakeApi);

        $page = FacebookPage::factory()->expiring()->create();
        $manager = app(TokenManager::class);

        $manager->refresh($page);

        $updated = $page->fresh();
        $this->assertEquals('active', $updated->token_status);
        $this->assertStringStartsWith('fake-refreshed-token-', $updated->access_token);
    }

    public function test_refresh_marks_expired_on_api_failure(): void
    {
        $fakeApi = (new FakeFacebookApi)->shouldFail('Token expired on Meta');
        $this->app->instance(FacebookApiClientInterface::class, $fakeApi);

        $page = FacebookPage::factory()->expiring()->create();
        $manager = app(TokenManager::class);

        $manager->refresh($page);

        $this->assertEquals('expired', $page->fresh()->token_status);
    }

    public function test_mark_expired_cancels_scheduled_posts(): void
    {
        $fakeApi = new FakeFacebookApi;
        $this->app->instance(FacebookApiClientInterface::class, $fakeApi);

        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);
        PostPage::create(['post_id' => $post->id, 'page_id' => $page->id, 'status' => 'pending']);

        $manager = app(TokenManager::class);
        $manager->markExpired($page);

        $this->assertEquals(Post::STATUS_CANCELLED, $post->fresh()->status);
    }
}
