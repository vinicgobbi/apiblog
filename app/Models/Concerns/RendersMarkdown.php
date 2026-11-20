<?php

namespace App\Models\Concerns;

use App\Services\MarkdownRenderer;
use Illuminate\Database\Eloquent\Model;

/**
 * Mantém body_html sincronizado com body_markdown.
 *
 * O HTML é renderizado ao salvar (e não a cada leitura) sempre que o
 * Markdown muda ou o HTML ainda não existe.
 */
trait RendersMarkdown
{
    public static function bootRendersMarkdown(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&RendersMarkdown $model */
            if ($model->isDirty('body_markdown') || $model->body_html === null) {
                $model->renderMarkdown();
            }
        });
    }

    /**
     * Renderiza o Markdown atual, sem salvar o model.
     */
    public function renderMarkdown(): void
    {
        $renderer = app(MarkdownRenderer::class);

        $this->body_html = $renderer->toHtml((string) $this->body_markdown);

        $this->markdownRendered($renderer);
    }

    /**
     * Ponto de extensão para models que derivam outros dados do HTML.
     */
    protected function markdownRendered(MarkdownRenderer $renderer): void
    {
        //
    }
}
