<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;

/**
 * Monta URLs das páginas do front-end (config blog.front_url e blog.front_paths).
 */
class FrontUrl
{
    public function home(): string
    {
        return config('blog.front_url').'/';
    }

    public function post(Post $post): string
    {
        return $this->path('post', $post->slug);
    }

    public function tag(Tag $tag): string
    {
        return $this->path('tag', $tag->slug);
    }

    public function category(Category $category): string
    {
        return $this->path('category', $category->slug);
    }

    private function path(string $page, string $slug): string
    {
        return config('blog.front_url').str_replace('{slug}', rawurlencode($slug), config("blog.front_paths.{$page}"));
    }
}
