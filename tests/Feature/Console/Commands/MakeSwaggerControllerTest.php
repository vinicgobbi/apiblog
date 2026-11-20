<?php

namespace Tests\Feature\Console\Commands;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MakeSwaggerControllerTest extends TestCase
{
    private string $folder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->folder = 'GeradoEmTeste'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(app_path("Http/Controllers/{$this->folder}"));
        File::deleteDirectory(app_path("Documentation/{$this->folder}"));

        parent::tearDown();
    }

    public function test_generates_loadable_controller_and_spec_documentation_in_sub_namespace(): void
    {
        $this->artisan('make:swagger', ['name' => "{$this->folder}/ExemploController"])->assertSuccessful();

        $controllerPath = app_path("Http/Controllers/{$this->folder}/ExemploController.php");
        $docsPath = app_path("Documentation/{$this->folder}/ExemploDocs.php");

        require_once $controllerPath;
        $this->assertTrue(is_subclass_of("App\\Http\\Controllers\\{$this->folder}\\ExemploController", Controller::class));
        $this->assertStringContainsString('use OpenApi\Spec as OA;', File::get($docsPath));
        $this->assertStringContainsString("namespace App\\Documentation\\{$this->folder};", File::get($docsPath));
    }

    public function test_refuses_to_overwrite_existing_controller(): void
    {
        $this->artisan('make:swagger', ['name' => "{$this->folder}/ExemploController"])->assertSuccessful();

        $this->artisan('make:swagger', ['name' => "{$this->folder}/ExemploController"])
            ->expectsOutput('O Controller ou o arquivo de documentação já existem!')
            ->assertFailed();
    }
}
