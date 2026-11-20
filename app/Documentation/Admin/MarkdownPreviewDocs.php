<?php

namespace App\Documentation\Admin;

use OpenApi\Spec as OA;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação do endpoint de pré-visualização de Markdown.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Tag(name: 'Admin - Markdown', description: 'Pré-visualização do editor (requer autenticação)')]
final class MarkdownPreviewDocs
{
    #[OA\Operation\Post(
        path: '/admin/markdown/preview',
        summary: 'Renderiza Markdown em HTML',
        description: 'Usa o mesmo renderizador dos posts: o resultado é idêntico ao que será publicado.',
        tags: ['Admin - Markdown'],
        security: [new Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: new Json(
                required: ['markdown'],
                properties: ['markdown' => new OA\Schema(type: Type::String, maxLength: 200000, nullable: true, example: '## Olá **mundo**')],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'HTML renderizado e sanitizado',
                content: new Json(properties: ['html' => Type::String]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Campo markdown ausente ou longo demais'),
        ],
    )]
    public function __invoke(): void {}
}
