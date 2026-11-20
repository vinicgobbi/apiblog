<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();
    }

    public function test_every_admin_category_endpoint_returns_401_without_token(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/admin/categories')->assertUnauthorized();
        $this->postJson('/api/admin/categories', [])->assertUnauthorized();
        $this->putJson("/api/admin/categories/{$category->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/categories/{$category->id}")->assertUnauthorized();
    }

    public function test_index_lists_all_categories_with_count_of_posts_in_any_state(): void
    {
        $empty = Category::factory()->create(['name' => 'Angular']);
        $withDrafts = Category::factory()->create(['name' => 'Laravel']);
        Post::factory()->draft()->for($withDrafts)->create();
        Post::factory()->published()->for($withDrafts)->create();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/categories')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$empty->id, $withDrafts->id])
            ->assertJsonPath('data.*.posts_count', [0, 2]);
    }

    public function test_store_creates_category_with_generated_slug(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories', ['name' => 'Programação Web', 'description' => 'Front e back'])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'programacao-web')
            ->assertJsonPath('data.posts_count', 0);
    }

    public function test_store_with_duplicate_slug_or_missing_name_returns_422(): void
    {
        Category::factory()->create(['slug' => 'laravel']);

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/categories', ['slug' => 'Laravel'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'O nome é obrigatório', 'slug' => 'Já existe uma categoria com este slug']);
    }

    public function test_update_renames_category_keeping_its_own_slug(): void
    {
        $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/categories/{$category->id}", ['name' => 'Laravel 13', 'slug' => 'laravel'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Laravel 13')
            ->assertJsonPath('data.slug', 'laravel');
    }

    public function test_destroy_removes_category_and_keeps_its_posts_without_category(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->for($category)->create();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/categories/{$category->id}")->assertNoContent();

        $this->assertModelMissing($category);
        $this->assertNull($post->fresh()->category_id);
    }
}
