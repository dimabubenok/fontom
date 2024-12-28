<?php

namespace Fontom\Rendering;

use Fontom\Interfaces\FontInterface;
use GDImage;

class TextRenderer
{
    /**
     * @var FontInterface The font instance used for rendering.
     */
    private FontInterface $font;

    /**
     * TextRenderer constructor.
     *
     * @param FontInterface $font The font instance to use for rendering.
     */
    public function __construct(FontInterface $font)
    {
        $this->font = $font;
    }

    /**
     * Renders text to an image using the provided font.
     *
     * @param string $text The text to render.
     * @param int $fontSize The size of the font.
     * @param int $imageWidth The width of the image.
     * @param int $imageHeight The height of the image.
     * @param array $textColor RGB array of text color (e.g., [0, 0, 0] for black).
     * @param array $backgroundColor RGB array of background color (e.g., [255, 255, 255] for white).
     * @return GDImage The generated image.
     * @throws \Exception If GD is not installed or the font file cannot be used.
     */
    public function renderText(
        string $text,
        int $fontSize = 20,
        int $imageWidth = 400,
        int $imageHeight = 100,
        array $textColor = [0, 0, 0],
        array $backgroundColor = [255, 255, 255]
    ): GDImage {
        if (!function_exists('imagettftext')) {
            throw new \Exception("The GD library with TTF support is required to render text.");
        }

        // Create an image
        $image = imagecreatetruecolor($imageWidth, $imageHeight);

        // Allocate colors
        $bgColor = imagecolorallocate($image, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        $txtColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);

        // Fill the background
        imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $bgColor);

        // Calculate Y position as an integer to avoid precision loss
        $yPosition = (int) ($imageHeight / 2 + $fontSize / 2);

        // Render text
        $boundingBox = imagettftext(
            $image,
            $fontSize,
            0, // Angle
            10, // X position
            $yPosition, // Y position
            $txtColor,
            $this->font->getFilePath(),
            $text
        );

        if (!$boundingBox) {
            throw new \Exception("Failed to render text with the font file: " . $this->font->getFilePath());
        }

        return $image;
    }

    /**
     * Generates an image containing each glyph of the font, skipping empty glyphs.
     *
     * @param int $glyphSize The size of each glyph in the image.
     * @param int $columns The number of glyphs per row.
     * @param array $textColor RGB array of text color (e.g., [0, 0, 0] for black).
     * @param array $backgroundColor RGB array of background color (e.g., [255, 255, 255] for white).
     * @return GDImage The generated image.
     * @throws \Exception If rendering fails.
     */
    public function renderGlyphs(
        int $glyphSize = 50,
        int $columns = 10,
        array $textColor = [0, 0, 0],
        array $backgroundColor = [255, 255, 255]
    ): GDImage {
        $cmap = $this->font->getCmapTable(); // Получаем таблицу cmap из шрифта
        $filteredCmap = [];

        // Проверка глифов на содержимое
        foreach ($cmap as $unicode => $glyphIndex) {
            $char = mb_convert_encoding(pack('n', $unicode), 'UTF-8', 'UTF-16BE');

            // Создаем временное изображение для проверки глифа
            $tempImage = imagecreatetruecolor($glyphSize, $glyphSize);
            $bgColor = imagecolorallocate($tempImage, 255, 255, 255);
            $txtColor = imagecolorallocate($tempImage, 0, 0, 0);
            imagefilledrectangle($tempImage, 0, 0, $glyphSize, $glyphSize, $bgColor);

            $boundingBox = imagettftext(
                $tempImage,
                $glyphSize / 2,
                0,
                10,
                $glyphSize - 10,
                $txtColor,
                $this->font->getFilePath(),
                $char
            );

            // Проверяем, рисует ли глиф что-либо (boundingBox должен быть непустым)
            if ($boundingBox && ($boundingBox[2] - $boundingBox[0]) > 0 && ($boundingBox[3] - $boundingBox[5]) > 0) {
                $filteredCmap[$unicode] = $glyphIndex;
            }

            imagedestroy($tempImage);
        }

        $numGlyphs = count($filteredCmap);
        $rows = (int) ceil($numGlyphs / $columns);
        $imageWidth = $columns * $glyphSize;
        $imageHeight = $rows * $glyphSize;

        if (!function_exists('imagettftext')) {
            throw new \Exception("The GD library with TTF support is required to render text.");
        }

        // Create an image
        $image = imagecreatetruecolor($imageWidth, $imageHeight);

        // Allocate colors
        $bgColor = imagecolorallocate($image, $backgroundColor[0], $backgroundColor[1], $backgroundColor[2]);
        $txtColor = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);

        // Fill the background
        imagefilledrectangle($image, 0, 0, $imageWidth, $imageHeight, $bgColor);

        // Render each glyph
        $i = 0;
        foreach ($filteredCmap as $unicode => $glyphIndex) {
            $row = (int) floor($i / $columns);
            $col = $i % $columns;

            $xPosition = $col * $glyphSize + 10;
            $yPosition = $row * $glyphSize + $glyphSize - 10;

            $char = mb_convert_encoding(pack('n', $unicode), 'UTF-8', 'UTF-16BE');

            imagettftext(
                $image,
                $glyphSize / 2,
                0, // Angle
                $xPosition,
                $yPosition,
                $txtColor,
                $this->font->getFilePath(),
                $char
            );

            $i++;
        }

        return $image;
    }

    /**
     * Saves a GDImage to a file.
     *
     * @param GDImage $image The image to save.
     * @param string $filePath The file path where the image will be saved.
     * @param string $format The format of the image (e.g., 'png', 'jpeg').
     * @throws \Exception If the format is unsupported or saving fails.
     */
    public function saveImage(GDImage $image, string $filePath, string $format = 'png'): void
    {
        switch (strtolower($format)) {
            case 'png':
                if (!imagepng($image, $filePath)) {
                    throw new \Exception("Failed to save image as PNG at: $filePath");
                }
                break;

            case 'jpeg':
            case 'jpg':
                if (!imagejpeg($image, $filePath)) {
                    throw new \Exception("Failed to save image as JPEG at: $filePath");
                }
                break;

            case 'gif':
                if (!imagegif($image, $filePath)) {
                    throw new \Exception("Failed to save image as GIF at: $filePath");
                }
                break;

            default:
                throw new \Exception("Unsupported image format: $format");
        }
    }
}
