<?php

namespace Tests\Feature\Models;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_scope_returns_only_posts_published_in_the_past(): void
    {
        $published = Post::factory()->published()->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->published()->create()->delete();

        $this->assertEquals([$published->id], Post::published()->pluck('id')->all());
    }

    public function test_post_becomes_public_once_its_scheduled_date_arrives(): void
    {
        $post = Post::factory()->published()->create(['published_at' => now()->addHour()]);

        $this->assertFalse($post->isPublished());
        $this->assertSame(0, Post::published()->count());

        $this->travel(2)->hours();

        $this->assertTrue($post->fresh()->isPublished());
        $this->assertSame(1, Post::published()->count());
    }

    public function test_post_published_at_the_current_second_is_already_published(): void
    {
        $this->freezeSecond();
        $post = Post::factory()->published()->create(['published_at' => now()]);

        $this->assertTrue($post->isPublished());
        $this->assertSame(1, Post::published()->count());
    }

    public function test_draft_with_publication_date_is_not_published(): void
    {
        $post = Post::factory()->draft()->create(['published_at' => now()->subDay()]);

        $this->assertFalse($post->isPublished());
        $this->assertSame(0, Post::published()->count());
    }
}
