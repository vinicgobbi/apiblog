<?php

namespace App\Services;

use enshrined\svgSanitize\Sanitizer as SvgSanitizer;
use GdImage;
use RuntimeException;

/**
 * Regrava imagens enviadas para remover metadados (EXIF, XMP, IPTC, chunks de
 * texto do PNG), que podem conter localização GPS e dados do aparelho.
 *
 * A imagem é decodificada e codificada de novo com GD, que não copia nenhum
 * metadado. Antes disso, a orientação EXIF é aplicada aos pixels, para que
 * fotos de celular não fiquem giradas. GIFs não são tocados: não têm EXIF e
 * o GD perderia a animação.
 *
 * SVGs passam por uma lista de tags e atributos permitidos, que remove
 * scripts, handlers de eventos (onload etc.), links javascript: e referências
 * a recursos externos, além de metadados como <metadata>.
 */
class ImageSanitizer
{
    private const int JPEG_QUALITY = 90;

    private const int WEBP_QUALITY = 90;

    /**
     * O GD usa ~4 bytes por pixel: uma imagem de 8000×8000 (o máximo aceito
     * em StoreMediaRequest) ocupa ~256 MB, e o imagerotate cria uma segunda
     * cópia antes de liberar a primeira.
     */
    public const string MEMORY_LIMIT = '768M';

    /**
     * Sobrescreve o arquivo com a versão sem metadados.
     *
     * @throws RuntimeException quando a imagem não pode ser decodificada ou gravada.
     */
    public function sanitize(string $path, string $mimeType): void
    {
        if ($mimeType === 'image/svg+xml') {
            $this->sanitizeSvg($path);

            return;
        }

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return;
        }

        $this->raiseMemoryLimit();

        $image = $this->decode($path, $mimeType);

        if ($mimeType === 'image/jpeg') {
            $image = $this->applyExifOrientation($image, $path);
        }

        $this->encode($image, $path, $mimeType);

        clearstatcache(true, $path);
    }

    /**
     * Regrava o SVG só com tags e atributos seguros.
     *
     * @throws RuntimeException quando o arquivo não é um XML válido.
     */
    private function sanitizeSvg(string $path): void
    {
        $sanitizer = new SvgSanitizer;
        $sanitizer->removeRemoteReferences(true);

        $clean = $sanitizer->sanitize((string) file_get_contents($path));

        if ($clean === false || $clean === '') {
            throw new RuntimeException('Não foi possível ler o SVG.');
        }

        if (file_put_contents($path, $clean) === false) {
            throw new RuntimeException('Não foi possível gravar o SVG.');
        }

        clearstatcache(true, $path);
    }

    /**
     * Sobe o memory_limit para MEMORY_LIMIT só nesta requisição, sem reduzir
     * um limite que já seja maior (ou ilimitado).
     */
    private function raiseMemoryLimit(): void
    {
        $current = (string) ini_get('memory_limit');

        if ($current === '-1' || ini_parse_quantity($current) >= ini_parse_quantity(self::MEMORY_LIMIT)) {
            return;
        }

        ini_set('memory_limit', self::MEMORY_LIMIT);
    }

    private function decode(string $path, string $mimeType): GdImage
    {
        $image = match ($mimeType) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
        };

        if (! $image instanceof GdImage) {
            throw new RuntimeException('Não foi possível ler a imagem.');
        }

        return $image;
    }

    private function encode(GdImage $image, string $path, string $mimeType): void
    {
        if ($mimeType !== 'image/jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $written = match ($mimeType) {
            'image/jpeg' => imagejpeg($image, $path, self::JPEG_QUALITY),
            'image/png' => imagepng($image, $path, 9),
            'image/webp' => imagewebp($image, $path, self::WEBP_QUALITY),
        };

        if (! $written) {
            throw new RuntimeException('Não foi possível gravar a imagem.');
        }
    }

    /**
     * Gira/espelha os pixels conforme a tag Orientation (1 a 8). O GD gira no
     * sentido anti-horário.
     */
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        $exif = @exif_read_data($path);
        $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;

        $rotation = match ($orientation) {
            3, 4 => 180,
            5, 6 => 270,
            7, 8 => 90,
            default => 0,
        };

        if ($rotation !== 0) {
            $rotated = imagerotate($image, $rotation, 0);

            if (! $rotated instanceof GdImage) {
                throw new RuntimeException('Não foi possível girar a imagem.');
            }

            $image = $rotated;
        }

        if (in_array($orientation, [2, 4, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }
}
