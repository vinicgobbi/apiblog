<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_categories_with_published_posts_and_their_count(): void
    {
        $laravel = Category::factory()->create(['name' => 'Laravel']);
        $angular = Category::factory()->create(['name' => 'Angular']);
        $onlyDrafts = Category::factory()->create(['name' => 'Rascunhos']);
        Category::factory()->create(['name' => 'Vazia']);
        Post::factory()->count(2)->published()->for($laravel)->create();
        Post::factory()->draft()->for($laravel)->create();
        Post::factory()->published()->for($angular)->create();
        Post::factory()->draft()->for($onlyDrafts)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Angular', 'Laravel'])
            ->assertJsonPath('data.*.posts_count', [1, 2]);
    }

    public function test_show_returns_category_by_slug_with_published_post_count(): void
    {
        $category = Category::factory()->create(['name' => 'Laravel', 'slug' => 'laravel', 'description' => 'Posts sobre Laravel']);
        Post::factory()->published()->for($category)->create();
        Post::factory()->scheduled()->for($category)->create();

        $this->getJson('/api/categories/laravel')
            ->assertOk()
            ->assertExactJson(['data' => [
                'id' => $category->id,
                'name' => 'Laravel',
                'slug' => 'laravel',
                'description' => 'Posts sobre Laravel',
                'posts_count' => 1,
            ]]);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $this->getJson('/api/categories/inexistente')->assertNotFound();
    }
}
