<?php

namespace App\Documentation;

use OpenApi\Attributes as OA;

/**
 * Metadados globais do OpenAPI (info, server, esquema de segurança).
 *
 * Classe usada apenas como alvo para os atributos do swagger-php durante
 * a geração da documentação — nunca é instanciada nem referenciada em runtime.
 */
#[OA\Info(
    title: \APP_NAME,
    version: \API_VERSION,
)]
#[OA\Server(
    url: \APP_URL,
    description: 'URL da aplicação'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
final class ApiDocumentation
{
    //
}
