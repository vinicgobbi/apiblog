<?php

namespace App\Documentation;

use OpenApi\Attributes as OA;

/**
 * Documentação dos endpoints de autenticação.
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime,
 * e não precisa ser implementada por nenhum controller.
 */
#[OA\Tag(name: 'Authentication', description: 'Documentação dos endpoints de autenticação')]
#[OA\Schema(
    schema: 'AuthToken',
    properties: [
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(property: 'token_type', type: 'string', example: 'bearer'),
        new OA\Property(property: 'expires_in', type: 'integer', description: 'Validade do token em segundos', example: 7200),
    ]
)]
final class AuthDocs
{
    #[OA\Post(
        path: '/auth/login',
        summary: 'Autenticação do administrador',
        description: 'Limitado a 5 tentativas por minuto.',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@blog.local'),
                    new OA\Property(property: 'password', type: 'string', example: 'senha123'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Autenticado com sucesso', content: new OA\JsonContent(ref: '#/components/schemas/AuthToken')),
            new OA\Response(response: 401, description: 'Credenciais inválidas'),
            new OA\Response(response: 422, description: 'Dados inválidos'),
            new OA\Response(response: 429, description: 'Muitas tentativas'),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Troca o token atual por um novo',
        description: 'Aceita tokens de acesso expirados dentro da janela de refresh (JWT_REFRESH_TTL). O token antigo é invalidado.',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Token atualizado com sucesso', content: new OA\JsonContent(ref: '#/components/schemas/AuthToken')),
            new OA\Response(response: 401, description: 'Token ausente, inválido ou fora da janela de refresh'),
        ]
    )]
    public function refresh(): void {}

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Encerra a sessão invalidando o token',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Logout realizado com sucesso.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function logout(): void {}

    #[OA\Get(
        path: '/auth/user',
        summary: 'Retorna os dados do usuário autenticado',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dados do usuário',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Administrador'),
                                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@blog.local'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function user(): void {}
}
