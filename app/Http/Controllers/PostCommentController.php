<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class PostCommentController extends Controller
{
    private const int PER_PAGE = 50;

    /**
     * Comentários aprovados do post, em ordem cronológica.
     */
    public function index(Post $post): AnonymousResourceCollection
    {
        abort_unless($post->isPublished(), 404);

        $comments = $post->comments()
            ->approved()
            ->oldest()
            ->oldest('id')
            ->paginate(self::PER_PAGE);

        return CommentResource::collection($comments);
    }

    /**
     * Recebe um comentário de leitor. Ele fica pendente até ser aprovado na
     * moderação. Envios com o honeypot preenchido recebem a mesma resposta,
     * mas são descartados, para não indicar ao bot que foi detectado.
     */
    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        abort_unless($post->isPublished(), 404);

        if (! $request->isSpam()) {
            $comment = new Comment($request->validated());
            $comment->ip_address = $request->ip();
            $comment->user_agent = Str::limit((string) $request->userAgent(), 512, '');
            $post->comments()->save($comment);
        }

        return response()->json(['message' => 'Comentário enviado! Ele será publicado após a moderação.'], 202);
    }
}
