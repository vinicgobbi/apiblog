<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints administrativos de posts.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Posts', description: 'Gestão de posts (requer autenticação)')]
#[OA\Schema(
    component: 'AdminPost',
    description: 'Post na visão do admin. body_markdown e body_html só aparecem no detalhe.',
    properties: [
        'id' => Type::Integer,
        'title' => Type::String,
        'slug' => Type::String,
        'excerpt' => new OA\Schema(type: Type::String, nullable: true),
        'body_markdown' => Type::String,
        'body_html' => Type::String,
        'cover_image_path' => new OA\Schema(type: Type::String, nullable: true),
        'cover_image_url' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
        'status' => new OA\Schema(type: Type::String, enum: ['draft', 'published']),
        'state' => new OA\Schema(type: Type::String, enum: ['draft', 'scheduled', 'published'], description: 'Situação calculada a partir do status e da data de publicação'),
        'published_at' => new OA\Schema(type: Type::String, format: Format::DateTime, nullable: true),
        'reading_time' => Type::Integer,
        'author' => new OA\Schema(type: Type::Object, properties: ['id' => Type::Integer, 'name' => Type::String]),
        'category' => new OA\Schema(ref: '#/components/schemas/Category', nullable: true),
        'tags' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Tag')),
        'created_at' => Format::DateTime,
        'updated_at' => Format::DateTime,
    ],
)]
#[OA\Schema(
    component: 'AdminPostInput',
    description: 'Dados para criar ou editar um post.',
    required: ['title', 'body_markdown', 'status'],
    properties: [
        'title' => new OA\Schema(type: Type::String, maxLength: 255),
        'slug' => new OA\Schema(type: Type::String, maxLength: 255, description: 'Opcional; gerado a partir do título. É normalizado (ex.: "Meu Post" vira "meu-post")'),
        'excerpt' => new OA\Schema(type: Type::String, maxLength: 500, nullable: true),
        'body_markdown' => new OA\Schema(type: Type::String, maxLength: 200000),
        'category_id' => new OA\Schema(type: Type::Integer, nullable: true),
        'cover_image_path' => new OA\Schema(type: Type::String, maxLength: 255, nullable: true),
        'status' => new OA\Schema(type: Type::String, enum: ['draft', 'published']),
        'published_at' => new OA\Schema(type: Type::String, format: Format::DateTime, nullable: true, description: 'Ao publicar sem data, a publicação é imediata; data futura agenda o post'),
        'tags' => new OA\Schema(type: Type::Array, maxItems: 20, items: new OA\Schema(type: Type::String, maxLength: 50), description: 'Nomes das tags; as inexistentes são criadas. Omitir o campo mantém as tags atuais'),
    ],
)]
final class PostDocs
{
    #[OA\Operation\Get(
        path: '/admin/posts',
        summary: 'Lista todos os posts (rascunhos, agendados e publicados)',
        tags: ['Admin - Posts'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [
            new OA\Parameter(name: 'state', in: ParameterIn::Query, schema: new OA\Schema(type: Type::String, enum: ['draft', 'scheduled', 'published'])),
            new OA\Parameter(name: 'q', in: ParameterIn::Query, description: 'Busca no título e no resumo', schema: new OA\Schema(type: Type::String, maxLength: 100)),
            new OA\Parameter(name: 'per_page', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer, minimum: 1, maximum: 100, default: 20)),
            new OA\Parameter(name: 'page', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer, minimum: 1, default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/AdminPost')),
                    'links' => new OA\Schema(ref: '#/components/schemas/PaginationLinks'),
                    'meta' => new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Parâmetros inválidos'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/admin/posts',
        summary: 'Cria um post',
        description: 'O autor é o usuário autenticado.',
        tags: ['Admin - Posts'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminPostInput')),
        responses: [
            new OA\Response(response: 201, description: 'Post criado', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPost')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function store(): void {}

    #[OA\Operation\Get(
        path: '/admin/posts/{id}',
        summary: 'Retorna um post com o Markdown original',
        tags: ['Admin - Posts'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Post', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPost')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Post inexistente ou na lixeira'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Put(
        path: '/admin/posts/{id}',
        summary: 'Atualiza um post',
        tags: ['Admin - Posts'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminPostInput')),
        responses: [
            new OA\Response(response: 200, description: 'Post atualizado', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPost')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Post inexistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/posts/{id}',
        summary: 'Move o post para a lixeira',
        description: 'Soft delete: o post sai do blog, mas o slug continua reservado.',
        tags: ['Admin - Posts'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Post removido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Post inexistente'),
        ],
    )]
    public function destroy(): void {}
}
