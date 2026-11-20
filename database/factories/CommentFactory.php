<?php

namespace Database\Factories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory()->published(),
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'body' => fake()->paragraph(),
            'status' => CommentStatus::Pending,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Rejected,
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CommentStatus::Spam,
        ]);
    }
}
