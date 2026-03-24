<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'content' => $this->faker->paragraph(),
            'media_paths' => null,
            'media_type' => 'none',
            'post_type' => 'post',
            'status' => Post::STATUS_DRAFT,
            'scheduled_at' => null,
            'published_at' => null,
            'failed_reason' => null,
            'retry_count' => 0,
        ];
    }

    public function scheduled(): static
    {
        return $this->state([
            'status' => Post::STATUS_SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);
    }

    public function failed(): static
    {
        return $this->state([
            'status' => Post::STATUS_FAILED,
            'failed_reason' => 'Test failure',
        ]);
    }
}
