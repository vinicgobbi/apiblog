<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints públicos de posts.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Posts', description: 'Posts publicados do blog')]
#[OA\Schema(
    component: 'Post',
    description: 'Resumo de um post publicado, usado nas listagens.',
    properties: [
        'id' => Type::Integer,
        'title' => Type::String,
        'slug' => Type::String,
        'excerpt' => new OA\Schema(type: Type::String, nullable: true),
        'cover_image_url' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
        'published_at' => Format::DateTime,
        'reading_time' => new OA\Schema(type: Type::Integer, description: 'Tempo estimado de leitura, em minutos', example: 4),
        'author' => new OA\Schema(type: Type::Object, properties: ['name' => Type::String]),
        'category' => new OA\Schema(ref: '#/components/schemas/Category', nullable: true),
        'tags' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Tag')),
    ],
)]
#[OA\Schema(
    component: 'PostDetail',
    description: 'Post completo, com o corpo em HTML já renderizado e sanitizado.',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/Post'),
        new OA\Schema(properties: [
            'body_html' => Type::String,
            'updated_at' => Format::DateTime,
        ]),
    ],
)]
#[OA\Schema(
    component: 'PaginationLinks',
    description: 'Links de navegação da paginação.',
    properties: [
        'first' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
        'last' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
        'prev' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
        'next' => new OA\Schema(type: Type::String, format: Format::Uri, nullable: true),
    ],
)]
#[OA\Schema(
    component: 'PaginationMeta',
    description: 'Metadados da paginação.',
    properties: [
        'current_page' => Type::Integer,
        'from' => new OA\Schema(type: Type::Integer, nullable: true),
        'last_page' => Type::Integer,
        'per_page' => Type::Integer,
        'to' => new OA\Schema(type: Type::Integer, nullable: true),
        'total' => Type::Integer,
    ],
)]
final class PostDocs
{
    #[OA\Operation\Get(
        path: '/posts',
        summary: 'Lista os posts publicados',
        description: 'Ordenados do mais recente para o mais antigo. Rascunhos e posts agendados não aparecem.',
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'tag', in: ParameterIn::Query, description: 'Slug da tag', schema: new OA\Schema(type: Type::String)),
            new OA\Parameter(name: 'category', in: ParameterIn::Query, description: 'Slug da categoria', schema: new OA\Schema(type: Type::String)),
            new OA\Parameter(name: 'q', in: ParameterIn::Query, description: 'Busca no título e no resumo', schema: new OA\Schema(type: Type::String, maxLength: 100)),
            new OA\Parameter(name: 'per_page', in: ParameterIn::Query, description: 'Posts por página', schema: new OA\Schema(type: Type::Integer, minimum: 1, maximum: 50, default: 10)),
            new OA\Parameter(name: 'page', in: ParameterIn::Query, description: 'Página', schema: new OA\Schema(type: Type::Integer, minimum: 1, default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada de posts',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Post')),
                    'links' => new OA\Schema(ref: '#/components/schemas/PaginationLinks'),
                    'meta' => new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 422, description: 'Parâmetros inválidos'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Get(
        path: '/posts/{slug}',
        summary: 'Retorna um post publicado',
        tags: ['Posts'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Post completo',
                content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/PostDetail')]),
            ),
            new OA\Response(response: 404, description: 'Post inexistente, rascunho ou agendado'),
        ],
    )]
    public function show(): void {}
}
