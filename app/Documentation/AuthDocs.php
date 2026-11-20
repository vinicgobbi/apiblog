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
final class AuthDocs
{
    #[OA\Post(
        path: '/auth/login',
        summary: 'Autenticação de usuário',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'password'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'usuario.teste'),
                    new OA\Property(property: 'password', type: 'string', example: 'senha123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autenticado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciais inválidas'
            ),
        ]
    )]
    public function login(): void {}

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Atualiza o token de autenticação',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token atualizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string'),
                        new OA\Property(property: 'refresh_token', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function refresh(): void {}

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Encerra a sessão do usuário autenticado',
        tags: ['Authentication'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout realizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Successfully logged out'),
                    ]
                )
            ),
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
                description: 'Dados do usuário recuperados',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Data retrieved successfully'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function user(): void {}
}
