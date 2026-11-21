<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marca respostas de leitura como cacheáveis publicamente, com ETag.
 *
 * O max-age vem de config/blog.php (chave http_cache.{perfil}) e é lido a cada
 * requisição, então pode ser ajustado sem alterar código nem o cache de rotas.
 * Só respostas de sucesso a GET/HEAD são marcadas (regra do SetCacheHeaders).
 */
class SetPublicCacheHeaders
{
    public function __construct(private SetCacheHeaders $cacheHeaders) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $profile = 'api'): Response
    {
        return $this->cacheHeaders->handle($request, $next, [
            'public' => true,
            'max_age' => (int) config("blog.http_cache.{$profile}"),
            'etag' => true,
        ]);
    }
}
