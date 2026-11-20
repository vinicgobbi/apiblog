<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints de moderação de comentários.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Comentários', description: 'Moderação de comentários (requer autenticação)')]
#[OA\Schema(
    component: 'AdminComment',
    description: 'Comentário na visão do moderador, com os dados privados do autor.',
    properties: [
        'id' => Type::Integer,
        'author_name' => Type::String,
        'author_email' => new OA\Schema(type: Type::String, format: Format::Email),
        'body' => Type::String,
        'status' => new OA\Schema(type: Type::String, enum: ['pending', 'approved', 'rejected', 'spam']),
        'ip_address' => new OA\Schema(type: Type::String, nullable: true),
        'user_agent' => new OA\Schema(type: Type::String, nullable: true),
        'post' => new OA\Schema(type: Type::Object, properties: ['id' => Type::Integer, 'title' => Type::String, 'slug' => Type::String]),
        'created_at' => Format::DateTime,
        'updated_at' => Format::DateTime,
    ],
)]
final class CommentDocs
{
    #[OA\Operation\Get(
        path: '/admin/comments',
        summary: 'Lista os comentários para moderação',
        description: 'Do mais recente para o mais antigo, 30 por página.',
        tags: ['Admin - Comentários'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [
            new OA\Parameter(name: 'status', in: ParameterIn::Query, schema: new OA\Schema(type: Type::String, enum: ['pending', 'approved', 'rejected', 'spam'])),
            new OA\Parameter(name: 'post_id', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer)),
            new OA\Parameter(name: 'page', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer, minimum: 1, default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/AdminComment')),
                    'links' => new OA\Schema(ref: '#/components/schemas/PaginationLinks'),
                    'meta' => new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Filtro inválido'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Get(
        path: '/admin/comments/counts',
        summary: 'Quantidade de comentários por status',
        tags: ['Admin - Comentários'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Totais por status',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Object, properties: [
                        'pending' => Type::Integer,
                        'approved' => Type::Integer,
                        'rejected' => Type::Integer,
                        'spam' => Type::Integer,
                    ]),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function counts(): void {}

    #[OA\Operation\Get(
        path: '/admin/comments/{id}',
        summary: 'Retorna um comentário',
        tags: ['Admin - Comentários'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Comentário', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminComment')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Comentário inexistente'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Patch(
        path: '/admin/comments/{id}',
        summary: 'Altera o status do comentário',
        description: 'Somente o status pode ser alterado; o texto do leitor nunca é editado. Aprovado = visível no blog.',
        tags: ['Admin - Comentários'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new Json(required: ['status'], properties: [
                'status' => new OA\Schema(type: Type::String, enum: ['pending', 'approved', 'rejected', 'spam']),
            ]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Comentário atualizado', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminComment')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Comentário inexistente'),
            new OA\Response(response: 422, description: 'Status inválido'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/comments/{id}',
        summary: 'Exclui o comentário',
        tags: ['Admin - Comentários'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Comentário excluído'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Comentário inexistente'),
        ],
    )]
    public function destroy(): void {}
}
