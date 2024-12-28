<?php

namespace Fontom;

use Fontom\Factory\FontFactory;
use Fontom\Interfaces\FontInterface;
use GdImage;

/**
 * Class Fontom
 * Main entry point for the Fontom library.
 */
class Fontom
{
    /**
     * The loaded font instance.
     *
     * @var FontInterface
     */
    private FontInterface $font;

    /**
     * Fontom constructor.
     *
     * @param string $filePath Path to the font file.
     * @throws \Exception If the font format is not supported.
     */
    public function __construct(string $filePath)
    {
        $this->font = FontFactory::load($filePath);
    }

    /**
     * Gets the name of the loaded font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        return $this->font->getFontName();
    }

    /**
     * Gets the author or designer of the loaded font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        return $this->font->getFontAuthor();
    }

    /**
     * Gets all name records from the loaded font.
     *
     * @return array The name records with descriptions.
     */
    public function getAllNameRecords(): array
    {
        if (method_exists($this->font, 'getAllNameRecords')) {
            return $this->font->getAllNameRecords();
        }

        throw new \Exception("The current font type does not support retrieving all name records.");
    }

    /**
     * Gets the number of glyphs in the loaded font.
     *
     * @return int The number of glyphs in the font.
     */
    public function getNumberOfGlyphs(): int
    {
        if (method_exists($this->font, 'getNumberOfGlyphs')) {
            return $this->font->getNumberOfGlyphs();
        }

        throw new \Exception("The current font type does not support retrieving the number of glyphs.");
    }

    /**
     * Renders an image with the specified text using the loaded font.
     *
     * @param string $text The text to render.
     * @param int $fontSize The size of the font.
     * @param int $imageWidth The width of the image.
     * @param int $imageHeight The height of the image.
     * @param array $textColor RGB array of text color (e.g., [0, 0, 0] for black).
     * @param array $backgroundColor RGB array of background color (e.g., [255, 255, 255] for white).
     * @return GdImage The generated image.
     * @throws \Exception If rendering fails.
     */
    public function renderTextImage(
        string $text,
        int $fontSize = 20,
        int $imageWidth = 400,
        int $imageHeight = 100,
        array $textColor = [0, 0, 0],
        array $backgroundColor = [255, 255, 255]
    ): GdImage {
        if (method_exists($this->font, 'renderTextImage')) {
            return $this->font->renderTextImage(
                $text,
                $fontSize,
                $imageWidth,
                $imageHeight,
                $textColor,
                $backgroundColor
            );
        }

        throw new \Exception("The current font type does not support rendering images.");
    }

    /**
     * Renders an image containing all glyphs in the loaded font.
     *
     * @param int $glyphSize The size of each glyph in the image.
     * @param int $columns The number of glyphs per row.
     * @param array $textColor RGB array of text color (e.g., [0, 0, 0] for black).
     * @param array $backgroundColor RGB array of background color (e.g., [255, 255, 255] for white).
     * @return GdImage The generated image.
     * @throws \Exception If rendering fails.
     */
    public function renderGlyphsImage(
        int $glyphSize = 50,
        int $columns = 10,
        array $textColor = [0, 0, 0],
        array $backgroundColor = [255, 255, 255]
    ): GdImage {
        if (method_exists($this->font, 'renderGlyphsImage')) {
            return $this->font->renderGlyphsImage(
                $glyphSize,
                $columns,
                $textColor,
                $backgroundColor
            );
        }

        throw new \Exception("The current font type does not support rendering glyphs.");
    }
}
