<?php

namespace Fontom\Factory;

use Fontom\Interfaces\FontInterface;
use Fontom\Fonts\TTFFont;
use Fontom\Fonts\OTFFont;
use Fontom\Fonts\WOFFFont;
use Fontom\Fonts\WOFF2Font;

/**
 * Class FontFactory
 * Factory for loading font files.
 */
class FontFactory
{
    /**
     * List of supported font classes.
     *
     * @var string[]
     */
    private static array $fontClasses = [
        TTFFont::class,
        OTFFont::class,
        WOFFFont::class,
        WOFF2Font::class,
    ];

    /**
     * Loads a font file and returns an instance of the corresponding font class.
     *
     * @param string $filePath Path to the font file.
     * @return FontInterface The font instance.
     * @throws \Exception If the font format is not supported.
     */
    public static function load(string $filePath): FontInterface
    {
        foreach (self::$fontClasses as $fontClass) {
            if ($fontClass::supports($filePath)) {
                return new $fontClass($filePath);
            }
        }

        throw new \Exception("Unsupported font format: $filePath");
    }
}
