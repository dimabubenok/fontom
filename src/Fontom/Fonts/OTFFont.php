<?php

namespace Fontom\Fonts;

use Fontom\Abstracts\Font;

/**
 * Class OTFFont
 * Handles OTF font files.
 */
class OTFFont extends Font
{
    /**
     * Checks if the given file is an OTF font.
     *
     * @param string $filePath The path to the font file.
     * @return bool True if the file is an OTF font, otherwise false.
     */
    public static function supports(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'otf';
    }

    /**
     * Gets the name of the OTF font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        return "OTF Font Name";
    }

    /**
     * Gets the author or designer of the OTF font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        return "OTF Font Author";
    }
}
