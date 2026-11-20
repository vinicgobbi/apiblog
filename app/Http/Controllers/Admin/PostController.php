<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPostRequest;
use App\Http\Requests\Admin\PostRequest;
use App\Http\Resources\Admin\PostResource;
use App\Models\Post;
use App\Models\Tag;
use App\Services\FeedCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends Controller
{
    /**
     * Colunas usadas na listagem; o corpo (Markdown e HTML) fica de fora.
     */
    private const array LIST_COLUMNS = [
        'id', 'user_id', 'category_id', 'title', 'slug', 'excerpt', 'cover_image_path',
        'status', 'published_at', 'reading_time', 'created_at', 'updated_at',
    ];

    private const array RELATIONS = ['author:id,name', 'category', 'tags'];

    public function __construct(private readonly FeedCache $feedCache) {}

    /**
     * Todos os posts (rascunhos, agendados e publicados), dos mais recentes aos mais antigos.
     */
    public function index(IndexPostRequest $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->select(self::LIST_COLUMNS)
            ->with(self::RELATIONS)
            ->when($request->validated('state'), fn (Builder $query, string $state) => match ($state) {
                'draft' => $query->where('status', PostStatus::Draft),
                'scheduled' => $query->scheduled(),
                'published' => $query->published(),
            })
            ->when($request->validated('q'), fn (Builder $query, string $term) => $query->search($term))
            ->latest()
            ->latest('id')
            ->paginate($request->perPage())
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function store(PostRequest $request): JsonResponse
    {
        $post = DB::transaction(function () use ($request): Post {
            $post = $request->user()->posts()->create($request->postAttributes());
            $post->tags()->sync($this->tagIds($request->tagNames() ?? []));

            return $post;
        });

        $this->feedCache->flush();

        return (new PostResource($post->load(self::RELATIONS)))->response()->setStatusCode(201);
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load(self::RELATIONS));
    }

    public function update(PostRequest $request, Post $post): PostResource
    {
        DB::transaction(function () use ($request, $post): void {
            $post->update($request->postAttributes());

            if (($tagNames = $request->tagNames()) !== null) {
                $post->tags()->sync($this->tagIds($tagNames));
            }
        });

        $this->feedCache->flush();

        return new PostResource($post->load(self::RELATIONS));
    }

    /**
     * Move o post para a lixeira (soft delete); o slug continua reservado.
     */
    public function destroy(Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }

    /**
     * Ids das tags pelos nomes, criando as que ainda não existem. Nomes que
     * resultam no mesmo slug (ex.: "PHP" e "php") são tratados como uma tag.
     *
     * @param  list<string>  $names
     * @return list<int>
     */
    private function tagIds(array $names): array
    {
        return collect($names)
            ->map(fn (string $name): string => trim($name))
            ->filter(fn (string $name): bool => Str::slug($name) !== '')
            ->unique(fn (string $name): string => Str::slug($name))
            ->map(fn (string $name): int => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id)
            ->values()
            ->all();
    }
}
