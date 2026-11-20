<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_every_admin_page_endpoint_returns_401_without_token(): void
    {
        $page = Page::factory()->create();

        $this->getJson('/api/admin/pages')->assertUnauthorized();
        $this->postJson('/api/admin/pages', [])->assertUnauthorized();
        $this->getJson("/api/admin/pages/{$page->id}")->assertUnauthorized();
        $this->putJson("/api/admin/pages/{$page->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/pages/{$page->id}")->assertUnauthorized();
    }

    public function test_index_lists_published_and_unpublished_pages_without_body(): void
    {
        $draft = Page::factory()->create(['title' => 'Contato']);
        $published = Page::factory()->published()->create(['title' => 'Sobre']);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/pages')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$draft->id, $published->id])
            ->assertJsonPath('data.*.is_published', [false, true])
            ->assertJsonMissingPath('data.0.body_markdown');
    }

    public function test_show_returns_unpublished_page_with_markdown(): void
    {
        $page = Page::factory()->create(['body_markdown' => '## Rascunho']);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/pages/{$page->id}")
            ->assertOk()
            ->assertJsonPath('data.body_markdown', '## Rascunho')
            ->assertJsonPath('data.is_published', false);
    }

    public function test_store_creates_page_with_rendered_html(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Sobre mim', 'body_markdown' => '**Oi**', 'is_published' => true])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'sobre-mim')
            ->assertJsonPath('data.body_html', "<p><strong>Oi</strong></p>\n");

        $this->getJson('/api/pages/sobre-mim')->assertOk();
    }

    public function test_store_with_invalid_data_returns_422(): void
    {
        Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['slug' => 'Sobre', 'is_published' => 'talvez'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'O título é obrigatório',
                'body_markdown' => 'O conteúdo é obrigatório',
                'slug' => 'Já existe uma página com este slug',
                'is_published' => 'O campo publicado deve ser verdadeiro ou falso',
            ]);
    }

    public function test_update_can_unpublish_page(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Sobre', 'slug' => 'sobre', 'body_markdown' => 'Texto', 'is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        $this->getJson('/api/pages/sobre')->assertNotFound();
    }

    public function test_destroy_deletes_page(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/pages/{$page->id}")->assertNoContent();

        $this->assertModelMissing($page);
    }
}
