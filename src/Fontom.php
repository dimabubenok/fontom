<?php

namespace Fontom;

/**
 * Class Fontom
 * A PHP library for handling font files (initially TTF format).
 */
class Fontom
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
        return $this->readNameRecord(2);
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
    private function readNameRecord(int $nameId): string
    {
        $nameTable = $this->readNameTable();
        foreach ($nameTable as $record) {
            if ($record['nameId'] === $nameId) {
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
        
        $offset = strpos($data, 'name');
        if ($offset === false) {
            throw new \Exception("Name table not found in font file.");
        }

        $offset += 6; // Skip 'name' tag and table header
        $count = unpack('n', substr($data, $offset, 2))[1];
        $stringOffset = unpack('n', substr($data, $offset + 2, 2))[1];

        $records = [];
        $recordOffset = $offset + 4;

        for ($i = 0; $i < $count; $i++) {
            $platformId = unpack('n', substr($data, $recordOffset, 2))[1];
            $encodingId = unpack('n', substr($data, $recordOffset + 2, 2))[1];
            $languageId = unpack('n', substr($data, $recordOffset + 4, 2))[1];
            $nameId = unpack('n', substr($data, $recordOffset + 6, 2))[1];
            $length = unpack('n', substr($data, $recordOffset + 8, 2))[1];
            $stringOffsetEntry = unpack('n', substr($data, $recordOffset + 10, 2))[1];
            
            $string = substr($data, $offset + $stringOffset + $stringOffsetEntry, $length);
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