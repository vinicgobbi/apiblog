<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\ParameterIn;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints administrativos de mídia.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Mídia', description: 'Upload e gestão de imagens (requer autenticação)')]
#[OA\Schema(
    component: 'Media',
    description: 'Imagem enviada pelo admin.',
    properties: [
        'id' => Type::Integer,
        'url' => new OA\Schema(type: Type::String, format: Format::Uri),
        'path' => new OA\Schema(type: Type::String, description: 'Caminho no disco público; use em cover_image_path'),
        'original_name' => Type::String,
        'mime_type' => new OA\Schema(type: Type::String, example: 'image/png'),
        'size' => new OA\Schema(type: Type::Integer, description: 'Tamanho em bytes'),
        'width' => new OA\Schema(type: Type::Integer, nullable: true),
        'height' => new OA\Schema(type: Type::Integer, nullable: true),
        'alt' => new OA\Schema(type: Type::String, nullable: true),
        'markdown' => new OA\Schema(type: Type::String, description: 'Snippet pronto para o editor', example: '![Pôr do sol](http://localhost:8000/storage/media/2026/11/abc.png)'),
        'created_at' => Format::DateTime,
    ],
)]
final class MediaDocs
{
    #[OA\Operation\Get(
        path: '/admin/media',
        summary: 'Lista as imagens enviadas',
        description: 'Das mais recentes às mais antigas, 30 por página.',
        tags: ['Admin - Mídia'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'page', in: ParameterIn::Query, schema: new OA\Schema(type: Type::Integer, minimum: 1, default: 1))],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista paginada',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Array, items: new OA\Schema(ref: '#/components/schemas/Media')),
                    'links' => new OA\Schema(ref: '#/components/schemas/PaginationLinks'),
                    'meta' => new OA\Schema(ref: '#/components/schemas/PaginationMeta'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function index(): void {}

    #[OA\Operation\Post(
        path: '/admin/media',
        summary: 'Envia uma imagem',
        description: 'JPG, PNG, WebP ou GIF (tipo verificado pelo conteúdo), até 5 MB e 8000×8000 pixels. SVG não é aceito.',
        tags: ['Admin - Mídia'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['file'],
                    properties: [
                        'file' => new OA\Schema(type: Type::String, format: 'binary'),
                        'alt' => new OA\Schema(type: Type::String, maxLength: 255, description: 'Texto alternativo (acessibilidade)'),
                    ],
                ),
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Imagem armazenada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Media')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Arquivo ausente, de tipo não permitido, grande demais ou com dimensões excessivas'),
        ],
    )]
    public function store(): void {}

    #[OA\Operation\Get(
        path: '/admin/media/{id}',
        summary: 'Retorna uma imagem',
        tags: ['Admin - Mídia'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 200, description: 'Imagem', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Media')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Imagem inexistente'),
        ],
    )]
    public function show(): void {}

    #[OA\Operation\Put(
        path: '/admin/media/{id}',
        summary: 'Atualiza o texto alternativo',
        tags: ['Admin - Mídia'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        requestBody: new OA\RequestBody(
            required: true,
            content: new Json(required: ['alt'], properties: ['alt' => new OA\Schema(type: Type::String, maxLength: 255, nullable: true)]),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Imagem atualizada', content: new Json(properties: ['data' => new OA\Schema(ref: '#/components/schemas/Media')])),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Imagem inexistente'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
        ],
    )]
    public function update(): void {}

    #[OA\Operation\Delete(
        path: '/admin/media/{id}',
        summary: 'Exclui a imagem e o arquivo',
        description: 'Imagens usadas como capa ou citadas no conteúdo de posts (inclusive na lixeira) ou páginas não podem ser excluídas.',
        tags: ['Admin - Mídia'],
        security: [new Requirement(scheme: 'bearerAuth')],
        parameters: [new OA\Parameter(name: 'id', in: ParameterIn::Path, required: true, schema: new OA\Schema(type: Type::Integer))],
        responses: [
            new OA\Response(response: 204, description: 'Imagem excluída'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Imagem inexistente'),
            new OA\Response(response: 409, description: 'Imagem em uso'),
        ],
    )]
    public function destroy(): void {}
}
