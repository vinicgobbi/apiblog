<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\RendersMarkdown;
use App\Services\FeedCache;
use App\Services\MarkdownRenderer;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * O autor (user_id) não é mass assignable: deve ser definido pelo
 * usuário autenticado, ex.: $user->posts()->create($data).
 */
#[Fillable(['category_id', 'title', 'slug', 'excerpt', 'body_markdown', 'cover_image_path', 'status', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, HasSlug, RendersMarkdown, SoftDeletes;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PostStatus::Draft->value,
    ];

    /**
     * Alterações refletem nos feeds RSS, então o cache deles é invalidado.
     */
    protected static function booted(): void
    {
        $flushFeeds = fn () => app(FeedCache::class)->flush();

        static::saved($flushFeeds);
        static::deleted($flushFeeds);
        static::restored($flushFeeds);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'published_at' => 'datetime',
            'reading_time' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Posts visíveis ao público: publicados e com data de publicação já
     * alcançada (posts com data futura ficam agendados).
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * URL pública da imagem de capa (arquivo no disco "public").
     *
     * @return Attribute<?string, never>
     */
    protected function coverImageUrl(): Attribute
    {
        return Attribute::get(
            fn (): ?string => $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null,
        );
    }

    /**
     * Busca no título e no resumo, tratando "%" e "_" digitados como texto.
     * O caractere de escape é explícito para funcionar igual no MySQL e no SQLite.
     *
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function search(Builder $query, string $term): void
    {
        $pattern = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';

        $query->where(fn (Builder $query) => $query
            ->whereRaw("title LIKE ? ESCAPE '!'", [$pattern])
            ->orWhereRaw("excerpt LIKE ? ESCAPE '!'", [$pattern]));
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && ! $this->published_at->isFuture();
    }

    protected function markdownRendered(MarkdownRenderer $renderer): void
    {
        $this->reading_time = $renderer->readingTime($this->body_html);
    }

    protected function slugSourceAttribute(): string
    {
        return 'title';
    }
}
