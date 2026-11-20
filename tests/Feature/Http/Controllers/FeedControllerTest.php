<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use SimpleXMLElement;
use Tests\TestCase;

class FeedControllerTest extends TestCase
{
    use RefreshDatabase;

    private const string CONTENT_NS = 'http://purl.org/rss/1.0/modules/content/';

    private const string DC_NS = 'http://purl.org/dc/elements/1.1/';

    private const string ATOM_NS = 'http://www.w3.org/2005/Atom';

    protected function setUp(): void
    {
        parent::setUp();

        config(['blog.title' => 'Meu Blog', 'blog.description' => 'Sobre código', 'blog.front_url' => 'https://blog.test']);
    }

    public function test_feed_returns_rss_document_with_channel_metadata(): void
    {
        $response = $this->get('/feed')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $rss = $this->parse($response);
        $this->assertSame('2.0', (string) $rss['version']);
        $this->assertSame('Meu Blog', (string) $rss->channel->title);
        $this->assertSame('https://blog.test/', (string) $rss->channel->link);
        $this->assertSame('Sobre código', (string) $rss->channel->description);
        $this->assertSame('pt-BR', (string) $rss->channel->language);
        $this->assertSame(url('/feed'), (string) $rss->channel->children(self::ATOM_NS)->link->attributes()['href']);
    }

    public function test_feed_lists_only_published_posts_newest_first(): void
    {
        Post::factory()->published()->create(['title' => 'Antigo', 'published_at' => now()->subDays(2)]);
        Post::factory()->published()->create(['title' => 'Novo', 'published_at' => now()->subDay()]);
        Post::factory()->draft()->create(['title' => 'Rascunho']);
        Post::factory()->scheduled()->create(['title' => 'Agendado']);
        Post::factory()->published()->create(['title' => 'Removido'])->delete();

        $this->assertSame(['Novo', 'Antigo'], $this->itemTitles($this->get('/feed')));
    }

    public function test_feed_is_limited_to_configured_number_of_items(): void
    {
        config(['blog.feed.items' => 3]);
        Post::factory()->count(5)->published()->create();

        $this->assertCount(3, $this->itemTitles($this->get('/feed')));
    }

    public function test_item_contains_front_link_author_categories_and_full_html(): void
    {
        $author = User::factory()->create(['name' => 'Autora']);
        $category = Category::factory()->create(['name' => 'Laravel']);
        $tag = Tag::factory()->create(['name' => 'PHP']);
        $post = Post::factory()->published()->for($author, 'author')->for($category)->create([
            'title' => 'Meu post',
            'excerpt' => 'Resumo do post',
            'body_markdown' => '**Conteúdo** completo',
            'published_at' => '2026-11-20 10:30:00',
        ]);
        $post->tags()->attach($tag);

        $item = $this->parse($this->get('/feed'))->channel->item[0];

        $this->assertSame('https://blog.test/posts/meu-post', (string) $item->link);
        $this->assertSame('https://blog.test/posts/meu-post', (string) $item->guid);
        $this->assertSame('true', (string) $item->guid['isPermaLink']);
        $this->assertSame($post->published_at->toRssString(), (string) $item->pubDate);
        $this->assertSame('Autora', (string) $item->children(self::DC_NS)->creator);
        $this->assertEqualsCanonicalizing(['Laravel', 'PHP'], array_map('strval', iterator_to_array($item->category, false)));
        $this->assertSame('Resumo do post', (string) $item->description);
        $this->assertStringContainsString('<strong>Conteúdo</strong> completo', (string) $item->children(self::CONTENT_NS)->encoded);
    }

    public function test_description_falls_back_to_plain_text_from_body_when_there_is_no_excerpt(): void
    {
        Post::factory()->published()->create(['excerpt' => null, 'body_markdown' => '## Título'."\n\n".'Texto com **ênfase** & símbolos']);

        $description = (string) $this->parse($this->get('/feed'))->channel->item[0]->description;

        $this->assertStringContainsString('Texto com ênfase & símbolos', $description);
        $this->assertStringNotContainsString('<', $description);
    }

    public function test_special_characters_in_titles_produce_valid_xml(): void
    {
        Post::factory()->published()->create(['title' => 'Tom & Jerry <script> "aspas"']);

        $this->assertSame(['Tom & Jerry <script> "aspas"'], $this->itemTitles($this->get('/feed')));
    }

    public function test_tag_feed_lists_only_posts_with_that_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        Post::factory()->published()->create(['title' => 'Com tag'])->tags()->attach($tag);
        Post::factory()->published()->create(['title' => 'Sem tag']);

        $response = $this->get('/feed/tags/php')->assertOk();

        $this->assertSame(['Com tag'], $this->itemTitles($response));
        $this->assertSame('Meu Blog — PHP', (string) $this->parse($response)->channel->title);
        $this->assertSame('https://blog.test/tags/php', (string) $this->parse($response)->channel->link);
    }

    public function test_category_feed_lists_only_posts_in_that_category(): void
    {
        $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);
        Post::factory()->published()->for($category)->create(['title' => 'Na categoria']);
        Post::factory()->published()->create(['title' => 'Fora']);

        $response = $this->get('/feed/categories/laravel')->assertOk();

        $this->assertSame(['Na categoria'], $this->itemTitles($response));
        $this->assertSame('https://blog.test/categorias/laravel', (string) $this->parse($response)->channel->link);
    }

    public function test_unknown_tag_or_category_feed_returns_404(): void
    {
        $this->get('/feed/tags/inexistente')->assertNotFound();
        $this->get('/feed/categories/inexistente')->assertNotFound();
    }

    public function test_cached_feed_is_refreshed_when_a_post_is_published(): void
    {
        Post::factory()->published()->create(['title' => 'Primeiro']);
        $this->assertSame(['Primeiro'], $this->itemTitles($this->get('/feed')));

        $draft = Post::factory()->draft()->create(['title' => 'Segundo']);
        $this->assertSame(['Primeiro'], $this->itemTitles($this->get('/feed')));

        $draft->update(['status' => 'published', 'published_at' => now()]);

        $this->assertSame(['Segundo', 'Primeiro'], $this->itemTitles($this->get('/feed')));
    }

    public function test_cached_feed_is_refreshed_when_a_tag_is_renamed(): void
    {
        $tag = Tag::factory()->create(['name' => 'PHP', 'slug' => 'php']);
        Post::factory()->published()->create()->tags()->attach($tag);
        $this->get('/feed/tags/php');

        $tag->update(['name' => 'PHP 8']);

        $this->assertSame('Meu Blog — PHP 8', (string) $this->parse($this->get('/feed/tags/php'))->channel->title);
    }

    public function test_cached_feed_is_reused_while_nothing_changes(): void
    {
        Post::factory()->published()->create();
        $first = $this->get('/feed')->getContent();

        $this->travel(10)->minutes();

        $this->assertSame($first, $this->get('/feed')->getContent());
    }

    private function parse(TestResponse $response): SimpleXMLElement
    {
        return new SimpleXMLElement($response->getContent());
    }

    /**
     * @return list<string>
     */
    private function itemTitles(TestResponse $response): array
    {
        return array_map(fn (SimpleXMLElement $item): string => (string) $item->title, $this->parse($response)->xpath('/rss/channel/item'));
    }
}
