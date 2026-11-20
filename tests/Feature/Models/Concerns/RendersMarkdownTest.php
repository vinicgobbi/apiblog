<?php

namespace Tests\Feature\Models\Concerns;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RendersMarkdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_post_stores_rendered_html_and_reading_time(): void
    {
        $post = Post::factory()->create(['body_markdown' => "**negrito**\n\n".str_repeat('palavra ', 250)]);

        $this->assertStringContainsString('<strong>negrito</strong>', $post->fresh()->body_html);
        $this->assertSame(2, $post->fresh()->reading_time);
    }

    public function test_updating_markdown_renders_html_again(): void
    {
        $post = Post::factory()->create(['body_markdown' => 'Primeira versão']);

        $post->update(['body_markdown' => '*Segunda* versão']);

        $this->assertStringContainsString('<em>Segunda</em>', $post->fresh()->body_html);
    }

    public function test_updating_other_fields_keeps_existing_html(): void
    {
        $post = Post::factory()->create(['body_markdown' => 'Conteúdo']);
        Post::whereKey($post->id)->update(['body_html' => '<p>HTML preservado</p>']);
        $post = $post->fresh();

        $post->update(['title' => 'Outro título']);

        $this->assertSame('<p>HTML preservado</p>', $post->fresh()->body_html);
    }

    public function test_creating_page_stores_rendered_html(): void
    {
        $page = Page::factory()->create(['body_markdown' => '## Sobre mim']);

        $this->assertStringContainsString('<h2 id="secao-sobre-mim">', $page->fresh()->body_html);
    }

    public function test_html_provided_directly_is_replaced_by_markdown_rendering(): void
    {
        $post = Post::factory()->create([
            'body_markdown' => 'Texto seguro',
            'body_html' => '<script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $post->fresh()->body_html);
    }
}
