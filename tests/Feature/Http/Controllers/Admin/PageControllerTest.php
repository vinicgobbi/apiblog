<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{string}>
     */
    public static function reservedSlugs(): array
    {
        return [
            'admin' => ['admin'],
            'busca com maiúscula' => ['Busca'],
            'posts com espaços' => [' posts '],
            'tags' => ['tags'],
            'categorias' => ['categorias'],
        ];
    }

    #[DataProvider('reservedSlugs')]
    public function test_store_rejects_reserved_slug(string $slug): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Página', 'slug' => $slug, 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Este endereço é usado pelo blog. Escolha outro slug']);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_store_rejects_title_that_generates_reserved_slug(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Busca', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Este endereço é usado pelo blog. Escolha outro slug']);
    }

    public function test_update_rejects_reserved_slug(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Sobre', 'slug' => 'Admin', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $this->assertSame('sobre', $page->fresh()->slug);
    }

    public function test_update_with_empty_slug_rejects_title_that_generates_reserved_slug(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Posts', 'slug' => null, 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_without_slug_keeps_current_slug_even_if_title_matches_reserved_word(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Tags', 'body_markdown' => 'Texto'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'sobre');
    }

    public function test_slugs_that_only_contain_a_reserved_word_are_accepted(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Como usar a busca', 'body_markdown' => 'Texto'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'como-usar-a-busca');
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
