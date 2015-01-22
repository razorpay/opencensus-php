<?php

namespace Models\Settlement\Kotak;

use Carbon\Carbon;

trait FileHandlerTrait
{
    public function writeToTextFile($txt)
    {
        $name = $this->getFileName();

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

    protected function getFileName()
    {
        $time = Carbon::now('Asia/Kolkata')->format('d-m-Y_H:i:s');

        return static::$filename . '_'.$time.'.txt';
    }

    protected function parseTextFile($file)
    {
        $filePath = $file->getRealPath();

        $file = fopen($filePath, 'r');

        $txt = fread($file, filesize($filePath));

        $rows = explode('\n', $txt);

        $data = array();

        $headings = $this->getHeadings();

        foreach ($rows as $row)
        {
            if ($row === '')
                continue;

            $values = explode('~', $row);

            $values = array_combine($headings, $values);

            $data[] = $values;
        }

        return $data;
    }

    public static function getHeadings()
    {
        return static::$headings;
    }
}