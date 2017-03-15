<?php

namespace RZP\Models\FileStore\Formatter;

use Excel;

class ExcelFormatter
{
    /**
     * Creates Excel Object
     *
     * @param array  $data         Contents of Excel File
     * @param string $name         Name of Excel File
     * @param array  $columnFormat Column Format
     * @param string $sheetName    Sheet Name
     *
     * @return Excel excel object
     */
    public static function createExcelObject($data, $name, $columnFormat = [], $sheetName = 'Sheet 1')
    {
        $excel = Excel::create(
            $name,
            function ($excel) use ($data, $columnFormat, $sheetName)
            {
                $excel->sheet(
                    $sheetName,
                    function ($sheet) use ($data, $columnFormat)
                    {
                        // If a columnFormat variable is specified.
                        // Use it.
                        if (empty($columnFormat) === false)
                        {
                            $sheet->setColumnFormat($columnFormat);
                        }

                        $sheet->fromArray($data, null, 'A1', true, true);
                    }
                );
            }
        );

        $excel->getDefaultStyle()->getFont()->setName('Ubuntu Mono')->setSize(14);

        return $excel;
    }

    /**
     * Write the content to excel file
     *
     * @param array  $content      Content of file
     * @param string $name         Name of file
     * @param array  $columnFormat Column format of file
     * @param string $extension    Extension of file to be saved as
     * @param string $path         Path of file to be stored as
     *
     * @return array containg full file path of excel file stored
     */
    public static function writeToExcelFile($content, $name, $columnFormat, $extension, $path)
    {
        $excel = self::createExcelObject($content, $name, $columnFormat);

        $fileMetadata = $excel->store($extension, $path, true);

        return $fileMetadata;
    }
}
