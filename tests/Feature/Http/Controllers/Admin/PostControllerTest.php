<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_every_admin_post_endpoint_returns_401_without_token(): void
    {
        $post = Post::factory()->create();

        $this->getJson('/api/admin/posts')->assertUnauthorized();
        $this->postJson('/api/admin/posts', [])->assertUnauthorized();
        $this->getJson("/api/admin/posts/{$post->id}")->assertUnauthorized();
        $this->putJson("/api/admin/posts/{$post->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/posts/{$post->id}")->assertUnauthorized();
    }

    public function test_index_lists_drafts_scheduled_and_published_posts_with_their_state(): void
    {
        $draft = Post::factory()->draft()->create(['created_at' => now()->subDays(3)]);
        $scheduled = Post::factory()->scheduled()->create(['created_at' => now()->subDays(2)]);
        $published = Post::factory()->published()->create(['created_at' => now()->subDay()]);
        Post::factory()->published()->create()->delete();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/posts')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$published->id, $scheduled->id, $draft->id])
            ->assertJsonPath('data.*.state', ['published', 'scheduled', 'draft'])
            ->assertJsonMissingPath('data.0.body_markdown');
    }

    public function test_index_filters_by_state(): void
    {
        Post::factory()->draft()->create();
        $scheduled = Post::factory()->scheduled()->create();
        Post::factory()->published()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/posts?state=scheduled')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$scheduled->id]);
    }

    public function test_index_searches_by_title(): void
    {
        $match = Post::factory()->draft()->create(['title' => 'Rascunho sobre Angular']);
        Post::factory()->draft()->create(['title' => 'Outro assunto', 'excerpt' => 'nada']);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/posts?q=angular')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$match->id]);
    }

    public function test_show_returns_draft_with_markdown_and_html(): void
    {
        $post = Post::factory()->draft()->create(['body_markdown' => '**rascunho**']);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/admin/posts/{$post->id}")
            ->assertOk()
            ->assertJsonPath('data.body_markdown', '**rascunho**')
            ->assertJsonPath('data.body_html', "<p><strong>rascunho</strong></p>\n")
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_store_creates_draft_authored_by_the_admin_and_returns_201(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/posts', [
            'title' => 'Novo post',
            'excerpt' => 'Resumo',
            'body_markdown' => '# Olá **mundo**',
            'category_id' => $category->id,
            'status' => 'draft',
            'user_id' => User::factory()->create()->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.slug', 'novo-post')
            ->assertJsonPath('data.state', 'draft')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.author.id', $this->admin->id);
        $post = Post::sole();
        $this->assertTrue($post->author->is($this->admin));
        $this->assertStringContainsString('<strong>mundo</strong>', $post->body_html);
        $this->assertNull($post->published_at);
    }

    public function test_publishing_without_date_publishes_immediately(): void
    {
        $this->freezeSecond();

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', ['title' => 'Já', 'body_markdown' => 'Texto', 'status' => 'published'])
            ->assertCreated()
            ->assertJsonPath('data.state', 'published');

        $this->assertEquals(now(), Post::sole()->published_at);
        $this->getJson('/api/posts/ja')->assertOk();
    }

    public function test_publishing_with_future_date_schedules_the_post(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', [
                'title' => 'Futuro',
                'body_markdown' => 'Texto',
                'status' => 'published',
                'published_at' => now()->addWeek()->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('data.state', 'scheduled');

        $this->getJson('/api/posts/futuro')->assertNotFound();
    }

    public function test_store_attaches_existing_tags_and_creates_new_ones_by_name(): void
    {
        $existing = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', [
                'title' => 'Com tags',
                'body_markdown' => 'Texto',
                'status' => 'draft',
                'tags' => ['php', 'Laravel 13', 'PHP', '  ', '!!!'],
            ])
            ->assertCreated()
            ->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseCount('tags', 2);
        $this->assertEqualsCanonicalizing([$existing->id, Tag::where('slug', 'laravel-13')->value('id')], Post::sole()->tags()->pluck('tags.id')->all());
    }

    public function test_store_with_invalid_data_returns_422_with_messages(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', ['status' => 'arquivado', 'category_id' => 999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title' => 'O título é obrigatório',
                'body_markdown' => 'O conteúdo é obrigatório',
                'status' => 'O status deve ser "draft" ou "published"',
                'category_id' => 'A categoria informada não existe',
            ]);
    }

    public function test_slug_already_in_use_returns_422_even_with_different_formatting(): void
    {
        Post::factory()->create(['slug' => 'meu-post']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', ['title' => 'X', 'slug' => 'Meu Post', 'body_markdown' => 'Texto', 'status' => 'draft'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Já existe um post com este slug']);
    }

    public function test_slug_of_trashed_post_is_still_reserved(): void
    {
        Post::factory()->create(['slug' => 'removido'])->delete();

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', ['title' => 'X', 'slug' => 'removido', 'body_markdown' => 'Texto', 'status' => 'draft'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    public function test_update_changes_fields_rerenders_html_and_replaces_tags(): void
    {
        $post = Post::factory()->draft()->create(['title' => 'Antigo', 'slug' => 'antigo']);
        $post->tags()->attach(Tag::factory()->create(['name' => 'Velha']));

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/posts/{$post->id}", [
                'title' => 'Atualizado',
                'slug' => 'antigo',
                'body_markdown' => '*novo*',
                'status' => 'draft',
                'tags' => ['Nova'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Atualizado')
            ->assertJsonPath('data.slug', 'antigo')
            ->assertJsonPath('data.tags.*.name', ['Nova']);

        $this->assertStringContainsString('<em>novo</em>', $post->fresh()->body_html);
    }

    public function test_update_without_tags_field_keeps_current_tags(): void
    {
        $post = Post::factory()->draft()->create();
        $post->tags()->attach($tag = Tag::factory()->create());

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/posts/{$post->id}", ['title' => 'T', 'body_markdown' => 'B', 'status' => 'draft'])
            ->assertOk();

        $this->assertEquals([$tag->id], $post->tags()->pluck('tags.id')->all());
    }

    public function test_update_with_empty_tags_removes_all_tags(): void
    {
        $post = Post::factory()->draft()->create();
        $post->tags()->attach(Tag::factory()->create());

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/posts/{$post->id}", ['title' => 'T', 'body_markdown' => 'B', 'status' => 'draft', 'tags' => []])
            ->assertOk()
            ->assertJsonCount(0, 'data.tags');
    }

    public function test_changing_tags_refreshes_the_cached_tag_feed(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Post do feed']);
        Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $this->assertStringNotContainsString('Post do feed', $this->get('/feed/tags/php')->getContent());

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/posts/{$post->id}", ['title' => 'Post do feed', 'body_markdown' => 'B', 'status' => 'published', 'tags' => ['PHP']])
            ->assertOk();

        $this->assertStringContainsString('Post do feed', $this->get('/feed/tags/php')->getContent());
    }

    public function test_destroy_moves_post_to_trash_and_returns_204(): void
    {
        $post = Post::factory()->published()->create(['slug' => 'removivel']);

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/posts/{$post->id}")->assertNoContent();

        $this->assertSoftDeleted($post);
        $this->getJson('/api/posts/removivel')->assertNotFound();
    }

    public function test_status_value_is_stored_as_enum(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/posts', ['title' => 'Enum', 'body_markdown' => 'B', 'status' => 'published'])
            ->assertCreated();

        $this->assertSame(PostStatus::Published, Post::sole()->status);
    }
}
