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
        $this->assertEqualsCanonicalizing(['/auth/login', '/auth/refresh', '/auth/logout', '/auth/user'], array_keys($docs['paths']));
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
