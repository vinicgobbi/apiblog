<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TagRequest;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class TagController extends Controller
{
    /**
     * Todas as tags, com a contagem de posts (de qualquer situação).
     */
    public function index(): AnonymousResourceCollection
    {
        return TagResource::collection(Tag::query()->withCount('posts')->orderBy('name')->get());
    }

    public function store(TagRequest $request): JsonResponse
    {
        $tag = Tag::create($request->validated());

        return (new TagResource($tag->loadCount('posts')))->response()->setStatusCode(201);
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag->loadCount('posts'));
    }

    public function update(TagRequest $request, Tag $tag): TagResource
    {
        $tag->update($request->validated());

        return new TagResource($tag->loadCount('posts'));
    }

    /**
     * Exclui a tag e a remove de todos os posts.
     */
    public function destroy(Tag $tag): Response
    {
        $tag->delete();

        return response()->noContent();
    }
}
