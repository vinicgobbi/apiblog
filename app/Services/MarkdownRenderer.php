<?php

namespace App\Services;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Str;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Normalizer\TextNormalizerInterface;

/**
 * Converte o Markdown dos posts e páginas em HTML seguro.
 *
 * HTML cru escrito no Markdown é escapado (não é interpretado) e links
 * inseguros, como "javascript:", são descartados, evitando XSS no front.
 */
#[Singleton]
class MarkdownRenderer
{
    /**
     * Velocidade média de leitura usada no cálculo do tempo de leitura.
     */
    public const int WORDS_PER_MINUTE = 200;

    private readonly MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
            'heading_permalink' => [
                'min_heading_level' => 2,
                'insert' => 'after',
                'symbol' => '#',
                'title' => 'Link para esta seção',
                'apply_id_to_heading' => true,
                'id_prefix' => 'secao',
                'fragment_prefix' => 'secao',
            ],
            'slug_normalizer' => [
                'instance' => new class implements TextNormalizerInterface
                {
                    /**
                     * Ids de títulos sem acentos, no mesmo formato dos slugs de posts.
                     *
                     * @param  array<string, mixed>  $context
                     */
                    public function normalize(string $text, array $context = []): string
                    {
                        return Str::slug($text);
                    }
                },
            ],
            'external_link' => [
                'internal_hosts' => $this->internalHosts(),
                'open_in_new_window' => true,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new ExternalLinkExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }

    /**
     * Tempo estimado de leitura, em minutos, do HTML renderizado.
     * Conteúdo sem palavras retorna 0; qualquer texto conta ao menos 1 minuto.
     */
    public function readingTime(string $html): int
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5));

        if ($text === '') {
            return 0;
        }

        $words = count(preg_split('/\s+/u', $text) ?: []);

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Hosts considerados internos: links para eles não abrem em nova aba.
     *
     * @return list<string>
     */
    private function internalHosts(): array
    {
        return collect([config('blog.front_url'), config('app.url')])
            ->map(fn (?string $url): ?string => $url ? parse_url($url, PHP_URL_HOST) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
