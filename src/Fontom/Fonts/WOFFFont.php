<?php

namespace Fontom\Fonts;

use Fontom\Abstracts\Font;

/**
 * Class WOFFFont
 * Handles WOFF font files.
 */
class WOFFFont extends Font
{
    /**
     * Checks if the given file is a WOFF font.
     *
     * @param string $filePath The path to the font file.
     * @return bool True if the file is a WOFF font, otherwise false.
     */
    public static function supports(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'woff';
    }

    /**
     * Gets the name of the WOFF font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        return "WOFF Font Name";
    }

    /**
     * Gets the author or designer of the WOFF font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        return "WOFF Font Author";
    }
}
