<?php

namespace RZP\Models\Card\IIN\Import;

use Excel;

use RZP\Base\RuntimeManager;
use RZP\Exception;
use RZP\Excel\Import as ExcelImport;

/**
 * This class extracts the data from the file and return the column names
 * and the rows.
 */
class XLSFileHandler
{

    /**
     * This function is called by the Importer class. It return the column names
     * and rows.
     */
    public function getData($input)
    {
        $file = $this->getFile($input);

        $filePath = $this->moveFile($file);

        $data = $this->parse($filePath);

        $this->removeFile($filePath);

        return $data;
    }

    public function getCsvData($file)
    {
        RuntimeManager::setMemoryLimit('2048M');
        RuntimeManager::setTimeLimit(3000);
        RuntimeManager::setMaxExecTime(6000);

        $fileHandler = fopen($file,"r");
        $ret = array();
        while(! feof($fileHandler))
        {
            $line = fgetcsv($fileHandler);
            if ($line){
                array_push($ret, $line);
            }
        }
/*
        $excelReader = Excel::load($file)->excel;
        $sheet = $excelReader->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestDataColumn();
        $columnNames = array();

        $data = $sheet->rangeToArray(
            'A1' . ':' . $highestColumn . $highestRow,
            null,
            true,
            false
        );
 */
        return ['data' => $ret];

    }
    /**
     * return the file from input array
     *
     * @param array $input the input array
     *
     * @return SplFileInfo      the file info object from the input array
     */
    protected function getFile($input)
    {
        if (isset($input['file']))
        {
            return $input['file'];
        }

        throw new Exception\BadRequestException('Input file not set');
    }

    /**
     * renames the file to its correct name. The excel parser has problem
     * processing the file without proper extension.
     *
     * @param SplFileInfo $file   the file object from input
     *
     * @return string  path to the new file.
     */
    protected function moveFile($file)
    {
        $originalName = $file->getClientOriginalName();
        $dir = "/tmp";
        $newFilePath = $dir . "/" . $originalName;

        $res = rename($file->getRealPath(), $newFilePath);

        if ($res === false)
        {
            throw new Exception\RuntimeException("Failed to rename the file");
        }

        return $newFilePath;
    }

    /**
     * Deletes the file after processing.
     *
     * @param string $file  the path of the file to delete.
     *
     * @return bool  Returns true on success or false on failure
     */
    protected function removeFile($file)
    {
        return unlink($file); // nosemgrep : php.lang.security.unlink-use.unlink-use
    }

    /**
     * This function extracts the columns and rows from the file.
     *
     * The return array contains keys <code>columns</code> and
     * <code>data</code>. <code>columns</code> contains the column names.
     * <code>data</code> contains the rows.
     *
     *
     * @param string $filePath    the path of the file to process
     *
     * @return array    as described above
     */
    protected function parse($filePath)
    {
        RuntimeManager::setMemoryLimit('1024M');
        RuntimeManager::setTimeLimit(300);
        RuntimeManager::setMaxExecTime(600);

        // The Laravel Excel Reader crashed due to some unknown reason
        // So, using the internal PHPExecl object
        $excelReader = (new ExcelImport(1))->toArray($filePath);

        $sheet = $excelReader[0];
        $highestRow = count($sheet);
        $highestColumn = count($sheet[0]);

        $columnIndex = $this->getColumnHeaderIndex($sheet, $highestRow);
        $sheet = $this->getParsedSheet($sheet, $columnIndex);
        $columnNames = $sheet[$columnIndex];

        $rowIndex = $this->skipBlankColumns($sheet, $columnIndex + 1, $highestRow);

        $rows = array_slice($sheet, $rowIndex);

        return ['columns' => $columnNames, 'data' => $rows];
    }

    protected function getParsedSheet($sheet, $columnIndex)
    {
        $parsedSheet = [];
        foreach ($sheet as $row => $rowValues) {
            $columns = [];
            foreach ($rowValues as $key => $value) {
                $columns[] = $value;
            }
            $parsedSheet[] = $columns;

        }
        return $parsedSheet;
    }

    protected function getColumnHeaderIndex($rows, $highestRow)
    {
        $len = count($rows[0]);

        // Skipping the the rows that contain atleast one null column
        // They are mostly page/file title
        for ($row = 0; $row < $highestRow; $row++)
        {
            foreach ($rows[$row] as $key => $value)
            {
                if ($value === null )
                {
                    continue 2;
                }
            }
            return $row;
        }

    }

    protected function skipBlankColumns($rows, $startIndex, $highestRow)
    {
        $len = count($rows[0]);

        // Skipping if the following row contains all cells null
        for ($row = $startIndex; $row < $highestRow; $row++)
        {
            foreach ($rows[$row] as $key => $value)
            {
                if ($value != null )
                    return $row;
            }
        }
    }

}
