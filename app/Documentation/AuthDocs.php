<?php

namespace App\Documentation;

use OpenApi\Spec as OA;
use OpenApi\Spec\Format;
use OpenApi\Spec\MediaType\Json;
use OpenApi\Spec\Security\Requirement;
use OpenApi\Spec\Type;

/**
 * Documentação dos endpoints de autenticação.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime,
 * e não precisa ser implementada por nenhum controller.
 */
#[OA\Tag(name: 'Authentication', description: 'Documentação dos endpoints de autenticação')]
#[OA\Schema(
    component: 'AuthToken',
    description: 'Token de acesso JWT retornado pelo login e pelo refresh.',
    properties: [
        'access_token' => Type::String,
        'token_type' => new OA\Schema(type: Type::String, example: 'bearer'),
        'expires_in' => new OA\Schema(type: Type::Integer, description: 'Validade do token em segundos', example: 7200),
    ],
)]
final class AuthDocs
{
    #[OA\Operation\Post(
        path: '/auth/login',
        summary: 'Autenticação do administrador',
        description: 'Limitado a 5 tentativas por minuto.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new Json(
                required: ['email', 'password'],
                properties: [
                    'email' => new OA\Schema(type: Type::String, format: Format::Email, example: 'admin@blog.local'),
                    'password' => new OA\Schema(type: Type::String, example: 'senha123'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Autenticado com sucesso', content: new Json(ref: '#/components/schemas/AuthToken')),
            new OA\Response(response: 401, description: 'Credenciais inválidas'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 429, description: 'Muitas tentativas'),
        ],
    )]
    public function login(): void {}

    #[OA\Operation\Post(
        path: '/auth/refresh',
        summary: 'Troca o token atual por um novo',
        description: 'Aceita tokens de acesso expirados dentro da janela de refresh (JWT_REFRESH_TTL). O token antigo é invalidado.',
        tags: ['Authentication'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(response: 200, description: 'Token atualizado com sucesso', content: new Json(ref: '#/components/schemas/AuthToken')),
            new OA\Response(response: 401, description: 'Token ausente, inválido ou fora da janela de refresh'),
        ],
    )]
    public function refresh(): void {}

    #[OA\Operation\Post(
        path: '/auth/logout',
        summary: 'Encerra a sessão invalidando o token',
        tags: ['Authentication'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado com sucesso',
                content: new Json(properties: [
                    'message' => new OA\Schema(type: Type::String, example: 'Logout realizado com sucesso.'),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function logout(): void {}

    #[OA\Operation\Get(
        path: '/auth/user',
        summary: 'Retorna os dados do usuário autenticado',
        tags: ['Authentication'],
        security: [new Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados do usuário',
                content: new Json(properties: [
                    'data' => new OA\Schema(type: Type::Object, properties: [
                        'id' => new OA\Schema(type: Type::Integer, example: 1),
                        'name' => new OA\Schema(type: Type::String, example: 'Administrador'),
                        'email' => new OA\Schema(type: Type::String, format: Format::Email, example: 'admin@blog.local'),
                    ]),
                ]),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ],
    )]
    public function user(): void {}
}
