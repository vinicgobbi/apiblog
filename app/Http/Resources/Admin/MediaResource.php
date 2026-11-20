<?php

namespace App\Http\Resources\Admin;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Media
 */
class MediaResource extends JsonResource
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
            'url' => $this->url,
            'path' => $this->path,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'width' => $this->width,
            'height' => $this->height,
            'alt' => $this->alt,
            'markdown' => $this->markdownSnippet(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Snippet pronto para colar no editor. Colchetes no texto alternativo são
     * escapados para não quebrar a sintaxe da imagem.
     */
    private function markdownSnippet(): string
    {
        $alt = str_replace(['\\', '[', ']'], ['\\\\', '\\[', '\\]'], (string) $this->alt);

        return "![{$alt}]({$this->url})";
    }
}
