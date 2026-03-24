<?php

namespace Database\Factories;

use App\Models\FacebookPage;
use Illuminate\Database\Eloquent\Factories\Factory;

class FacebookPageFactory extends Factory
{
    protected $model = FacebookPage::class;

    public function definition(): array
    {
        return [
            'page_id' => $this->faker->numerify('##########'),
            'page_name' => $this->faker->company().' Page',
            // The model encrypts on set via setAccessTokenAttribute mutator
            'access_token' => 'fake-token-'.$this->faker->uuid(),
            'token_expires_at' => now()->addDays(60),
            'token_status' => 'active',
            'linked_by_user_id' => null,
        ];
    }

    public function expiring(): static
    {
        return $this->state([
            'token_expires_at' => now()->addDays(3),
            'token_status' => 'expiring',
        ]);
    }

    public function expired(): static
    {
        return $this->state([
            'token_expires_at' => now()->subDay(),
            'token_status' => 'expired',
        ]);
    }
}
