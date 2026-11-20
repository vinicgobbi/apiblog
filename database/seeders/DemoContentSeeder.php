<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Conteúdo fictício para desenvolvimento local.
 *
 * Não usa WithoutModelEvents porque os eventos dos models geram os slugs.
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoContentSeeder não roda em produção.');

            return;
        }

        $author = User::query()->first() ?? User::factory()->create();
        $categories = Category::factory()->count(4)->create();
        $tags = Tag::factory()->count(10)->create();

        $posts = Post::factory()
            ->count(15)
            ->published()
            ->for($author, 'author')
            ->sequence(fn () => ['category_id' => $categories->random()->id])
            ->create();

        Post::factory()->count(2)->draft()->for($author, 'author')->create();
        Post::factory()->scheduled()->for($author, 'author')->create();

        $posts->each(function (Post $post) use ($tags): void {
            $post->tags()->attach($tags->random(fake()->numberBetween(1, 3)));

            Comment::factory()->count(fake()->numberBetween(0, 4))->approved()->for($post)->create();
            Comment::factory()->count(fake()->numberBetween(0, 1))->pending()->for($post)->create();
        });

        Page::factory()->published()->create([
            'title' => 'Sobre',
            'slug' => 'sobre',
        ]);
    }
}
