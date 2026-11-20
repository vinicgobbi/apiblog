<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexCommentRequest;
use App\Http\Requests\Admin\UpdateCommentRequest;
use App\Http\Resources\Admin\CommentResource;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CommentController extends Controller
{
    /**
     * Comentários do mais recente para o mais antigo, com filtros de status e post.
     */
    public function index(IndexCommentRequest $request): AnonymousResourceCollection
    {
        $comments = Comment::query()
            ->with($this->postRelation())
            ->when($request->validated('status'), fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->validated('post_id'), fn (Builder $query, int|string $postId) => $query->where('post_id', $postId))
            ->latest()
            ->latest('id')
            ->paginate(IndexCommentRequest::PER_PAGE)
            ->withQueryString();

        return CommentResource::collection($comments);
    }

    /**
     * Quantidade de comentários por status (ex.: badge de pendentes no menu).
     */
    public function counts(): JsonResponse
    {
        $totals = Comment::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return response()->json([
            'data' => collect(CommentStatus::cases())
                ->mapWithKeys(fn (CommentStatus $status): array => [$status->value => (int) ($totals[$status->value] ?? 0)])
                ->all(),
        ]);
    }

    public function show(Comment $comment): CommentResource
    {
        return new CommentResource($comment->load($this->postRelation()));
    }

    public function update(UpdateCommentRequest $request, Comment $comment): CommentResource
    {
        $comment->status = $request->status();
        $comment->save();

        return new CommentResource($comment->load($this->postRelation()));
    }

    public function destroy(Comment $comment): Response
    {
        $comment->delete();

        return response()->noContent();
    }

    /**
     * O post é carregado inclusive se estiver na lixeira, para dar contexto
     * ao moderador.
     *
     * @return array<string, \Closure>
     */
    private function postRelation(): array
    {
        return ['post' => fn ($query) => $query->withTrashed()->select(['id', 'title', 'slug'])];
    }
}
