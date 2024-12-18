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
        // Implementation placeholder: actual parsing logic needed.
        return $this->readNameRecord(3);
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
     * Reads a specific name record by ID (placeholder for actual parsing logic).
     * @param int $nameId The ID of the name record (1 = Font Name, 2 = Font Author).
     * @return string
     */
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

            // Конвертация строки из UTF-16BE в UTF-8
            if ($platformId == 0 || ($platformId == 3 && $encodingId == 1)) {
                $string = mb_convert_encoding($string, 'UTF-8', 'UTF-16BE');
            }

            $records[] = [
                'platformId' => $platformId,
                'encodingId' => $encodingId,
                'languageId' => $languageId,
                'nameId' => $nameId,
                'string' => $string
            ];

            $recordOffset += 12;
        }

        return $records;
    }

    /**
     * Closes the font file when the object is destroyed.
     */
    public function __destruct()
    {
        if ($this->filePointer) {
            fclose($this->filePointer);
        }
    }
}
