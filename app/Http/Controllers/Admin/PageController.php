<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Http\Resources\Admin\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class PageController extends Controller
{
    /**
     * Todas as páginas (publicadas ou não), sem o corpo.
     */
    public function index(): AnonymousResourceCollection
    {
        $pages = Page::query()
            ->select(['id', 'title', 'slug', 'icon', 'is_published', 'created_at', 'updated_at'])
            ->orderBy('title')
            ->get();

        return PageResource::collection($pages);
    }

    public function store(PageRequest $request): JsonResponse
    {
        $page = Page::create($request->validated());

        return (new PageResource($page))->response()->setStatusCode(201);
    }

    public function show(Page $page): PageResource
    {
        return new PageResource($page);
    }

    public function update(PageRequest $request, Page $page): PageResource
    {
        $page->update($request->validated());

        return new PageResource($page);
    }

    public function destroy(Page $page): Response
    {
        $page->delete();

        return response()->noContent();
    }
}
