<?php

namespace App\Http\Resources;

use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Post completo, com o HTML renderizado do corpo.
 *
 * @mixin Post
 */
class PostDetailResource extends PostResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'body_html' => $this->body_html,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
