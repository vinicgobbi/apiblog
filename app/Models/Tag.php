<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\FeedCache;
use Database\Factories\TagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug'])]
class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
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
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class);
    }

    protected function slugSourceAttribute(): string
    {
        return 'name';
    }
}
