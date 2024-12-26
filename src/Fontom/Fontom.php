<?php

namespace Fontom;

use Fontom\Factory\FontFactory;
use Fontom\Interfaces\FontInterface;

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
}
