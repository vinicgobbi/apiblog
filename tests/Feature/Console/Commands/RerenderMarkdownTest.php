<?php

namespace Tests\Feature\Console\Commands;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RerenderMarkdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_html_of_posts_including_trashed_and_pages(): void
    {
        $post = Post::factory()->create(['body_markdown' => '**post**']);
        $trashed = Post::factory()->create(['body_markdown' => '**lixeira**']);
        $trashed->delete();
        $page = Page::factory()->create(['body_markdown' => '**página**']);
        Post::withTrashed()->update(['body_html' => null, 'reading_time' => 0]);
        Page::query()->update(['body_html' => null]);

        $this->artisan('posts:rerender')
            ->expectsOutput('2 post(s) e 1 página(s) renderizados novamente.')
            ->assertSuccessful();

        $this->assertStringContainsString('<strong>post</strong>', $post->fresh()->body_html);
        $this->assertSame(1, $post->fresh()->reading_time);
        $this->assertStringContainsString('<strong>lixeira</strong>', Post::withTrashed()->find($trashed->id)->body_html);
        $this->assertStringContainsString('<strong>página</strong>', $page->fresh()->body_html);
    }

    public function test_refreshes_cached_rss_feeds(): void
    {
        $post = Post::factory()->published()->create(['body_markdown' => 'Versão antiga']);
        $this->get('/feed');
        Post::whereKey($post->id)->update(['body_markdown' => 'Versão **nova**']);

        $this->artisan('posts:rerender')->assertSuccessful();

        $this->assertStringContainsString('<strong>nova</strong>', $this->get('/feed')->getContent());
    }

    public function test_does_not_change_updated_at(): void
    {
        $post = Post::factory()->create();
        $updatedAt = $post->fresh()->updated_at;

        $this->travel(1)->day();
        $this->artisan('posts:rerender')->assertSuccessful();

        $this->assertEquals($updatedAt, $post->fresh()->updated_at);
    }
}
