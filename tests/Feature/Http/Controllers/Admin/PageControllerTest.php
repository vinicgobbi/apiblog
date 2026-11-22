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
        $this->putJson('/api/admin/pages/order', ['ids' => [$page->id]])->assertUnauthorized();
    }

    public function test_index_lists_published_and_unpublished_pages_in_menu_order_without_body(): void
    {
        $published = Page::factory()->published()->create(['title' => 'Sobre', 'position' => 2]);
        $draft = Page::factory()->create(['title' => 'Contato', 'position' => 1]);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/pages')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$draft->id, $published->id])
            ->assertJsonPath('data.*.position', [1, 2])
            ->assertJsonPath('data.*.is_published', [false, true])
            ->assertJsonPath('data.*.icon', [$draft->icon, $published->icon])
            ->assertJsonMissingPath('data.0.body_markdown');
    }

    public function test_reorder_sets_the_menu_order_without_touching_updated_at(): void
    {
        [$about, $contact, $projects] = Page::factory()->count(3)->create(['updated_at' => now()->subWeek()]);
        $updatedAt = $about->fresh()->updated_at;

        $this->actingAs($this->admin, 'api')
            ->putJson('/api/admin/pages/order', ['ids' => [$projects->id, $about->id, $contact->id]])
            ->assertOk()
            ->assertJsonPath('data.*.id', [$projects->id, $about->id, $contact->id])
            ->assertJsonPath('data.*.position', [1, 2, 3]);

        $this->assertSame(2, $about->fresh()->position);
        $this->assertEquals($updatedAt, $about->fresh()->updated_at);
    }

    public function test_update_keeps_the_page_position(): void
    {
        Page::factory()->create();
        $page = Page::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", [
                'title' => 'Novo título',
                'icon' => 'person',
                'body_markdown' => 'Texto',
                'position' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.position', 2);
    }

    /**
     * @return array<string, array{0: callable(list<int>): mixed, 1: string}>
     */
    public static function invalidOrderProvider(): array
    {
        return [
            'sem ids' => [fn (array $ids) => null, 'ids'],
            'lista vazia' => [fn (array $ids) => [], 'ids'],
            'faltando uma página' => [fn (array $ids) => array_slice($ids, 0, 1), 'ids'],
            'página inexistente' => [fn (array $ids) => [...$ids, 999], 'ids'],
            'página repetida' => [fn (array $ids) => [$ids[0], $ids[0], $ids[1]], 'ids.1'],
            'id não numérico' => [fn (array $ids) => ['abc', $ids[1]], 'ids.0'],
        ];
    }

    /**
     * @param  callable(list<int>): mixed  $ids
     */
    #[DataProvider('invalidOrderProvider')]
    public function test_reorder_rejects_a_list_that_does_not_match_the_pages(callable $ids, string $errorKey): void
    {
        $pages = Page::factory()->count(2)->create();

        $this->actingAs($this->admin, 'api')
            ->putJson('/api/admin/pages/order', ['ids' => $ids($pages->modelKeys())])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor($errorKey);

        $this->assertEquals([1, 2], $pages->map->fresh()->pluck('position')->all());
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
            ->postJson('/api/admin/pages', ['title' => 'Sobre mim', 'icon' => 'person', 'body_markdown' => '**Oi**', 'is_published' => true])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'sobre-mim')
            ->assertJsonPath('data.icon', 'person')
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
                'icon' => 'Escolha um ícone para a página',
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
            ->postJson('/api/admin/pages', ['title' => 'Página', 'slug' => $slug, 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Este endereço é usado pelo blog. Escolha outro slug']);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_store_rejects_title_that_generates_reserved_slug(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Busca', 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Este endereço é usado pelo blog. Escolha outro slug']);
    }

    public function test_update_rejects_reserved_slug(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Sobre', 'slug' => 'Admin', 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $this->assertSame('sobre', $page->fresh()->slug);
    }

    public function test_update_with_empty_slug_rejects_title_that_generates_reserved_slug(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Posts', 'slug' => null, 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_without_slug_keeps_current_slug_even_if_title_matches_reserved_word(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Tags', 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertOk()
            ->assertJsonPath('data.slug', 'sobre');
    }

    public function test_slugs_that_only_contain_a_reserved_word_are_accepted(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Como usar a busca', 'icon' => 'person', 'body_markdown' => 'Texto'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'como-usar-a-busca');
    }

    public function test_update_can_unpublish_page(): void
    {
        $page = Page::factory()->published()->create(['slug' => 'sobre']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Sobre', 'slug' => 'sobre', 'icon' => 'person', 'body_markdown' => 'Texto', 'is_published' => false])
            ->assertOk()
            ->assertJsonPath('data.is_published', false);

        $this->getJson('/api/pages/sobre')->assertNotFound();
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidIcons(): array
    {
        return [
            'com prefixo e espaço' => ['bi bi-person'],
            'classe extra' => ['person text-danger'],
            'maiúsculas' => ['Person'],
            'hífen no fim' => ['person-'],
            'não string' => [['person']],
        ];
    }

    #[DataProvider('invalidIcons')]
    public function test_store_rejects_icon_that_is_not_a_bootstrap_icon_name(mixed $icon): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/pages', ['title' => 'Página', 'icon' => $icon, 'body_markdown' => 'Texto'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['icon']);

        $this->assertDatabaseCount('pages', 0);
    }

    public function test_update_changes_icon(): void
    {
        $page = Page::factory()->create(['slug' => 'sobre', 'icon' => 'person']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/pages/{$page->id}", ['title' => 'Sobre', 'icon' => 'person-badge', 'body_markdown' => 'Texto'])
            ->assertOk()
            ->assertJsonPath('data.icon', 'person-badge');

        $this->assertSame('person-badge', $page->fresh()->icon);
    }

    public function test_destroy_deletes_page(): void
    {
        $page = Page::factory()->create();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/pages/{$page->id}")->assertNoContent();

        $this->assertModelMissing($page);
    }
}
