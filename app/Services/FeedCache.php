<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache dos feeds RSS.
 *
 * Cada chave inclui um número de versão; flush() incrementa a versão e,
 * com isso, invalida todos os feeds de uma vez. Funciona com qualquer
 * driver de cache (o driver "database" não suporta tags).
 */
class FeedCache
{
    private const string VERSION_KEY = 'feed:version';

    /**
     * @param  Closure(): string  $callback
     */
    public function remember(string $key, Closure $callback): string
    {
        return Cache::remember(
            "feed:v{$this->version()}:{$key}",
            config('blog.feed.cache_ttl'),
            $callback,
        );
    }

    public function flush(): void
    {
        Cache::forever(self::VERSION_KEY, $this->version() + 1);
    }

    private function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
