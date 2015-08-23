<?php

class AsyncDmlController {
    const SESSION_KEY = 'ASYNC_DML';
    const FILE_KEY = 'CSV_FILE';
    const RESET_KEY = 'RESET';
    const CHUNK_SIZE = 1024;
    const FILE_EXP_SEC = 10 * 60;
    const COLUMN_NAMES = 'COLUMN_NAMES';

    const STEP_UPLOAD = 'STEP_UPLOAD';
    const STEP_MAP = 'STEP_MAP';

    function __construct(){
        if (isset($_POST[self::RESET_KEY])) {
            $this->reset();
        }

        if (isset($_FILES[self::FILE_KEY])) {
            $handle = fopen($_FILES[self::FILE_KEY]['tmp_name'], 'rb');
            $this->setColumnNames($handle);
            $this->stashFile($handle);
            fclose($handle);
        }
    }

    public function stepView() {
        if ($this->isFileStashed() && $this->getColumnNames()) { // TODO: something more than this? how to clear?
            return self::STEP_MAP;
        } else {
            return self::STEP_UPLOAD;
        }
    }

    private function reset() {
        $this->clearFile();
        $this->clearSession();
    }

    private function setColumnNames($handle) {
        rewind($handle);
        $this->writeSession(self::COLUMN_NAMES, str_getcsv(fgets($handle)));
    }

    public function getColumnNames() {
        return $this->readSession(self::COLUMN_NAMES);
    }

    private function fileSessionKey() {
        return self::FILE_KEY . ":" . session_id();
    }

    private function isFileStashed() {
        return redis()->exists($this->fileSessionKey());
    }

    private function stashFile($handle) {
        rewind($handle);
        redis()->setex($this->fileSessionKey(), self::FILE_EXP_SEC, null);
        while (!feof($handle)) {
            $chunk = fread($handle, self::CHUNK_SIZE);
            redis()->append($this->fileSessionKey(), $chunk);
        }
    }

    private function clearFile() {
        redis()->del($this->fileSessionKey());
    }

    private function writeSession($subkey, $value) {
        $_SESSION[self::SESSION_KEY][$subkey] = $value;
    }

    private function readSession($subkey) {
        return $_SESSION[self::SESSION_KEY][$subkey];
    }

    private function clearSession() {
        unset($_SESSION[self::SESSION_KEY]);
    }
}