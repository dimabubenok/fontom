<?php

namespace Fontom\Abstracts;

use Fontom\Interfaces\FontInterface;

/**
 * Abstract class Font
 * Base class for handling font files.
 */
abstract class Font implements FontInterface
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    abstract public static function supports(string $filePath): bool;
}
