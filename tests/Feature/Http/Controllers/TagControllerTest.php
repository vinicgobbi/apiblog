<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_tags_with_published_posts_and_their_count(): void
    {
        $php = Tag::factory()->create(['name' => 'php']);
        $css = Tag::factory()->create(['name' => 'css']);
        $draftOnly = Tag::factory()->create(['name' => 'rascunho']);
        Tag::factory()->create(['name' => 'vazia']);
        Post::factory()->count(2)->published()->create()->each(fn (Post $post) => $post->tags()->attach($php));
        Post::factory()->published()->create()->tags()->attach($css);
        Post::factory()->draft()->create()->tags()->attach([$php->id, $draftOnly->id]);

        $this->getJson('/api/tags')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['css', 'php'])
            ->assertJsonPath('data.*.posts_count', [1, 2]);
    }

    public function test_show_returns_tag_by_slug_with_published_post_count(): void
    {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        Post::factory()->published()->create()->tags()->attach($tag);
        Post::factory()->draft()->create()->tags()->attach($tag);

        $this->getJson('/api/tags/php')
            ->assertOk()
            ->assertExactJson(['data' => ['id' => $tag->id, 'name' => 'PHP', 'slug' => 'php', 'posts_count' => 1]]);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/tags/inexistente')->assertNotFound();
    }
}
