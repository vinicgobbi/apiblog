<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * Gera documentos RSS 2.0 a partir de posts.
 *
 * Usa XMLWriter para que todo texto seja escapado corretamente. Os posts
 * devem vir com author, category e tags carregados.
 */
class RssFeedBuilder
{
    private const int DESCRIPTION_FALLBACK_LENGTH = 300;

    public function __construct(private readonly FrontUrl $frontUrl) {}

    /**
     * @param  Collection<int, Post>  $posts
     */
    public function build(string $title, string $link, string $description, string $selfUrl, Collection $posts): string
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->writeAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

        $xml->startElement('channel');
        $xml->writeElement('title', $title);
        $xml->writeElement('link', $link);
        $xml->writeElement('description', $description !== '' ? $description : $title);
        $xml->writeElement('language', 'pt-BR');
        $xml->writeElement('lastBuildDate', ($posts->max('updated_at') ?? now())->toRssString());

        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $selfUrl);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($posts as $post) {
            $this->writeItem($xml, $post);
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function writeItem(XMLWriter $xml, Post $post): void
    {
        $url = $this->frontUrl->post($post);

        $xml->startElement('item');
        $xml->writeElement('title', $post->title);
        $xml->writeElement('link', $url);

        $xml->startElement('guid');
        $xml->writeAttribute('isPermaLink', 'true');
        $xml->text($url);
        $xml->endElement();

        $xml->writeElement('pubDate', $post->published_at->toRssString());
        $xml->writeElement('dc:creator', $post->author->name);

        foreach (array_filter([$post->category?->name, ...$post->tags->pluck('name')]) as $category) {
            $xml->writeElement('category', $category);
        }

        $xml->writeElement('description', $this->description($post));

        $xml->startElement('content:encoded');
        $xml->writeCdata($this->cdataSafe((string) $post->body_html));
        $xml->endElement();

        $xml->endElement();
    }

    private function description(Post $post): string
    {
        if (filled($post->excerpt)) {
            return $post->excerpt;
        }

        $text = trim(html_entity_decode(strip_tags((string) $post->body_html), ENT_QUOTES | ENT_HTML5));

        return Str::limit(preg_replace('/\s+/u', ' ', $text), self::DESCRIPTION_FALLBACK_LENGTH);
    }

    /**
     * Uma seção CDATA não pode conter "]]>"; a sequência é dividida entre
     * duas seções para manter o XML válido.
     */
    private function cdataSafe(string $html): string
    {
        return str_replace(']]>', ']]]]><![CDATA[>', $html);
    }
}
