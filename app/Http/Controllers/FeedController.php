<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Services\FeedCache;
use App\Services\FrontUrl;
use App\Services\RssFeedBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Feeds RSS 2.0 dos posts publicados: geral, por tag e por categoria.
 */
class FeedController extends Controller
{
    public function __construct(
        private readonly RssFeedBuilder $builder,
        private readonly FeedCache $cache,
        private readonly FrontUrl $frontUrl,
    ) {}

    public function index(Request $request): Response
    {
        $xml = $this->cache->remember('index', fn (): string => $this->builder->build(
            config('blog.title'),
            $this->frontUrl->home(),
            config('blog.description'),
            $request->url(),
            $this->latestPosts(),
        ));

        return $this->rss($xml);
    }

    public function tag(Request $request, Tag $tag): Response
    {
        $xml = $this->cache->remember("tag:{$tag->id}", fn (): string => $this->builder->build(
            config('blog.title')." — {$tag->name}",
            $this->frontUrl->tag($tag),
            "Posts com a tag {$tag->name}",
            $request->url(),
            $this->latestPosts(fn (Builder $query) => $query->whereRelation('tags', 'tags.id', $tag->id)),
        ));

        return $this->rss($xml);
    }

    public function category(Request $request, Category $category): Response
    {
        $xml = $this->cache->remember("category:{$category->id}", fn (): string => $this->builder->build(
            config('blog.title')." — {$category->name}",
            $this->frontUrl->category($category),
            $category->description ?: "Posts da categoria {$category->name}",
            $request->url(),
            $this->latestPosts(fn (Builder $query) => $query->whereBelongsTo($category)),
        ));

        return $this->rss($xml);
    }

    /**
     * @param  (callable(Builder<Post>): mixed)|null  $filter
     * @return Collection<int, Post>
     */
    private function latestPosts(?callable $filter = null): Collection
    {
        return Post::query()
            ->published()
            ->with(['author:id,name', 'category', 'tags'])
            ->when($filter, fn (Builder $query) => $filter($query))
            ->latest('published_at')
            ->latest('id')
            ->limit(config('blog.feed.items'))
            ->get();
    }

    private function rss(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
