<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HttpCacheHeadersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['blog.http_cache.api' => 60, 'blog.http_cache.feed' => 300]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function publicEndpoints(): array
    {
        return [
            'lista de posts' => ['/api/posts'],
            'post' => ['/api/posts/post-publico'],
            'comentários do post' => ['/api/posts/post-publico/comments'],
            'lista de categorias' => ['/api/categories'],
            'categoria' => ['/api/categories/php'],
            'lista de tags' => ['/api/tags'],
            'tag' => ['/api/tags/laravel'],
            'página' => ['/api/pages/sobre'],
        ];
    }

    #[DataProvider('publicEndpoints')]
    public function test_public_endpoints_are_publicly_cacheable_with_etag(string $uri): void
    {
        $this->seedPublicContent();

        $response = $this->getJson($uri)->assertOk();

        $this->assertPublicCache($response, 60);
        $this->assertNotEmpty($response->headers->get('ETag'));
    }

    public function test_max_age_comes_from_configuration(): void
    {
        config(['blog.http_cache.api' => 15]);

        $this->assertPublicCache($this->getJson('/api/tags')->assertOk(), 15);
    }

    public function test_matching_etag_returns_not_modified_without_body(): void
    {
        $this->seedPublicContent();
        $etag = $this->getJson('/api/posts/post-publico')->headers->get('ETag');

        $this->getJson('/api/posts/post-publico', ['If-None-Match' => $etag])
            ->assertStatus(304)
            ->assertContent('');
    }

    public function test_changed_content_invalidates_etag(): void
    {
        $this->seedPublicContent();
        $etag = $this->getJson('/api/posts/post-publico')->headers->get('ETag');

        Post::query()->where('slug', 'post-publico')->update(['title' => 'Título alterado']);

        $this->getJson('/api/posts/post-publico', ['If-None-Match' => $etag])
            ->assertOk()
            ->assertJsonPath('data.title', 'Título alterado');
    }

    public function test_not_found_responses_are_not_publicly_cached(): void
    {
        $response = $this->getJson('/api/posts/nao-existe')->assertNotFound();

        $this->assertNotPublic($response);
    }

    public function test_feeds_use_feed_max_age(): void
    {
        $this->seedPublicContent();

        foreach (['/feed', '/feed/tags/laravel', '/feed/categories/php'] as $uri) {
            $this->assertPublicCache($this->get($uri)->assertOk(), 300);
        }
    }

    public function test_admin_endpoints_are_never_cached(): void
    {
        $this->seedPublicContent();
        $admin = User::factory()->create();

        foreach (['/api/admin/posts', '/api/admin/tags', '/api/admin/comments', '/api/auth/user'] as $uri) {
            $response = $this->actingAs($admin, 'api')->getJson($uri)->assertOk();

            $this->assertNotPublic($response);
            $this->assertTrue($response->baseResponse->headers->hasCacheControlDirective('no-store'), "{$uri} deveria enviar no-store.");
        }
    }

    public function test_auth_endpoints_are_never_public(): void
    {
        $admin = User::factory()->create(['email' => 'admin@blog.test', 'password' => 'senha-correta']);

        $this->assertNotPublic($this->getJson('/api/auth/user')->assertUnauthorized());

        $login = $this->postJson('/api/auth/login', ['email' => $admin->email, 'password' => 'senha-correta'])->assertOk();

        $this->assertNotPublic($login);
    }

    private function seedPublicContent(): void
    {
        $category = Category::factory()->create(['slug' => 'php']);
        $tag = Tag::factory()->create(['slug' => 'laravel']);
        $post = Post::factory()->published()->for($category)->create(['slug' => 'post-publico']);
        $post->tags()->attach($tag);
        Comment::factory()->approved()->for($post)->create();
        Page::factory()->published()->create(['slug' => 'sobre']);
    }

    private function assertPublicCache(TestResponse $response, int $maxAge): void
    {
        $headers = $response->baseResponse->headers;

        $this->assertTrue($headers->hasCacheControlDirective('public'), 'Cache-Control deveria ser public: '.$headers->get('Cache-Control'));
        $this->assertFalse($headers->hasCacheControlDirective('private'));
        $this->assertSame((string) $maxAge, $headers->getCacheControlDirective('max-age'));
    }

    private function assertNotPublic(TestResponse $response): void
    {
        $headers = $response->baseResponse->headers;

        $this->assertFalse($headers->hasCacheControlDirective('public'), 'Cache-Control não deveria ser public: '.$headers->get('Cache-Control'));
        $this->assertTrue($headers->hasCacheControlDirective('private'));
    }
}
