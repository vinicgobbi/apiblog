<?php

namespace Tests\Feature\Models\Concerns;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_slug_from_title_without_accents(): void
    {
        $post = Post::factory()->create(['title' => 'Introdução ao Laravel: Ações & Serviços']);

        $this->assertSame('introducao-ao-laravel-acoes-servicos', $post->slug);
    }

    public function test_appends_numeric_suffix_when_slug_is_taken(): void
    {
        Post::factory()->create(['title' => 'Olá Mundo']);
        $second = Post::factory()->create(['title' => 'Olá Mundo']);
        $third = Post::factory()->create(['title' => 'Olá Mundo']);

        $this->assertSame('ola-mundo-2', $second->slug);
        $this->assertSame('ola-mundo-3', $third->slug);
    }

    public function test_slug_of_soft_deleted_post_is_not_reused(): void
    {
        Post::factory()->create(['title' => 'Post Removido'])->delete();

        $post = Post::factory()->create(['title' => 'Post Removido']);

        $this->assertSame('post-removido-2', $post->slug);
    }

    public function test_normalizes_slug_provided_by_the_user(): void
    {
        $post = Post::factory()->create(['slug' => 'Meu Slug Personalizado!']);

        $this->assertSame('meu-slug-personalizado', $post->slug);
    }

    public function test_keeps_slug_when_title_changes(): void
    {
        $post = Post::factory()->create(['title' => 'Título Original']);

        $post->update(['title' => 'Título Novo']);

        $this->assertSame('titulo-original', $post->fresh()->slug);
    }

    public function test_changing_slug_to_one_already_taken_receives_suffix(): void
    {
        Post::factory()->create(['title' => 'Primeiro']);
        $post = Post::factory()->create(['title' => 'Segundo']);

        $post->update(['slug' => 'primeiro']);

        $this->assertSame('primeiro-2', $post->fresh()->slug);
    }

    public function test_falls_back_to_model_name_when_source_has_no_sluggable_characters(): void
    {
        $category = Category::factory()->create(['name' => '!!!']);

        $this->assertSame('category', $category->slug);
    }

    public function test_long_titles_produce_slug_within_column_length(): void
    {
        Post::factory()->create(['title' => str_repeat('palavra ', 60)]);
        $post = Post::factory()->create(['title' => str_repeat('palavra ', 60)]);

        $this->assertLessThanOrEqual(255, strlen($post->slug));
        $this->assertStringEndsWith('-2', $post->slug);
    }
}
