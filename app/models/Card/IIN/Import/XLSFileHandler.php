<?php

namespace Models\Card\IIN\Import;

use Excel;

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

        throw new Exception('Input file not set');
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

    /**
     * Deletes the file after processing.
     *
     * @param string $file  the path of the file to delete.
     *
     * @return bool  Returns true on success or false on failure
     */
    protected function removeFile($file)
    {
        return unlink($file);
    }

    protected function getStorageDir()
    {
       return storage_path('iins');
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
