<?php

class AsyncDmlController {
    const SESSION_KEY = 'ASYNC_DML';
    const CSV_FILE = 'CSV_FILE';
    const CHUNK_SIZE = 1024;
    const FILE_EXP_SEC = 10 * 60;
    const COLUMN_NAMES = 'COLUMN_NAMES';

    function __construct(){
        if (array_key_exists(self::CSV_FILE, $_FILES)) {
            $handle = fopen($_FILES[self::CSV_FILE]['tmp_name'], 'rb');
            $this->setColumnNames($handle);
            $this->stashCsvFile($handle);
            fclose($handle);
        }
    }

    private function setColumnNames($handle) {
        rewind($handle);
        $this->writeSession(self::COLUMN_NAMES, str_getcsv(fgets($handle)));
    }

    private function getColumnNames($handle) {
        $this->readSession(self::COLUMN_NAMES);
    }

    private function csvFileSessionKey() {
        return self::CSV_FILE . ":" . session_id();
    }

    private function stashCsvFile($handle) {
        $this->stashFile($this->csvFileSessionKey(), $handle);
    }

    private function stashFile($key, $handle) {
        rewind($handle);
        redis()->setex($key, self::FILE_EXP_SEC, null);
        while (!feof($handle)) {
            $chunk = fread($handle, self::CHUNK_SIZE);
            redis()->append($key, $chunk);
        }
    }

    private function writeSession($subkey, $value) {
        $_SESSION[self::SESSION_KEY][$subkey] = $value;
    }

    private function readSession($subkey) {
        return $_SESSION[self::SESSION_KEY][$subkey];
    }
}