<?php

namespace Fontom\Fonts;

use Fontom\Abstracts\Font;

/**
 * Class TTFFont
 * Handles TTF font files.
 */
class TTFFont extends Font
{
    /**
     * Checks if the given file is a TTF font.
     *
     * @param string $filePath The path to the font file.
     * @return bool True if the file is a TTF font, otherwise false.
     */
    public static function supports(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'ttf';
    }

    /**
     * Gets the name of the TTF font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        return "TTF Font Name";
    }

    /**
     * Gets the author or designer of the TTF font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        return "TTF Font Author";
    }
}
