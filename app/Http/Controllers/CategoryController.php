<?php

namespace App\Http\Controllers;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Categorias com ao menos um post publicado, com a contagem desses posts.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->whereHas('posts', fn (Builder $query) => $query->published())
            ->withCount(['posts' => fn (Builder $query) => $query->published()])
            ->orderBy('name')
            ->get();

        return CategoryResource::collection($categories);
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource(
            $category->loadCount(['posts' => fn (Builder $query) => $query->published()]),
        );
    }
}
