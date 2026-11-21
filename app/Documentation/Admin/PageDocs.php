<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints administrativos de páginas.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Páginas', description: 'Gestão de páginas (requer autenticação)')]
#[OA\Schema(
    component: 'AdminPageInput',
    description: 'Dados para criar ou editar uma página.',
    required: ['title', 'icon', 'body_markdown'],
    properties: [
        'title' => new OA\Schema(type: Type::String, maxLength: 255),
        'slug' => new OA\Schema(type: Type::String, maxLength: 255, description: 'Opcional; gerado a partir do título e normalizado. Não pode ser uma rota do front (admin, busca, categorias, posts, tags)'),
        'icon' => new OA\Schema(type: Type::String, maxLength: 64, pattern: '^[a-z0-9]+(-[a-z0-9]+)*$', description: 'Nome do ícone do Bootstrap Icons, sem o prefixo "bi-" (ex.: person)'),
        'body_markdown' => new OA\Schema(type: Type::String, maxLength: 200000),
        'is_published' => new OA\Schema(type: Type::Boolean, default: false),
    ],
)]
#[OA\Schema(
    component: 'AdminPage',
    description: 'Página na visão do admin. body_markdown e body_html só aparecem no detalhe.',
    properties: [
        'id' => Type::Integer,
        'title' => Type::String,
        'slug' => Type::String,
        'icon' => Type::String,
        'body_markdown' => Type::String,
        'body_html' => Type::String,
        'is_published' => Type::Boolean,
        'created_at' => Format::DateTime,
        'updated_at' => Format::DateTime,
    ],
)]
final class PageDocs
{
    #[OA\Operation\Get(
        path: '/admin/pages',
        summary: 'Lista todas as páginas (publicadas ou não)',
        tags: ['Admin - Páginas'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Páginas em ordem alfabética, sem o corpo',
                content: new Json(properties: ['data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/AdminPage'))]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/admin/pages',
        summary: 'Cria uma página',
        tags: ['Admin - Páginas'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminPageInput')),
        responses: [
            new OA\Response(response: 201, description: 'Página criada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPage')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function store(): void {}

    #[OA\Operation\Get(
        path: '/admin/pages/{id}',
        summary: 'Retorna uma página',
        tags: ['Admin - Páginas'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Página', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPage')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Página inexistente'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Put(
        path: '/admin/pages/{id}',
        summary: 'Atualiza uma página',
        tags: ['Admin - Páginas'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminPageInput')),
        responses: [
            new OA\Response(response: 200, description: 'Página atualizada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/AdminPage')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Página inexistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/pages/{id}',
        summary: 'Exclui uma página',
        description: 'Exclusão definitiva.',
        tags: ['Admin - Páginas'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Página excluída'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Página inexistente'),
        ],
    )]
    public function destroy(): void {}
}
