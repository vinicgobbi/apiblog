<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_published_page_with_rendered_html(): void
    {
        $page = Page::factory()->published()->create(['title' => 'Sobre', 'slug' => 'sobre', 'body_markdown' => '*Olá*']);

        $this->getJson('/api/pages/sobre')
            ->assertOk()
            ->assertJsonPath('data.id', $page->id)
            ->assertJsonPath('data.title', 'Sobre')
            ->assertJsonPath('data.body_html', "<p><em>Olá</em></p>\n")
            ->assertJsonMissingPath('data.body_markdown');
    }

    public function test_show_returns_404_for_unpublished_or_unknown_page(): void
    {
        Page::factory()->create(['slug' => 'rascunho']);

        $this->getJson('/api/pages/rascunho')->assertNotFound();
        $this->getJson('/api/pages/inexistente')->assertNotFound();
    }
}
