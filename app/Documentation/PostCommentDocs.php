<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints públicos de comentários de posts.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Comments', description: 'Comentários dos leitores (publicados após moderação)')]
#[OA\Schema(
    component: 'Comment',
    description: 'Comentário aprovado. Não expõe o e-mail nem dados técnicos do autor.',
    properties: [
        'id' => Type::Integer,
        'author_name' => Type::String,
        'body' => new OA\Schema(type: Type::String, description: 'Texto puro; deve ser exibido escapado'),
        'created_at' => Format::DateTime,
    ],
)]
final class PostCommentDocs
{
    #[OA\Operation\Get(
        path: '/posts/{slug}/comments',
        summary: 'Lista os comentários aprovados de um post',
        description: 'Em ordem cronológica, 50 por página.',
        tags: ['Comments'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
            new OA\Parameter(name: 'page', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer, minimum: 1, default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de comentários',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Comment')),
                    'links' => new OA\Schema(ref: '#/components/schemas/PaginationLinks'),
                    'meta' => new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 404, description: 'Post inexistente ou não publicado'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/posts/{slug}/comments',
        summary: 'Envia um comentário',
        description: 'O comentário fica pendente até ser aprovado. Limitado a 3 envios por minuto. '
            .'O campo "website" é um honeypot: deve ficar escondido e vazio no formulário.',
        tags: ['Comments'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new Json(
                required: ['author_name', 'author_email', 'body'],
                properties: [
                    'author_name' => new OA\Schema(type: Type::String, maxLength: 100, example: 'Leitora'),
                    'author_email' => new OA\Schema(type: Type::String, format: Format::Email, maxLength: 255, example: 'leitora@example.com'),
                    'body' => new OA\Schema(type: Type::String, minLength: 2, maxLength: 5000, example: 'Ótimo post!'),
                    'website' => new OA\Schema(type: Type::String, description: 'Honeypot: deve ser enviado vazio'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 202,
                description: 'Comentário recebido e aguardando moderação',
                content: new Json(properties: [
                    'message' => new OA\Schema(type: Type::String, example: 'Comentário enviado! Ele será publicado após a moderação.'),
                ]),
            ),
            new OA\Response(response: 404, description: 'Post inexistente ou não publicado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 429, description: 'Muitos envios'),
        ],
    )]
    public function store(): void {}
}
