<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\FeedCache;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'description'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, HasSlug;

    /**
     * Alterações refletem nos feeds RSS, então o cache deles é invalidado.
     */
    protected static function booted(): void
    {
        $flushFeeds = fn () => app(FeedCache::class)->flush();

        static::saved($flushFeeds);
        static::deleted($flushFeeds);
    }

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    protected function slugSourceAttribute(): string
    {
        return 'name';
    }
}
