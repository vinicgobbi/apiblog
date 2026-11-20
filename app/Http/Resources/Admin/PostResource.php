<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\TagResource;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Post na visão do admin: inclui rascunhos, status e o Markdown original.
 * O corpo só é incluído quando carregado (fica de fora nas listagens).
 *
 * @mixin Post
 */
class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body_markdown' => $this->whenHas('body_markdown'),
            'body_html' => $this->whenHas('body_html'),
            'cover_image_path' => $this->cover_image_path,
            'cover_image_url' => $this->cover_image_url,
            'status' => $this->status->value,
            'state' => $this->state(),
            'published_at' => $this->published_at?->toIso8601String(),
            'reading_time' => $this->reading_time,
            'author' => $this->whenLoaded('author', fn (): array => ['id' => $this->author->id, 'name' => $this->author->name]),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
