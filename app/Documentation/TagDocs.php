<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints públicos de tags.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Tags', description: 'Tags dos posts')]
#[OA\Schema(
    component: 'Tag',
    description: 'Tag de posts.',
    properties: [
        'id' => Type::Integer,
        'name' => Type::String,
        'slug' => Type::String,
        'posts_count' => new OA\Schema(type: Type::Integer, description: 'Quantidade de posts publicados (presente na listagem e no detalhe)'),
    ],
)]
final class TagDocs
{
    #[OA\Operation\Get(
        path: '/tags',
        summary: 'Lista as tags com posts publicados',
        tags: ['Tags'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tags em ordem alfabética',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Tag')),
                ]),
            ),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Get(
        path: '/tags/{slug}',
        summary: 'Retorna uma tag',
        tags: ['Tags'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tag',
                content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Tag')]),
            ),
            new OA\Response(response: 404, description: 'Tag inexistente'),
        ],
    )]
    public function show(): void {}
}
