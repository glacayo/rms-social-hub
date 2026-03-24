<?php

namespace Tests\Feature\Publisher;

use App\Models\FacebookPage;
use App\Models\Post;
use App\Models\PostPage;
use App\Models\User;
use App\Modules\Facebook\Contracts\FacebookApiClientInterface;
use App\Modules\Publisher\PublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Fakes\FakeFacebookApi;
use Tests\TestCase;

class PublishPostTest extends TestCase
{
    use RefreshDatabase;

    private FakeFacebookApi $fakeApi;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakeApi = new FakeFacebookApi;
        $this->app->instance(FacebookApiClientInterface::class, $this->fakeApi);
    }

    /**
     * Create a user, page, and post with a PostPage pivot record.
     *
     * @param  string  $tokenStatus  Token status for the FacebookPage
     * @param  string  $mediaType  'image' routes through FakeFacebookApi; 'none' hits raw HTTP
     */
    private function makePageAndPost(string $tokenStatus = 'active', string $mediaType = 'image'): array
    {
        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create(['token_status' => $tokenStatus]);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => Post::STATUS_SCHEDULED,
            'media_type' => $mediaType,
            'post_type' => 'post',
        ]);
        PostPage::create(['post_id' => $post->id, 'page_id' => $page->id, 'status' => 'pending']);

        return [$user, $page, $post];
    }

    public function test_publish_succeeds_and_marks_post_published(): void
    {
        // Use 'image' media_type so publishPhoto() is called on FakeFacebookApi
        [, , $post] = $this->makePageAndPost(mediaType: 'image');
        $service = app(PublishService::class);

        $service->publish($post);

        $this->assertEquals(Post::STATUS_PUBLISHED, $post->fresh()->status);
        $this->assertTrue($this->fakeApi->wasPublished());
    }

    public function test_publish_fails_when_api_returns_error(): void
    {
        // Use 'image' so the error flows through FakeFacebookApi
        [, , $post] = $this->makePageAndPost(mediaType: 'image');
        $this->fakeApi->shouldFail('Meta rate limit exceeded');

        $service = app(PublishService::class);
        $service->publish($post);

        $this->assertEquals(Post::STATUS_FAILED, $post->fresh()->status);
        $this->assertStringContainsString('Meta rate limit exceeded', $post->fresh()->failed_reason);
    }

    public function test_publish_skips_expired_page(): void
    {
        // Expired page short-circuits before hitting API — media_type doesn't matter
        [, , $post] = $this->makePageAndPost(tokenStatus: 'expired', mediaType: 'image');

        $service = app(PublishService::class);
        $service->publish($post);

        $this->assertEquals(Post::STATUS_FAILED, $post->fresh()->status);
        $this->assertFalse($this->fakeApi->wasPublished());
    }

    public function test_post_page_pivot_updated_on_success(): void
    {
        [, $page, $post] = $this->makePageAndPost(mediaType: 'image');

        app(PublishService::class)->publish($post);

        $postPage = PostPage::where('post_id', $post->id)->where('page_id', $page->id)->first();
        $this->assertEquals('published', $postPage->status);
        $this->assertNotNull($postPage->facebook_post_id);
    }

    public function test_text_only_post_calls_graph_api_directly(): void
    {
        // Text-only posts (media_type='none') hit the Graph API directly via Http::post().
        // We fake the HTTP layer so no real network call is made.
        Http::fake([
            'graph.facebook.com/*' => Http::response(['id' => 'graph-post-123'], 200),
        ]);

        [, , $post] = $this->makePageAndPost(mediaType: 'none');

        app(PublishService::class)->publish($post);

        $this->assertEquals(Post::STATUS_PUBLISHED, $post->fresh()->status);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'graph.facebook.com'));
    }
}
