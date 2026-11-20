<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Gera um slug único a partir de um atributo do model ao salvar.
 *
 * Se o slug for informado, ele é normalizado; se ficar vazio, é gerado a
 * partir do atributo indicado em slugSourceAttribute(). Em caso de
 * colisão, um sufixo numérico é adicionado (ex.: "meu-post-2").
 * Registros na lixeira (soft delete) também contam como colisão, pois
 * continuam ocupando o índice único da coluna.
 */
trait HasSlug
{
    /**
     * Tamanho máximo da parte base do slug, deixando espaço para o sufixo.
     */
    private const int SLUG_BASE_MAX_LENGTH = 200;

    /**
     * Nome do atributo usado como origem do slug (ex.: "title").
     */
    abstract protected function slugSourceAttribute(): string;

    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&HasSlug $model */
            $model->fillSlug();
        });
    }

    protected function fillSlug(): void
    {
        if (filled($this->slug) && ! $this->isDirty('slug')) {
            return;
        }

        $base = $this->slugBase(filled($this->slug) ? $this->slug : (string) $this->{$this->slugSourceAttribute()});
        $slug = $base;
        $suffix = 2;

        while ($this->slugQuery()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        $this->slug = $slug;
    }

    private function slugBase(string $value): string
    {
        $slug = rtrim(Str::substr(Str::slug($value), 0, self::SLUG_BASE_MAX_LENGTH), '-');

        return $slug !== '' ? $slug : Str::slug(class_basename($this));
    }

    /**
     * @return Builder<static>
     */
    private function slugQuery(): Builder
    {
        $query = static::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($this), true)) {
            $query->withTrashed();
        }

        if ($this->exists) {
            $query->whereKeyNot($this->getKey());
        }

        return $query;
    }
}
