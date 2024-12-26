<?php

namespace Fontom\Interfaces;

/**
 * Interface FontInterface
 * Defines the contract for font classes.
 */
interface FontInterface
{
    /**
     * Gets the name of the font.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string;
    /**
     * Gets the author or designer of the font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string;
}
