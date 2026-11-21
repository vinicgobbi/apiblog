<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_pages_in_creation_order_without_body(): void
    {
        $contact = Page::factory()->published()->create(['title' => 'Contato', 'slug' => 'contato', 'icon' => 'envelope', 'created_at' => now()->subDay()]);
        Page::factory()->create(['title' => 'Rascunho']);
        $about = Page::factory()->published()->create(['title' => 'Sobre', 'slug' => 'sobre', 'icon' => 'person']);

        $this->getJson('/api/pages')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    ['id' => $contact->id, 'title' => 'Contato', 'slug' => 'contato', 'icon' => 'envelope'],
                    ['id' => $about->id, 'title' => 'Sobre', 'slug' => 'sobre', 'icon' => 'person'],
                ],
            ]);
    }

    public function test_index_returns_empty_list_when_no_page_is_published(): void
    {
        Page::factory()->create();

        $this->getJson('/api/pages')->assertOk()->assertExactJson(['data' => []]);
    }

    public function test_show_returns_published_page_with_rendered_html(): void
    {
        $page = Page::factory()->published()->create(['title' => 'Sobre', 'slug' => 'sobre', 'icon' => 'person', 'body_markdown' => '*Olá*']);

        $this->getJson('/api/pages/sobre')
            ->assertOk()
            ->assertJsonPath('data.id', $page->id)
            ->assertJsonPath('data.title', 'Sobre')
            ->assertJsonPath('data.icon', 'person')
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
