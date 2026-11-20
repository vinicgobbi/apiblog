<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints públicos de categorias.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Categories', description: 'Categorias dos posts')]
#[OA\Schema(
    component: 'Category',
    description: 'Categoria de posts.',
    properties: [
        'id' => Type::Integer,
        'name' => Type::String,
        'slug' => Type::String,
        'description' => new OA\Schema(type: Type::String, nullable: true),
        'posts_count' => new OA\Schema(type: Type::Integer, description: 'Quantidade de posts publicados (presente na listagem e no detalhe)'),
    ],
)]
final class CategoryDocs
{
    #[OA\Operation\Get(
        path: '/categories',
        summary: 'Lista as categorias com posts publicados',
        tags: ['Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categorias em ordem alfabética',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Category')),
                ]),
            ),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Get(
        path: '/categories/{slug}',
        summary: 'Retorna uma categoria',
        tags: ['Categories'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categoria',
                content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Category')]),
            ),
            new OA\Response(response: 404, description: 'Categoria inexistente'),
        ],
    )]
    public function show(): void {}
}
