<?php

namespace Tests\Feature\Services;

use App\Services\MarkdownRenderer;
use Tests\TestCase;

class MarkdownRendererTest extends TestCase
{
    private MarkdownRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = app(MarkdownRenderer::class);
    }

    public function test_escapes_raw_html_instead_of_rendering_it(): void
    {
        $html = $this->renderer->toHtml("<script>alert('xss')</script>\n\nTexto <img src=x onerror=alert(1)>");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_drops_unsafe_link_targets(): void
    {
        $html = $this->renderer->toHtml('[clique](javascript:alert(1)) [dados](data:text/html;base64,PHNjcmlwdD4=)');

        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('data:text/html', $html);
    }

    public function test_renders_github_flavored_tables_strikethrough_and_task_lists(): void
    {
        $html = $this->renderer->toHtml("| Coluna |\n|---|\n| valor |\n\n~~antigo~~\n\n- [x] feito");

        $this->assertStringContainsString('<td>valor</td>', $html);
        $this->assertStringContainsString('<del>antigo</del>', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
    }

    public function test_headings_receive_unique_ascii_anchor_ids(): void
    {
        $html = $this->renderer->toHtml("## Introdução\n\n## Introdução");

        $this->assertStringContainsString('<h2 id="secao-introducao">', $html);
        $this->assertStringContainsString('<h2 id="secao-introducao-1">', $html);
        $this->assertStringContainsString('href="#secao-introducao"', $html);
    }

    public function test_external_links_open_in_new_tab_and_front_links_do_not(): void
    {
        $html = $this->renderer->toHtml('[externo](https://example.com) [interno](http://localhost:4200/posts/x)');

        $this->assertStringContainsString('<a rel="noopener noreferrer" target="_blank" href="https://example.com">', $html);
        $this->assertStringContainsString('<a href="http://localhost:4200/posts/x">', $html);
    }

    public function test_reading_time_is_rounded_up_at_200_words_per_minute(): void
    {
        $this->assertSame(1, $this->renderer->readingTime('<p>'.str_repeat('palavra ', 200).'</p>'));
        $this->assertSame(2, $this->renderer->readingTime('<p>'.str_repeat('palavra ', 201).'</p>'));
    }

    public function test_reading_time_is_at_least_one_minute_for_short_text_and_zero_when_empty(): void
    {
        $this->assertSame(1, $this->renderer->readingTime('<p>Oi</p>'));
        $this->assertSame(0, $this->renderer->readingTime('<p> </p>'));
    }
}
