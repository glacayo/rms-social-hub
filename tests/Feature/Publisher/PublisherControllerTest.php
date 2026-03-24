<?php

namespace Tests\Feature\Publisher;

use App\Models\FacebookPage;
use App\Models\User;
use App\Models\UserPagePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_editor_can_access_publisher_create(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user);

        $response = $this->get(route('publisher.create'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('Publisher/Create', false));
    }

    public function test_unauthenticated_user_redirected_from_publisher(): void
    {
        $response = $this->get(route('publisher.create'));
        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_draft_post_without_schedule(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create();
        // Assign page to editor
        UserPagePermission::create([
            'user_id' => $user->id,
            'page_id' => $page->id,
            'assigned_by' => null,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('publisher.store'), [
            'content' => 'Test post content',
            'page_ids' => [$page->id],
            'post_type' => 'post',
            'media_type' => 'none',
        ]);

        $response->assertRedirect(route('publisher.index'));
        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'content' => 'Test post content',
            'status' => 'draft',
        ]);
    }

    public function test_store_creates_scheduled_post_with_future_date(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $page = FacebookPage::factory()->create();
        UserPagePermission::create(['user_id' => $user->id, 'page_id' => $page->id, 'assigned_by' => null]);

        $this->actingAs($user);

        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        $this->post(route('publisher.store'), [
            'content' => 'Scheduled post',
            'page_ids' => [$page->id],
            'post_type' => 'post',
            'media_type' => 'none',
            'scheduled_at' => $scheduledAt,
        ]);

        $this->assertDatabaseHas('posts', [
            'content' => 'Scheduled post',
            'status' => 'scheduled',
        ]);
    }

    public function test_editor_cannot_access_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'editor']);
        $this->actingAs($user);

        $response = $this->get(route('admin.pages.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $response = $this->get(route('admin.pages.index'));
        $response->assertOk();
    }
}
