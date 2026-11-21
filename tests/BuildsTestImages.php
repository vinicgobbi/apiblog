<?php

namespace Tests;

/**
 * Gera imagens com metadados (EXIF com GPS, chunks de texto) para os testes
 * de remoção de metadados.
 */
trait BuildsTestImages
{
    /**
     * Marcador gravado nos metadados, para conferir que não sobrou no arquivo.
     */
    protected const string METADATA_MARKER = 'LOCALIZACAO-SECRETA';

    /**
     * JPEG com metade esquerda vermelha e direita azul, com bloco EXIF contendo
     * a orientação informada, coordenadas GPS e o marcador.
     */
    protected function jpegWithExif(int $width = 20, int $height = 10, int $orientation = 1): string
    {
        $jpeg = $this->encodeImage($this->twoColorImage($width, $height), 'imagejpeg');
        $tiff = $this->exifTiff($orientation);
        $app1 = "\xFF\xE1".pack('n', strlen($tiff) + 8)."Exif\0\0".$tiff;

        return substr($jpeg, 0, 2).$app1.substr($jpeg, 2);
    }

    protected function pngWithTextChunks(int $width = 20, int $height = 10): string
    {
        $image = $this->twoColorImage($width, $height);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagesetpixel($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        $png = $this->encodeImage($image, 'imagepng');

        $chunk = fn (string $type, string $data): string => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $iendPosition = strrpos($png, 'IEND') - 4;

        return substr($png, 0, $iendPosition)
            .$chunk('tEXt', 'Comment'."\0".self::METADATA_MARKER)
            .$chunk('eXIf', $this->exifTiff(1))
            .substr($png, $iendPosition);
    }

    protected function webpWithExifChunk(int $width = 20, int $height = 10): string
    {
        $webp = $this->encodeImage($this->twoColorImage($width, $height), 'imagewebp');
        $exif = $this->exifTiff(1);
        $body = substr($webp, 12).'EXIF'.pack('V', strlen($exif)).$exif.(strlen($exif) % 2 ? "\0" : '');

        return 'RIFF'.pack('V', strlen($body) + 4).'WEBP'.$body;
    }

    /**
     * Bloco TIFF (big-endian) com IFD0 (Orientation, ImageDescription e ponteiro GPS) e IFD GPS.
     */
    private function exifTiff(int $orientation): string
    {
        $description = self::METADATA_MARKER."\0";
        $ifd0Offset = 8;
        $ifd0Size = 2 + 3 * 12 + 4;
        $descriptionOffset = $ifd0Offset + $ifd0Size;
        $gpsOffset = $descriptionOffset + strlen($description);

        $ifd0 = pack('n', 3)
            .pack('nnNN', 0x010E, 2, strlen($description), $descriptionOffset)
            .pack('nnNnn', 0x0112, 3, 1, $orientation, 0)
            .pack('nnNN', 0x8825, 4, 1, $gpsOffset)
            .pack('N', 0);

        $gps = pack('n', 1)
            .pack('nnN', 0x0001, 2, 2).'S'."\0\0\0"
            .pack('N', 0);

        return "MM\x00\x2A".pack('N', $ifd0Offset).$ifd0.$description.$gps;
    }

    private function twoColorImage(int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
        imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, 255));

        return $image;
    }

    /**
     * @param  callable(\GdImage): bool  $encoder
     */
    private function encodeImage(\GdImage $image, callable $encoder): string
    {
        ob_start();
        $encoder($image);

        return (string) ob_get_clean();
    }

    /**
     * @return array{red: int, green: int, blue: int, alpha: int}
     */
    protected function pixelAt(string $content, int $x, int $y): array
    {
        $image = imagecreatefromstring($content);

        return imagecolorsforindex($image, imagecolorat($image, $x, $y));
    }
}
