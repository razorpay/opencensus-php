<?php

namespace Models\Card\IIN;

use Excel;

class XLSFileHandler
{

    public function getData($input)
    {
        $file = $this->getFile($input);
        $filePath = $this->moveFile($file);

        $data = $this->parse($filePath);

        $this->removeFile($filePath);

        return $data;
    }

    protected function getFile($input)
    {
        if (isset($input['file']))
        {
            return $input['file'];
        }

        throw new Exception('Input file not set');
    }

    protected function moveFile($file)
    {

        $originalName = $file->getClientOriginalName();
        $dir = $this->getStorageDir();
        $newFilePath = $dir . DIRECTORY_SEPARATOR . $originalName;

        if (file_exists($dir) === false)
        {
            mkdir($dir, 0777);
        }

        $res = rename($file->getRealPath(), $newFilePath);

        if ($res === false)
        {
            throw new Exception("Failed to rename the file");
        }

        return $newFilePath;
    }

    protected function removeFile($file)
    {
        return unlink($file);
    }

    protected function getStorageDir()
    {
       return storage_path('iins');
    }

    protected function parse($filePath)
    {
        ini_set("memory_limit","2G");

        // The Laravel Excel Reader crashed due to some unknown reason
        // So, using the internal PHPExecl object
        $objPHPExcel = Excel::load($filePath)->excel;

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $columnNames = array();

        // Skipping the the rows that contain atleast one null column
        // They are mostly page/file title
        for ($row = 1; $row <= $highestRow; $row++)
        {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

            for($i = 0; $i < count($rowData); $i++)
            {
                if($rowData[$i] == NULL )
                {
                    continue 2;
                }
            }
            break;
        }

        $columnNames = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

        // Skipping if the following row contains all colums null
        for ($row++; $row <= $highestRow; $row++)
        {
            $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE)[0];

            for($i = 1; $i < count($rowData); $i++)
            {
                if($rowData[$i] != NULL )
                    break 2;
            }
        }

        $data = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $highestRow, NULL, TRUE, FALSE);

        return ['columns' => $columnNames, 'data' => $data];
    }

}
