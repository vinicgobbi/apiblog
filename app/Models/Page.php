<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RendersMarkdown;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'icon', 'body_markdown', 'is_published'])]
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory, HasSlug, RendersMarkdown;

    /**
     * Página nova entra no fim do menu. A posição não é preenchível em massa:
     * só muda pela reordenação (PUT /admin/pages/order).
     */
    protected static function booted(): void
    {
        static::creating(function (Page $page): void {
            $page->position ??= (int) static::query()->max('position') + 1;
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /**
     * Ordem do menu do blog, definida no admin.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    protected function slugSourceAttribute(): string
    {
        return 'title';
    }
}
