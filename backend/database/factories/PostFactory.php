<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'       => User::factory(),
            'body'          => $this->faker->sentence(),
            'parent_id'     => null,
            'likes_count'   => 0,
            'replies_count' => 0,
            'reposts_count' => 0,
        ];
    }
}
