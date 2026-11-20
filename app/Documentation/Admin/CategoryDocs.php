<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints administrativos de categorias.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Categorias', description: 'Gestão de categorias (requer autenticação)')]
#[OA\Schema(
    component: 'AdminCategoryInput',
    description: 'Dados para criar ou editar uma categoria.',
    required: ['name'],
    properties: [
        'name' => new OA\Schema(type: Type::String, maxLength: 100),
        'slug' => new OA\Schema(type: Type::String, maxLength: 255, description: 'Opcional; gerado a partir do nome e normalizado'),
        'description' => new OA\Schema(type: Type::String, maxLength: 500, nullable: true),
    ],
)]
final class CategoryDocs
{
    #[OA\Operation\Get(
        path: '/admin/categories',
        summary: 'Lista todas as categorias',
        tags: ['Admin - Categorias'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categorias em ordem alfabética, com a contagem de posts em qualquer situação',
                content: new Json(properties: ['data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Category'))]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/admin/categories',
        summary: 'Cria uma categoria',
        tags: ['Admin - Categorias'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminCategoryInput')),
        responses: [
            new OA\Response(response: 201, description: 'Categoria criada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Category')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function store(): void {}

    #[OA\Operation\Get(
        path: '/admin/categories/{id}',
        summary: 'Retorna uma categoria',
        tags: ['Admin - Categorias'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Categoria', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Category')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Categoria inexistente'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Put(
        path: '/admin/categories/{id}',
        summary: 'Atualiza uma categoria',
        tags: ['Admin - Categorias'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminCategoryInput')),
        responses: [
            new OA\Response(response: 200, description: 'Categoria atualizada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Category')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Categoria inexistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/categories/{id}',
        summary: 'Exclui uma categoria',
        description: 'Os posts da categoria ficam sem categoria.',
        tags: ['Admin - Categorias'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Categoria excluída'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Categoria inexistente'),
        ],
    )]
    public function destroy(): void {}
}
