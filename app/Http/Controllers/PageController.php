<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageResource;
use App\Models\Page;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PageController extends Controller
{
    /**
     * Páginas publicadas, sem o corpo, na ordem definida no admin. Usado no menu do blog.
     */
    public function index(): AnonymousResourceCollection
    {
        $pages = Page::query()
            ->published()
            ->select(['id', 'title', 'slug', 'icon'])
            ->ordered()
            ->get();

        return PageResource::collection($pages);
    }

    public function show(Page $page): PageResource
    {
        abort_unless($page->is_published, 404);

        return new PageResource($page);
    }
}
