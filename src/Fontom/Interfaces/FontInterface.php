<?php

namespace Fontom\Interfaces;

/**
 * Interface FontInterface
 * Defines the basic methods for font handling.
 */
interface FontInterface
{
    /**
     * Get the name of the font.
     *
     * @return string
     */
    public function getFontName(): string;

    /**
     * Get the author of the font.
     *
     * @return string
     */
    public function getFontAuthor(): string;

    /**
     * Get a list of tables in the font file.
     *
     * @return array
     */
    public function getTables(): array;
}
