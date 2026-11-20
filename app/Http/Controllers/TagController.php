<?php

namespace App\Http\Controllers;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    /**
     * Tags com ao menos um post publicado, com a contagem desses posts.
     */
    public function index(): AnonymousResourceCollection
    {
        $tags = Tag::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('name')
            ->get();

        return TagResource::collection($tags);
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource(
            $tag->loadCount(['posts' => fn (Builder $query) => $query->published()]),
        );
    }
}
