<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => rtrim(fake()->unique()->sentence(6), '.'),
            'excerpt' => fake()->paragraph(),
            'body_markdown' => $this->markdownBody(),
            'status' => PostStatus::Draft,
            'published_at' => null,
        ];
    }

    /**
     * Post publicado, com data de publicação no passado.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('-1 year', '-1 hour'),
        ]);
    }

    /**
     * Post publicado com data futura (agendado).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Published,
            'published_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PostStatus::Draft,
            'published_at' => null,
        ]);
    }

    private function markdownBody(): string
    {
        $sections = collect(range(1, 3))->map(fn (int $section): string => '## '.rtrim(fake()->sentence(4), '.')
            ."\n\n".implode("\n\n", fake()->paragraphs(2))
        );

        return $sections->implode("\n\n");
    }
}
