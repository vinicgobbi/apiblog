<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIF 1×1 válido (o ambiente não tem a extensão GD para gerar imagens).
     */
    private const string GIF_1X1 = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->admin = User::factory()->create();
    }

    public function test_every_media_endpoint_returns_401_without_token(): void
    {
        $media = Media::factory()->create();

        $this->getJson('/api/admin/media')->assertUnauthorized();
        $this->postJson('/api/admin/media', [])->assertUnauthorized();
        $this->putJson("/api/admin/media/{$media->id}", [])->assertUnauthorized();
        $this->deleteJson("/api/admin/media/{$media->id}")->assertUnauthorized();
    }

    public function test_upload_stores_image_with_random_name_and_returns_markdown_snippet(): void
    {
        $this->freezeTime();

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/admin/media', [
            'file' => $this->pngUpload('Minha Foto.png', 640, 480),
            'alt' => 'Pôr do sol',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.original_name', 'Minha Foto.png')
            ->assertJsonPath('data.mime_type', 'image/png')
            ->assertJsonPath('data.width', 640)
            ->assertJsonPath('data.height', 480)
            ->assertJsonPath('data.alt', 'Pôr do sol');

        $media = Media::sole();
        $this->assertStringStartsWith('media/'.now()->format('Y/m').'/', $media->path);
        $this->assertStringNotContainsString('Minha Foto', $media->path);
        Storage::disk('public')->assertExists($media->path);
        $response->assertJsonPath('data.url', Storage::disk('public')->url($media->path))
            ->assertJsonPath('data.markdown', '![Pôr do sol]('.Storage::disk('public')->url($media->path).')');
    }

    public function test_accepts_gif(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => $this->upload('anim.gif', self::GIF_1X1)])
            ->assertCreated()
            ->assertJsonPath('data.mime_type', 'image/gif');
    }

    public function test_brackets_in_alt_text_are_escaped_in_markdown_snippet(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => $this->pngUpload('a.png'), 'alt' => 'gráfico [beta]'])
            ->assertCreated()
            ->assertJsonPath('data.markdown', fn (string $markdown): bool => str_starts_with($markdown, '![gráfico \[beta\]]('));
    }

    public function test_rejects_svg_with_422(): void
    {
        $svg = $this->upload('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => $svg])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'A imagem deve ser JPG, PNG, WebP ou GIF']);

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_rejects_script_disguised_with_image_extension(): void
    {
        $fake = $this->upload('foto.png', '<?php system($_GET["c"]); ?>');

        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => $fake])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'A imagem deve ser JPG, PNG, WebP ou GIF']);

        $this->assertDatabaseCount('media', 0);
    }

    public function test_rejects_file_larger_than_5_mb(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => UploadedFile::fake()->create('grande.png', 5121, 'image/png')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'A imagem pode ter no máximo 5 MB']);
    }

    public function test_rejects_image_above_maximum_dimensions(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', ['file' => $this->pngUpload('larga.png', 8001, 1)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'A imagem pode ter no máximo 8000×8000 pixels']);
    }

    public function test_missing_file_returns_422(): void
    {
        $this->actingAs($this->admin, 'api')
            ->postJson('/api/admin/media', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Selecione uma imagem']);
    }

    public function test_index_lists_newest_first_with_pagination(): void
    {
        $older = Media::factory()->create(['created_at' => now()->subDay()]);
        $newer = Media::factory()->create();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonPath('data.*.id', [$newer->id, $older->id])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_update_changes_alt_text(): void
    {
        $media = Media::factory()->create(['alt' => 'antigo']);

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/admin/media/{$media->id}", ['alt' => 'novo'])
            ->assertOk()
            ->assertJsonPath('data.alt', 'novo');
    }

    public function test_destroy_deletes_record_and_file(): void
    {
        $this->actingAs($this->admin, 'api')->postJson('/api/admin/media', ['file' => $this->pngUpload('a.png')])->assertCreated();
        $media = Media::sole();

        $this->actingAs($this->admin, 'api')->deleteJson("/api/admin/media/{$media->id}")->assertNoContent();

        $this->assertModelMissing($media);
        Storage::disk('public')->assertMissing($media->path);
    }

    public function test_destroy_returns_409_when_image_is_a_post_cover(): void
    {
        $media = Media::factory()->create(['path' => 'media/2026/11/capa.png']);
        Post::factory()->create(['cover_image_path' => 'media/2026/11/capa.png']);

        $this->assertDestroyIsBlocked($media);
    }

    public function test_destroy_returns_409_when_image_is_referenced_in_a_trashed_post_body(): void
    {
        $media = Media::factory()->create(['path' => 'media/2026/11/corpo.png']);
        Post::factory()->create(['body_markdown' => '![x](http://localhost/storage/media/2026/11/corpo.png)'])->delete();

        $this->assertDestroyIsBlocked($media);
    }

    public function test_destroy_returns_409_when_image_is_referenced_in_a_page(): void
    {
        $media = Media::factory()->create(['path' => 'media/2026/11/pagina.png']);
        Page::factory()->create(['body_markdown' => '![x](/storage/media/2026/11/pagina.png)']);

        $this->assertDestroyIsBlocked($media);
    }

    private function assertDestroyIsBlocked(Media $media): void
    {
        $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'A imagem está em uso em um post ou página e não pode ser excluída.');

        $this->assertModelExists($media);
    }

    private function pngUpload(string $name, int $width = 10, int $height = 10): UploadedFile
    {
        return $this->upload($name, $this->png($width, $height));
    }

    /**
     * Upload com arquivo real em disco. Diferente de UploadedFile::fake(), que
     * deduz o tipo pelo nome, aqui o tipo é detectado pelo conteúdo (fileinfo),
     * como acontece num upload de verdade.
     */
    private function upload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return new UploadedFile($path, $name, null, null, true);
    }

    /**
     * Gera um PNG RGB válido (preto) sem depender da extensão GD.
     */
    private function png(int $width, int $height): string
    {
        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $scanlines = str_repeat("\x00".str_repeat("\x00\x00\x00", $width), $height);

        return "\x89PNG\r\n\x1a\n"
            .$chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0))
            .$chunk('IDAT', gzcompress($scanlines))
            .$chunk('IEND', '');
    }
}
