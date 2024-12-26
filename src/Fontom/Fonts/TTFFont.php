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
}
