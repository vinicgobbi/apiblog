<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_every_admin_tag_endpoint_returns_401_without_token(): void
    {
        $tag = Tag::factory()->create();

        $this->getJson('/api/admin/tags')->assertUnauthorized();
        $this->postJson('/api/admin/tags', [])->assertUnauthorized();
        $this->putJson("/api/admin/tags/{$tag->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/tags/{$tag->id}")->assertUnauthorized();
    }

    public function test_index_lists_all_tags_with_count_of_posts_in_any_state(): void
    {
        $unused = Tag::factory()->create(['name' => 'css']);
        $used = Tag::factory()->create(['name' => 'php']);
        Post::factory()->draft()->create()->tags()->attach($used);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/tags')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$unused->id, $used->id])
            ->assertJsonPath('data.*.posts_count', [0, 1]);
    }

    public function test_store_creates_tag_and_rejects_duplicate_slug(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/tags', ['name' => 'Vue.js'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'vuejs');

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/tags', ['name' => 'Outra', 'slug' => 'VueJS'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug' => 'Já existe uma tag com este slug']);
    }

    public function test_update_renames_tag(): void
    {
        $tag = Tag::factory()->create(['name' => 'php']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/tags/{$tag->id}", ['name' => 'PHP'])
            ->assertOk()
            ->assertJsonPath('data.name', 'PHP');
    }

    public function test_destroy_removes_tag_from_posts(): void
    {
        $tag = Tag::factory()->create();
        $post = Post::factory()->create();
        $post->tags()->attach($tag);

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/tags/{$tag->id}")->assertNoContent();

        $this->assertModelMissing($tag);
        $this->assertModelExists($post);
        $this->assertSame(0, $post->tags()->count());
    }
}
