<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MarkdownPreviewRequest;
use App\Services\MarkdownRenderer;
use Illuminate\Http\JsonResponse;

/**
 * Pré-visualização do editor: usa o mesmo renderizador dos posts, então o
 * resultado é idêntico ao que será publicado.
 */
class MarkdownPreviewController extends Controller
{
    public function __invoke(MarkdownPreviewRequest $request, MarkdownRenderer $renderer): JsonResponse
    {
        return response()->json(['html' => $renderer->toHtml((string) $request->validated('markdown'))]);
    }
}
