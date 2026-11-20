<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_posts_newest_first(): void
    {
        $older = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        $newer = Post::factory()->published()->create(['published_at' => now()->subDay()]);
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->published()->create()->delete();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newer->id, $older->id]);
    }

    public function test_index_returns_summary_without_body_or_author_email(): void
    {
        $author = User::factory()->create(['name' => 'Autora']);
        $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        $post = Post::factory()->published()->for($author, 'author')->for($category)->create([
            'title' => 'Primeiro post',
            'cover_image_path' => 'media/capa.jpg',
            'body_markdown' => 'Corpo do post',
        ]);
        $post->tags()->attach($tag);

        $response = $this->getJson('/api/posts')->assertOk();

        $response->assertJsonPath('data.0.title', 'Primeiro post')
            ->assertJsonPath('data.0.slug', 'primeiro-post')
            ->assertJsonPath('data.0.author', ['name' => 'Autora'])
            ->assertJsonPath('data.0.category.slug', 'laravel')
            ->assertJsonPath('data.0.tags.0.slug', 'php')
            ->assertJsonPath('data.0.cover_image_url', asset('storage/media/capa.jpg'))
            ->assertJsonMissingPath('data.0.body_html')
            ->assertJsonMissingPath('data.0.body_markdown');
        $this->assertStringNotContainsString($author->email, $response->getContent());
    }

    public function test_index_filters_by_tag_slug(): void
    {
        $tag = Tag::factory()->create(['slug' => 'php']);
        $tagged = Post::factory()->published()->create();
        $tagged->tags()->attach($tag);
        Post::factory()->published()->create();

        $this->getJson('/api/posts?tag=php')->assertOk()->assertJsonPath('data.*.id', [$tagged->id]);
    }

    public function test_index_filters_by_category_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'laravel']);
        $inCategory = Post::factory()->published()->for($category)->create();
        Post::factory()->published()->create();

        $this->getJson('/api/posts?category=laravel')->assertOk()->assertJsonPath('data.*.id', [$inCategory->id]);
    }

    public function test_index_with_unknown_filter_returns_empty_list(): void
    {
        Post::factory()->published()->create();

        $this->getJson('/api/posts?tag=inexistente')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_search_matches_title_or_excerpt(): void
    {
        $byTitle = Post::factory()->published()->create(['title' => 'Aprendendo Angular', 'excerpt' => 'Resumo']);
        $byExcerpt = Post::factory()->published()->create(['title' => 'Outro', 'excerpt' => 'Um guia de Angular']);
        Post::factory()->published()->create(['title' => 'Laravel', 'excerpt' => 'Backend']);

        $this->getJson('/api/posts?q=angular')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $byTitle->id])
            ->assertJsonFragment(['id' => $byExcerpt->id]);
    }

    public function test_search_treats_like_wildcards_as_literal_text(): void
    {
        $match = Post::factory()->published()->create(['title' => 'Desconto de 50% hoje', 'excerpt' => '']);
        Post::factory()->published()->create(['title' => 'Desconto de 500 reais', 'excerpt' => '']);
        Post::factory()->published()->create(['title' => 'nome_de_variavel', 'excerpt' => '']);
        Post::factory()->published()->create(['title' => 'nomeXdeXvariavel', 'excerpt' => '']);

        $this->getJson('/api/posts?q='.urlencode('50%'))->assertOk()->assertJsonPath('data.*.id', [$match->id]);
        $this->getJson('/api/posts?q=nome_de')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_index_paginates_with_laravel_meta(): void
    {
        Post::factory()->count(3)->published()->create();

        $this->getJson('/api/posts?per_page=2&page=2')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_pagination_links_keep_the_filters(): void
    {
        $tag = Tag::factory()->create(['slug' => 'php']);
        Post::factory()->count(3)->published()->create()->each(fn (Post $post) => $post->tags()->attach($tag));

        $next = $this->getJson('/api/posts?tag=php&per_page=2')->assertOk()->json('links.next');

        $this->assertStringContainsString('tag=php', $next);
    }

    public function test_index_rejects_per_page_above_limit_with_422(): void
    {
        $this->getJson('/api/posts?per_page=51')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['per_page' => 'A quantidade por página deve ser de no máximo 50']);
    }

    public function test_show_returns_published_post_with_rendered_html(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Meu post', 'body_markdown' => '**Olá**']);

        $this->getJson('/api/posts/meu-post')
            ->assertOk()
            ->assertJsonPath('data.id', $post->id)
            ->assertJsonPath('data.body_html', "<p><strong>Olá</strong></p>\n")
            ->assertJsonMissingPath('data.body_markdown');
    }

    public function test_show_returns_404_for_draft_scheduled_trashed_or_unknown_post(): void
    {
        Post::factory()->draft()->create(['title' => 'Rascunho']);
        Post::factory()->scheduled()->create(['title' => 'Agendado']);
        Post::factory()->published()->create(['title' => 'Removido'])->delete();

        $this->getJson('/api/posts/rascunho')->assertNotFound();
        $this->getJson('/api/posts/agendado')->assertNotFound();
        $this->getJson('/api/posts/removido')->assertNotFound();
        $this->getJson('/api/posts/inexistente')->assertNotFound();
    }
}
