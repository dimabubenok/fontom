<?php

namespace Fontom;

use Fontom\Interfaces\FontInterface;

/**
 * Class Fontom
 * A PHP library for handling font files (initially TTF format).
 */
class Fontom implements FontInterface
{
    /** @var string Path to the font file */
    private $filePath;

    /** @var resource File pointer resource */
    private $filePointer;

    /**
     * Fontom constructor.
     * @param string $filePath Path to the TTF font file.
     * @throws \Exception If the file cannot be opened.
     */
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        $this->openFile();
    }

    /**
     * Factory method to create an instance of Fontom.
     * @param string $filePath Path to the TTF font file.
     * @return self
     * @throws \Exception If the file does not exist or cannot be opened.
     */
    public static function load(string $filePath): self
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File does not exist: $filePath");
        }
        return new self($filePath);
    }

    /**
     * Opens the TTF font file.
     * @throws \Exception If the file cannot be opened.
     */
    private function openFile(): void
    {
        $this->filePointer = fopen($this->filePath, 'rb');
        if (!$this->filePointer) {
            throw new \Exception("Unable to open font file: " . $this->filePath);
        }
    }

    /**
     * Reads the font file and extracts the font name.
     * @return string Font name.
     */
    public function getFontName(): string
    {
        $nameTable = $this->readNameTable();
        foreach ($nameTable as $record) {
            if ($record['nameId'] === 1) {
                return $record['string'];
            }
        }
        return "Unknown Font Name";
    }

    /**
     * Reads the font file and extracts the font author's name.
     * @return string Font author.
     */
    public function getFontAuthor(): string
    {
        $author = $this->readNameRecord(17);
        if ($author === "Unknown Record #9") {
            $manufacturer = $this->readNameRecord(8);
            if ($manufacturer !== "Unknown Record #8") {
                return $manufacturer;
            }
        }
        return $author;
    }

    /**
     * Reads all tables in the font file.
     * @return array List of tables found in the font file.
     */
    public function getTables(): array
    {
        // Implementation placeholder: actual parsing logic needed.
        return ["cmap", "glyf", "head", "hhea", "hmtx", "maxp", "name", "post"];
    }

    /**
     * Reads a specific name record by ID, considering different platform IDs.
     * @param int $nameId The ID of the name record (e.g., 1 = Font Name, 9 = Font Author).
     * @return string
     */
    private function readNameRecord(int $nameId): string
    {
        $nameTable = $this->readNameTable();
        foreach ($nameTable as $record) {
            if ($record['nameId'] === $nameId) {
                // Handle potential encoding issues based on platformId
                if ($record['platformId'] === 3) {
                    return mb_convert_encoding($record['string'], 'UTF-8', 'UTF-16BE');
                } elseif ($record['platformId'] === 1) {
                    return $record['string'];
                }
                return $record['string'];
            }
        }
        return "Unknown Record #$nameId";
    }

    /**
     * Reads the name table from the TTF font file.
     * @return array Parsed name table records.
     */
    private function readNameTable(): array
    {
        fseek($this->filePointer, 0);
        $data = fread($this->filePointer, filesize($this->filePath));

        // Проверка сигнатуры TTF-файла
        $sfntVersion = substr($data, 0, 4);
        if ($sfntVersion !== "\x00\x01\x00\x00" && $sfntVersion !== "OTTO") {
            throw new \Exception("Invalid TTF file.");
        }

        // Количество таблиц
        $numTables = unpack('n', substr($data, 4, 2))[1];

        // Читаем Offset Table для поиска 'name' таблицы
        $offsetTable = 12;
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
                break;
            }
        }

        if ($nameTableOffset === null) {
            throw new \Exception("Name table not found in font file.");
        }

        // Теперь читаем 'name' таблицу по найденному смещению
        fseek($this->filePointer, $nameTableOffset);
        $nameTable = fread($this->filePointer, $nameTableLength);

        $count = unpack('n', substr($nameTable, 2, 2))[1];
        $stringOffset = unpack('n', substr($nameTable, 4, 2))[1];

        $records = [];
        $recordOffset = 6;

        for ($i = 0; $i < $count; $i++) {
            $platformId = unpack('n', substr($nameTable, $recordOffset, 2))[1];
            $encodingId = unpack('n', substr($nameTable, $recordOffset + 2, 2))[1];
            $languageId = unpack('n', substr($nameTable, $recordOffset + 4, 2))[1];
            $nameId = unpack('n', substr($nameTable, $recordOffset + 6, 2))[1];
            $length = unpack('n', substr($nameTable, $recordOffset + 8, 2))[1];
            $stringOffsetEntry = unpack('n', substr($nameTable, $recordOffset + 10, 2))[1];

            $string = substr($nameTable, $stringOffset + $stringOffsetEntry, $length);

            $records[] = [
                'platformId' => $platformId,
                'encodingId' => $encodingId,
                'languageId' => $languageId,
                'nameId'     => $nameId,
                'string'     => $string
            ];

            $recordOffset += 12;
        }

        return $records;
    }

    /**
     * Reads all name records and returns them as an array with NameID names.
     *
     * @return array An array of name records, each containing 'id', 'name', and 'value'.
     * @throws \Exception If the name table is not found or the file is invalid.
     */
    public function getAllNameRecords(): array
    {
        $nameTable = $this->readNameTable();
        $allRecords = [];

        foreach ($nameTable as $record) {
            $nameId = $record['nameId'];
            $allRecords[] = [
                'id'    => $nameId,
                'name'  => $this->getNameIdDescription($nameId),
                'value' => $this->decodeString($record),
            ];
        }

        return $allRecords;
    }

    /**
     * Returns a human-readable description for a given NameID.
     *
     * @param int $nameId The NameID.
     * @return string Description of the NameID.
     */
    private function getNameIdDescription(int $nameId): string
    {
        $nameIdMap = [
            0  => 'Copyright Notice',
            1  => 'Font Family Name',
            2  => 'Font Subfamily Name',
            3  => 'Unique Font Identifier',
            4  => 'Full Font Name',
            5  => 'Version String',
            6  => 'PostScript Name',
            7  => 'Trademark',
            8  => 'Manufacturer',
            9  => 'Designer',
            10 => 'Description',
            11 => 'URL Vendor',
            12 => 'URL Designer',
            13 => 'License Description',
            14 => 'License Info URL',
            15 => 'Reserved',
            16 => 'Typographic Family Name',
            17 => 'Typographic Subfamily Name',
            18 => 'Compatible Full Name',
            19 => 'Sample Text',
            20 => 'PostScript CID Findfont Name',
            21 => 'WWS Family Name',
            22 => 'WWS Subfamily Name',
            23 => 'Light Background Palette',
            24 => 'Dark Background Palette',
            25 => 'Variations PostScript Name Prefix',
        ];

        return $nameIdMap[$nameId] ?? "Unknown NameID ($nameId)";
    }

    /**
     * Decodes a string based on platformId.
     *
     * @param array $record The name record.
     * @return string Decoded string.
     */
    private function decodeString(array $record): string
    {
        if ($record['platformId'] === 3) {
            return mb_convert_encoding($record['string'], 'UTF-8', 'UTF-16BE');
        } elseif ($record['platformId'] === 1) {
            return $record['string'];
        }
        return $record['string'];
    }
}
