<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkdownPreviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_401_without_token(): void
    {
        $this->postJson('/api/admin/markdown/preview', ['markdown' => '# Oi'])->assertUnauthorized();
    }

    public function test_preview_is_identical_to_the_html_saved_for_the_post(): void
    {
        $markdown = "## Seção\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n<script>alert(1)</script> [link](https://example.com)";
        $post = Post::factory()->create(['body_markdown' => $markdown]);

        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/admin/markdown/preview', ['markdown' => $markdown])
            ->assertOk()
            ->assertExactJson(['html' => $post->fresh()->body_html]);
    }

    public function test_empty_editor_returns_empty_html(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/admin/markdown/preview', ['markdown' => ''])
            ->assertOk()
            ->assertExactJson(['html' => '']);
    }

    public function test_missing_markdown_field_returns_422(): void
    {
        $this->actingAs(User::factory()->create(), 'api')
            ->postJson('/api/admin/markdown/preview', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['markdown' => 'O campo markdown é obrigatório']);
    }
}
