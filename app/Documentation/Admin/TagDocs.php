<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints administrativos de tags.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Tags', description: 'Gestão de tags (requer autenticação)')]
#[OA\Schema(
    component: 'AdminTagInput',
    description: 'Dados para criar ou editar uma tag.',
    required: ['name'],
    properties: [
        'name' => new OA\Schema(type: Type::String, maxLength: 50),
        'slug' => new OA\Schema(type: Type::String, maxLength: 255, description: 'Opcional; gerado a partir do nome e normalizado'),
    ],
)]
final class TagDocs
{
    #[OA\Operation\Get(
        path: '/admin/tags',
        summary: 'Lista todas as tags',
        tags: ['Admin - Tags'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tags em ordem alfabética, com a contagem de posts em qualquer situação',
                content: new Json(properties: ['data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Tag'))]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/admin/tags',
        summary: 'Cria uma tag',
        tags: ['Admin - Tags'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminTagInput')),
        responses: [
            new OA\Response(response: 201, description: 'Tag criada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Tag')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function store(): void {}

    #[OA\Operation\Get(
        path: '/admin/tags/{id}',
        summary: 'Retorna uma tag',
        tags: ['Admin - Tags'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Tag', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Tag')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Tag inexistente'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Put(
        path: '/admin/tags/{id}',
        summary: 'Atualiza uma tag',
        tags: ['Admin - Tags'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(required: true, content: new Json(ref: '#/components/schemas/AdminTagInput')),
        responses: [
            new OA\Response(response: 200, description: 'Tag atualizada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Tag')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Tag inexistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/tags/{id}',
        summary: 'Exclui uma tag',
        description: 'A tag é removida de todos os posts.',
        tags: ['Admin - Tags'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Tag excluída'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Tag inexistente'),
        ],
    )]
    public function destroy(): void {}
}
