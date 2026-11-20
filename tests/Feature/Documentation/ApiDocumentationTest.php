<?php

namespace Tests\Feature\Documentation;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    private string $docsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docsPath = sys_get_temp_dir().'/blog-api-docs-'.uniqid();
        config(['l5-swagger.defaults.paths.docs' => $this->docsPath]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->docsPath);

        parent::tearDown();
    }

    public function test_generates_openapi_document_from_spec_attributes(): void
    {
        $docs = $this->generateDocs();

        $this->assertSame(config('app.name'), $docs['info']['title']);
        $this->assertSame(['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'], $docs['components']['securitySchemes']['bearerAuth']);
        foreach ([
            '/auth/login', '/auth/refresh', '/auth/logout', '/auth/user',
            '/posts', '/posts/{slug}', '/posts/{slug}/comments', '/categories', '/categories/{slug}', '/tags', '/tags/{slug}', '/pages/{slug}',
            '/admin/posts', '/admin/posts/{id}', '/admin/categories', '/admin/categories/{id}',
            '/admin/tags', '/admin/tags/{id}', '/admin/pages', '/admin/pages/{id}', '/admin/markdown/preview',
        ] as $path) {
            $this->assertArrayHasKey($path, $docs['paths'], "{$path} não está documentado");
        }
    }

    public function test_every_admin_operation_requires_bearer_token(): void
    {
        $adminPaths = array_filter($this->generateDocs()['paths'], fn (string $path): bool => str_starts_with($path, '/admin/'), ARRAY_FILTER_USE_KEY);

        $this->assertNotEmpty($adminPaths);
        foreach ($adminPaths as $path => $operations) {
            foreach ($operations as $method => $operation) {
                $this->assertSame([['bearerAuth' => []]], $operation['security'] ?? null, strtoupper($method)." {$path} deveria exigir bearer token");
            }
        }
    }

    public function test_public_endpoints_do_not_require_authentication(): void
    {
        $paths = $this->generateDocs()['paths'];

        foreach (['/posts', '/posts/{slug}', '/posts/{slug}/comments', '/categories', '/tags', '/pages/{slug}'] as $path) {
            $this->assertArrayNotHasKey('security', $paths[$path]['get'], "{$path} não deveria exigir autenticação");
        }

        $this->assertArrayNotHasKey('security', $paths['/posts/{slug}/comments']['post']);
    }

    public function test_documents_login_contract(): void
    {
        $login = $this->generateDocs()['paths']['/auth/login']['post'];

        $requestSchema = $login['requestBody']['content']['application/json']['schema'];
        $this->assertSame(['email', 'password'], $requestSchema['required']);
        $this->assertSame('email', $requestSchema['properties']['email']['format']);
        $this->assertSame('#/components/schemas/AuthToken', $login['responses']['200']['content']['application/json']['schema']['$ref']);
        $this->assertEqualsCanonicalizing(['200', '401', '422', '429'], array_map('strval', array_keys($login['responses'])));
    }

    public function test_protected_endpoints_require_bearer_token(): void
    {
        $paths = $this->generateDocs()['paths'];

        $this->assertSame([['bearerAuth' => []]], $paths['/auth/user']['get']['security']);
        $this->assertSame([['bearerAuth' => []]], $paths['/auth/logout']['post']['security']);
        $this->assertArrayNotHasKey('security', $paths['/auth/login']['post']);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateDocs(): array
    {
        $this->artisan('l5-swagger:generate')->assertSuccessful();

        return json_decode(File::get($this->docsPath.'/api-docs.json'), true, flags: JSON_THROW_ON_ERROR);
    }
}
