<?php

namespace Tests\Feature\Services;

use App\Services\ImageSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\BuildsTestImages;
use Tests\TestCase;

class ImageSanitizerTest extends TestCase
{
    use BuildsTestImages;

    private ImageSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sanitizer = app(ImageSanitizer::class);
    }

    public function test_removes_exif_with_gps_from_jpeg(): void
    {
        $path = $this->tempFile($this->jpegWithExif());
        $this->assertArrayHasKey('GPSLatitudeRef', exif_read_data($path), 'A imagem de teste deveria ter GPS.');

        $this->sanitizer->sanitize($path, 'image/jpeg');

        $content = (string) file_get_contents($path);
        $this->assertStringNotContainsString(self::METADATA_MARKER, $content);
        $this->assertStringNotContainsString("Exif\0\0", $content);
        $this->assertArrayNotHasKey('GPSLatitudeRef', @exif_read_data($path) ?: []);
        $this->assertSame([20, 10], array_slice(getimagesize($path), 0, 2));
    }

    /**
     * Imagem armazenada: esquerda vermelha, direita azul. Cada orientação diz
     * como ela deve ser exibida; após o processamento, os pixels já estão assim.
     *
     * @return array<string, array{int, array{int, int}, string, string}>
     */
    public static function orientations(): array
    {
        return [
            '1 (normal)' => [1, [20, 10], 'left', 'red'],
            '3 (180°)' => [3, [20, 10], 'left', 'blue'],
            '6 (90° horário)' => [6, [10, 20], 'top', 'red'],
            '8 (90° anti-horário)' => [8, [10, 20], 'top', 'blue'],
            '2 (espelhada)' => [2, [20, 10], 'left', 'blue'],
        ];
    }

    /**
     * @param  array{int, int}  $expectedSize
     */
    #[DataProvider('orientations')]
    public function test_applies_exif_orientation_to_pixels(int $orientation, array $expectedSize, string $edge, string $expectedColor): void
    {
        $path = $this->tempFile($this->jpegWithExif(orientation: $orientation));

        $this->sanitizer->sanitize($path, 'image/jpeg');

        [$width, $height] = getimagesize($path);
        $this->assertSame($expectedSize, [$width, $height]);

        [$x, $y] = $edge === 'left' ? [1, intdiv($height, 2)] : [intdiv($width, 2), 1];
        $pixel = $this->pixelAt((string) file_get_contents($path), $x, $y);
        $this->assertSame($expectedColor, $pixel['red'] > $pixel['blue'] ? 'red' : 'blue');
    }

    public function test_removes_text_and_exif_chunks_from_png_keeping_transparency(): void
    {
        $path = $this->tempFile($this->pngWithTextChunks());

        $this->sanitizer->sanitize($path, 'image/png');

        $content = (string) file_get_contents($path);
        $this->assertStringNotContainsString(self::METADATA_MARKER, $content);
        $this->assertStringNotContainsString('eXIf', $content);
        $this->assertStringNotContainsString('tEXt', $content);
        $this->assertSame(127, $this->pixelAt($content, 0, 0)['alpha']);
        $this->assertSame(255, $this->pixelAt($content, 1, 1)['red']);
    }

    public function test_removes_exif_chunk_from_webp(): void
    {
        $path = $this->tempFile($this->webpWithExifChunk());

        $this->sanitizer->sanitize($path, 'image/webp');

        $content = (string) file_get_contents($path);
        $this->assertStringNotContainsString(self::METADATA_MARKER, $content);
        $this->assertSame([20, 10], array_slice(getimagesize($path), 0, 2));
    }

    public function test_leaves_gif_untouched(): void
    {
        $gif = "GIF89a\x01\x00\x01\x00\x80\x00\x00\x00\x00\x00\xff\xff\xff!\xf9\x04\x01\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
        $path = $this->tempFile($gif);

        $this->sanitizer->sanitize($path, 'image/gif');

        $this->assertSame($gif, file_get_contents($path));
    }

    public function test_throws_when_image_cannot_be_decoded(): void
    {
        $path = $this->tempFile("\xFF\xD8\xFF\xE0corrompido");

        $this->expectException(RuntimeException::class);

        $this->sanitizer->sanitize($path, 'image/jpeg');
    }

    private function tempFile(string $content): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'img');
        file_put_contents($path, $content);
        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
