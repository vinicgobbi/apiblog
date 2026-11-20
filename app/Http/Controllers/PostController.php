<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexPostRequest;
use App\Http\Resources\PostDetailResource;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    /**
     * Colunas usadas na listagem; o corpo (Markdown e HTML) fica de fora.
     */
    private const array LIST_COLUMNS = [
        'id', 'user_id', 'category_id', 'title', 'slug', 'excerpt',
        'cover_image_path', 'published_at', 'reading_time',
    ];

    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->published()
            ->select(self::LIST_COLUMNS)
            ->with(['author:id,name', 'category', 'tags'])
            ->when($request->validated('tag'), fn (Builder $query, string $tag) => $query->whereRelation('tags', 'slug', $tag))
            ->when($request->validated('category'), fn (Builder $query, string $category) => $query->whereRelation('category', 'slug', $category))
            ->when($request->validated('q'), fn (Builder $query, string $term) => $this->search($query, $term))
            ->latest('published_at')
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function show(Post $post): PostDetailResource
    {
        abort_unless($post->isPublished(), 404);

        return new PostDetailResource($post->load(['author:id,name', 'category', 'tags']));
    }

    /**
     * Busca no título e no resumo, tratando "%" e "_" digitados como texto.
     * O caractere de escape é explícito para funcionar igual no MySQL e no SQLite.
     */
    private function search(Builder $query, string $term): void
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(fn (Builder $query) => $query
            ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereRaw("excerpt LIKE ? ESCAPE '!'", [$pattern]));
    }
}
