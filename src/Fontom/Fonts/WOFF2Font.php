<?php

namespace Fontom\Fonts;

use Fontom\Abstracts\Font;

/**
 * Class WOFF2Font
 * Handles WOFF2 font files.
 */
class WOFF2Font extends Font
{
    /**
     * Checks if the given file is a WOFF2 font.
     *
     * @param string $filePath The path to the font file.
     * @return bool True if the file is a WOFF2 font, otherwise false.
     */
    public static function supports(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'woff2';
    }

    /**
     * Gets the name of the WOFF2 font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        return "WOFF2 Font Name";
    }

    /**
     * Gets the author or designer of the WOFF2 font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        return "WOFF2 Font Author";
    }
}
