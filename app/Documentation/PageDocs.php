<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints públicos de páginas estáticas.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Pages', description: 'Páginas estáticas (ex.: Sobre)')]
#[OA\Schema(
    component: 'Page',
    description: 'Página estática publicada. body_html e updated_at só aparecem no detalhe.',
    properties: [
        'id' => Type::Integer,
        'title' => Type::String,
        'slug' => Type::String,
        'icon' => new OA\Schema(type: Type::String, description: 'Nome do ícone do Bootstrap Icons, sem o prefixo "bi-" (ex.: person)'),
        'body_html' => new OA\Schema(type: Type::String, description: 'Corpo em HTML já renderizado e sanitizado'),
        'updated_at' => Format::DateTime,
    ],
)]
final class PageDocs
{
    #[OA\Operation\Get(
        path: '/pages',
        summary: 'Lista as páginas publicadas (para o menu do blog)',
        tags: ['Pages'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Páginas publicadas na ordem definida no admin, sem o corpo',
                content: new Json(properties: ['data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Page'))]),
            ),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Get(
        path: '/pages/{slug}',
        summary: 'Retorna uma página publicada',
        tags: ['Pages'],
        parameters: [
            new OA\Parameter(name: 'slug', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::String)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Página',
                content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Page')]),
            ),
            new OA\Response(response: 404, description: 'Página inexistente ou não publicada'),
        ],
    )]
    public function show(): void {}
}
