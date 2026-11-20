<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Imagem enviada pelo admin, armazenada no disco "public".
 */
#[Fillable(['path', 'original_name', 'mime_type', 'size', 'width', 'height', 'alt'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    public const string DISK = 'public';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * URL pública do arquivo.
     *
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk(self::DISK)->url($this->path));
    }

    /**
     * Indica se a imagem é capa de algum post (inclusive na lixeira, que pode
     * ser restaurado) ou se aparece no conteúdo de algum post ou página.
     */
    public function isInUse(): bool
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $this->path).'%';

        return Post::withTrashed()
            ->where(fn ($query) => $query
                ->where('cover_image_path', $this->path)
                ->orWhereRaw("body_markdown LIKE ? ESCAPE '!'", [$pattern]))
            ->exists()
            || Page::query()->whereRaw("body_markdown LIKE ? ESCAPE '!'", [$pattern])->exists();
    }
}
