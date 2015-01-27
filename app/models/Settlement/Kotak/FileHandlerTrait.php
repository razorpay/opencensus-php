<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;
use EE\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

trait FileHandlerTrait
{
    public function writeToTextFile($txt)
    {
        $name = $this->getFileToWriteName();

        $path = storage_path() . '/files/settlement/';

        $fullpath = $path . $name;

        $file = fopen($fullpath, 'w');
        fwrite($file, $txt);
        fclose($file);

        return $fullpath;
    }

    protected function generateText($data)
    {
        $txt = '';

        foreach ($data as $row)
        {
            $txt .= implode('~', array_values($row)) . '\n';
        }

        return $txt;
    }

    protected function getFileIfExists()
    {
        $name = $this->getFileToReadName();

        $path = storage_path('files/settlement');

        $fullpath = $path . '/' . $name;

        if (file_exists($fullpath) === false)
        {
            // @todo: trace here
            return null;
        }

        return $fullpath;
    }

    public function deleteFileIfExists()
    {
        $fullPath = $this->getFileIfExists();

        if ($fullPath !== null)
        {
            $success = unlink($fullPath);

            if ($success === false)
            {
                throw new Exception\RuntimeErrorException(
                    'Failed to delete file: ' . $fullPath);
            }
        }
    }

    protected function getFileToReadName()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        $name = static::$fileToReadName.'_'.$mode.'_'.$time.'.txt';

        return $name;
    }

    protected function getFileToWriteName()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y');

        $mode = $this->getMode();

        return static::$fileToWriteName.'_'.$mode.'_'.$time.'.txt';
    }

    protected function parseTextFile($file)
    {
        $rows = $this->getFileLines($file);

        $data = array();
        $headings = $this->getHeadings();

        foreach ($rows as $row)
        {
            // Ending row may be just empty.
            if ($row === '')
                continue;

            $values = explode('~', $row);
            $values = array_combine($headings, $values);
            $data[] = $values;
        }

        return $data;
    }

    protected function getFileLines($file)
    {
        $filePath = $file;

        if ($file instanceof UploadedFile)
        {
            $filePath = $file->getRealPath();
        }

        $file = fopen($filePath, 'r');
        $txt = fread($file, filesize($filePath));
        $lines = explode('\n', $txt);

        return $lines;
    }

    public static function getHeadings()
    {
        return static::$headings;
    }

    public function moveFile($file)
    {
        $filename = basename($file, '.txt');

        $dir = storage_path('files/settlement/reconciled');

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $time = Carbon::now('Asia/Kolkata')->format('H:i:s');

        $mode = $this->getMode();

        $newName = $dir . '/' . $filename . '_' . $mode.'_'.$time . '.txt';

        rename($file, $newName);
    }

    protected function getMode()
    {
        $mode = \BasicAuth::getMode();

        return $mode;
    }
}