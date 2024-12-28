<?php

namespace Fontom\Fonts;

use Fontom\Abstracts\Font;
use Fontom\Rendering\TextRenderer;


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
     * Gets the file path of the TTF font.
     *
     * @return string The file path of the font.
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * Gets the name of the TTF font by reading the 'name' table in the TTF file.
     *
     * @return string The name of the font.
     */
    public function getFontName(): string
    {
        $nameTable = $this->readNameTable();
        $fontName = "Unknown Font Name";

        foreach ($nameTable['records'] as $record) {
            if ($record['nameId'] === 1 && $record['platformId'] === 3) {
                $fontName = mb_convert_encoding($record['value'], 'UTF-8', 'UTF-16BE');
                break;
            }
        }

        return $fontName;
    }

    /**
     * Gets the author or designer of the TTF font.
     *
     * @return string The author or designer of the font.
     */
    public function getFontAuthor(): string
    {
        $nameTable = $this->readNameTable();
        $author = "Unknown Author";

        foreach ($nameTable['records'] as $record) {
            if ($record['nameId'] === 9 && $record['platformId'] === 3) {
                $author = mb_convert_encoding($record['value'], 'UTF-8', 'UTF-16BE');
                break;
            }
        }

        return $author;
    }

    /**
     * Gets all name records from the 'name' table.
     *
     * @return array An array of all name records with descriptions.
     */
    public function getAllNameRecords(): array
    {
        $nameTable = $this->readNameTable();
        $nameIdDescriptions = [
            0 => 'Copyright Notice',
            1 => 'Font Family Name',
            2 => 'Font Subfamily Name',
            3 => 'Unique Font Identifier',
            4 => 'Full Font Name',
            5 => 'Version String',
            6 => 'PostScript Name',
            7 => 'Trademark',
            8 => 'Manufacturer',
            9 => 'Designer',
            10 => 'Description',
            11 => 'URL Vendor',
            12 => 'URL Designer',
            13 => 'License Description',
            14 => 'License Info URL',
            16 => 'Typographic Family Name',
            17 => 'Typographic Subfamily Name',
            18 => 'Compatible Full Name',
            19 => 'Sample Text',
        ];

        $recordsWithDescriptions = [];
        foreach ($nameTable['records'] as $record) {
            if ($record['platformId'] === 3) {
                $decodedValue = mb_convert_encoding($record['value'], 'UTF-8', 'UTF-16BE');
            } elseif ($record['platformId'] === 1) {
                $decodedValue = iconv('MacRoman', 'UTF-8', $record['value']);
            } else {
                $decodedValue = $record['value'];
            }

            $nameId = $record['nameId'];
            $recordsWithDescriptions[] = [
                'nameId' => $nameId,
                'nameDescription' => $nameIdDescriptions[$nameId] ?? "Unknown NameID ($nameId)",
                'value' => $decodedValue,
                'platformId' => $record['platformId'],
            ];
        }

        return $recordsWithDescriptions;
    }

    /**
     * Reads the 'name' table from the TTF font file.
     *
     * @return array Parsed name table with records.
     */
    private function readNameTable(): array
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new \Exception("Unable to open font file: {$this->filePath}");
        }

        $data = fread($handle, filesize($this->filePath));
        fclose($handle);

        $sfntVersion = substr($data, 0, 4);
        if ($sfntVersion !== "\x00\x01\x00\x00" && $sfntVersion !== "OTTO") {
            throw new \Exception("Invalid TTF file format.");
        }

        $numTables = unpack('n', substr($data, 4, 2))[1];
        $offsetTable = 12;
        $glyfTableOffset = null;
        $nameTableOffset = null;
        $nameTableLength = null;

        for ($i = 0; $i < $numTables; $i++) {
            $entry = substr($data, $offsetTable + ($i * 16), 16);
            $tag = substr($entry, 0, 4);
            $offset = unpack('N', substr($entry, 8, 4))[1];
            $length = unpack('N', substr($entry, 12, 4))[1];

            if ($tag === 'name') {
                $nameTableOffset = $offset;
                $nameTableLength = $length;
            }

            if ($tag === 'maxp') {
                $glyfTableOffset = $offset;
            }
        }

        if ($nameTableOffset === null) {
            throw new \Exception("Name table not found in font file.");
        }

        $nameTable = substr($data, $nameTableOffset, $nameTableLength);
        $count = unpack('n', substr($nameTable, 2, 2))[1];
        $stringOffset = unpack('n', substr($nameTable, 4, 2))[1];

        $records = [];
        $recordOffset = 6;

        for ($i = 0; $i < $count; $i++) {
            $platformId = unpack('n', substr($nameTable, $recordOffset, 2))[1];
            $nameId = unpack('n', substr($nameTable, $recordOffset + 6, 2))[1];
            $length = unpack('n', substr($nameTable, $recordOffset + 8, 2))[1];
            $stringOffsetEntry = unpack('n', substr($nameTable, $recordOffset + 10, 2))[1];

            $value = substr($nameTable, $stringOffset + $stringOffsetEntry, $length);

            $records[] = [
                'platformId' => $platformId,
                'nameId' => $nameId,
                'value' => $value,
            ];

            $recordOffset += 12;
        }

        return ['records' => $records];
    }

    /**
     * Reads the 'maxp' table from the TTF font file.
     *
     * @return int The number of glyphs in the font.
     */
    private function readMaxpTable(): int
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new \Exception("Unable to open font file: {$this->filePath}");
        }

        $data = fread($handle, filesize($this->filePath));
        fclose($handle);

        $sfntVersion = substr($data, 0, 4);
        if ($sfntVersion !== "\x00\x01\x00\x00" && $sfntVersion !== "OTTO") {
            throw new \Exception("Invalid TTF file format.");
        }

        $numTables = unpack('n', substr($data, 4, 2))[1];
        $offsetTable = 12;
        $maxpOffset = null;

        for ($i = 0; $i < $numTables; $i++) {
            $entry = substr($data, $offsetTable + ($i * 16), 16);
            $tag = substr($entry, 0, 4);
            $offset = unpack('N', substr($entry, 8, 4))[1];

            if ($tag === 'maxp') {
                $maxpOffset = $offset;
                break;
            }
        }

        if ($maxpOffset === null) {
            throw new \Exception("maxp table not found in font file.");
        }

        $maxpTable = substr($data, $maxpOffset, 6);
        return unpack('n', substr($maxpTable, 4, 2))[1];
    }

    /**
     * Gets the number of glyphs in the font.
     *
     * @return int The number of glyphs in the font.
     */
    public function getNumberOfGlyphs(): int
    {
        return $this->readMaxpTable();
    }

    /**
     * Creates an image with rendered text using the TextRenderer.
     *
     * @param string $text The text to render.
     * @param int $fontSize The size of the font.
     * @param int $imageWidth The width of the image.
     * @param int $imageHeight The height of the image.
     * @param array $textColor RGB array of text color (e.g., [0, 0, 0] for black).
     * @param array $backgroundColor RGB array of background color (e.g., [255, 255, 255] for white).
     * @return \GdImage The generated image.
     * @throws \Exception If rendering fails.
     */
    public function renderTextImage(
        string $text,
        int $fontSize = 10,
        int $imageWidth = 1400,
        int $imageHeight = 500,
        array $textColor = [0, 0, 0],
        array $backgroundColor = [255, 255, 255]
    ): \GdImage {
        $renderer = new TextRenderer($this);
        return $renderer->renderText(
            $text,
            $fontSize,
            $imageWidth,
            $imageHeight,
            $textColor,
            $backgroundColor
        );
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
    ): \GdImage {
        $renderer = new TextRenderer($this);
        return $renderer->renderGlyphs(
            $glyphSize,
            $columns,
            $textColor,
            $backgroundColor
        );
    }

    public function getCmapTable(): array
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new \Exception("Unable to open font file: {$this->filePath}");
        }

        $data = fread($handle, filesize($this->filePath));
        fclose($handle);

        $numTables = unpack('n', substr($data, 4, 2))[1];
        $offsetTable = 12;

        $cmapOffset = null;

        // Найти таблицу cmap
        for ($i = 0; $i < $numTables; $i++) {
            $entry = substr($data, $offsetTable + ($i * 16), 16);
            $tag = substr($entry, 0, 4);
            $offset = unpack('N', substr($entry, 8, 4))[1];

            if ($tag === 'cmap') {
                $cmapOffset = $offset;
                break;
            }
        }

        if ($cmapOffset === null) {
            throw new \Exception("cmap table not found in font file.");
        }

        // Читаем таблицу cmap
        $cmapHeader = substr($data, $cmapOffset, 4);
        $numSubtables = unpack('n', substr($cmapHeader, 2, 2))[1];

        $bestSubtableOffset = null;

        // Ищем лучшую подтаблицу (формат 4 или 12 для Unicode)
        for ($i = 0; $i < $numSubtables; $i++) {
            $subtableEntry = substr($data, $cmapOffset + 4 + ($i * 8), 8);
            $platformId = unpack('n', substr($subtableEntry, 0, 2))[1];
            $encodingId = unpack('n', substr($subtableEntry, 2, 2))[1];
            $subtableOffset = unpack('N', substr($subtableEntry, 4, 4))[1];

            if ($platformId === 3 && ($encodingId === 1 || $encodingId === 10)) { // Windows Unicode
                $bestSubtableOffset = $cmapOffset + $subtableOffset;
                break;
            }
        }

        if ($bestSubtableOffset === null) {
            throw new \Exception("No suitable cmap subtable found.");
        }

        // Парсим подтаблицу формата 4
        $subtable = substr($data, $bestSubtableOffset);
        $format = unpack('n', substr($subtable, 0, 2))[1];

        if ($format !== 4) {
            throw new \Exception("Only cmap format 4 is supported.");
        }

        $segCountX2 = unpack('n', substr($subtable, 6, 2))[1];
        $segCount = $segCountX2 / 2;

        $endCodeOffset = 14;
        $startCodeOffset = $endCodeOffset + ($segCount * 2) + 2;
        $idDeltaOffset = $startCodeOffset + ($segCount * 2);
        $idRangeOffsetOffset = $idDeltaOffset + ($segCount * 2);

        $cmap = [];

        for ($i = 0; $i < $segCount - 1; $i++) { // Последний сегмент обычно 0xFFFF
            $endCode = unpack('n', substr($subtable, $endCodeOffset + ($i * 2), 2))[1];
            $startCode = unpack('n', substr($subtable, $startCodeOffset + ($i * 2), 2))[1];
            $idDelta = unpack('n', substr($subtable, $idDeltaOffset + ($i * 2), 2))[1];
            $idRangeOffset = unpack('n', substr($subtable, $idRangeOffsetOffset + ($i * 2), 2))[1];

            if ($idDelta >= 0x8000) {
                $idDelta -= 0x10000;
            }

            for ($code = $startCode; $code <= $endCode; $code++) {
                if ($idRangeOffset === 0) {
                    $glyphIndex = ($code + $idDelta) & 0xFFFF;
                } else {
                    $rangeOffset = $idRangeOffset / 2 + ($code - $startCode);
                    $glyphIndex = unpack('n', substr($subtable, $idRangeOffsetOffset + ($i * 2) + ($rangeOffset * 2), 2))[1];
                    if ($glyphIndex !== 0) {
                        $glyphIndex = ($glyphIndex + $idDelta) & 0xFFFF;
                    }
                }

                $cmap[$code] = $glyphIndex;
            }
        }

        return $cmap;
    }
}
