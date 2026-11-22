<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Http\Requests\Admin\ReorderPagesRequest;
use App\Http\Resources\Admin\PageResource;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class PageController extends Controller
{
    /**
     * Todas as páginas (publicadas ou não), sem o corpo, na ordem do menu do blog.
     */
    public function index(): AnonymousResourceCollection
    {
        $pages = Page::query()
            ->select(['id', 'title', 'slug', 'icon', 'position', 'is_published', 'created_at', 'updated_at'])
            ->ordered()
            ->get();

        return PageResource::collection($pages);
    }

    /**
     * Define a ordem das páginas no menu do blog a partir da lista de ids. Reordenar
     * não conta como edição da página, então o updated_at não muda.
     */
    public function reorder(ReorderPagesRequest $request): AnonymousResourceCollection
    {
        DB::transaction(function () use ($request): void {
            Page::withoutTimestamps(function () use ($request): void {
                foreach ($request->ids() as $index => $id) {
                    Page::query()->whereKey($id)->update(['position' => $index + 1]);
                }
            });
        });

        return $this->index();
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
