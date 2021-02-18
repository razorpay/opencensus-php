<?php

namespace RZP\Models\FileStore\Formatter;

use Excel;
use RZP\Excel\Export as ExcelExport;
use RZP\Excel\ExportSheet as ExcelSheetExport;

class ExcelFormatter
{
    /**
     * Creates Excel Object
     *
     * @param array  $data         Contents of Excel File
     * @param string $name         Name of Excel File
     * @param array  $columnFormat Column Format
     * @param bool   $headers      Whether excel should contain headers
     * @param string $sheetName    Sheet Name
     *
     * @return Excel excel object
     */
    public static function createExcelObject(
        $data,
        $dir,
        $filename,
        $extension,
        $columnFormat = [],
        $headers = true,
        $sheetName = 'Sheet 1')
    {
        $path = $dir . DIRECTORY_SEPARATOR . $filename . '.' . $extension;

        (new ExcelExport)->setSheets(function() use ($data, $sheetName, $columnFormat, $headers) {
                $sheetsInfo = [];
                $sheetsInfo[$sheetName] = (new ExcelSheetExport($data))
                                                ->setTitle($sheetName)
                                                ->setColumnFormat($columnFormat)
                                                ->generateAutoHeading($headers)
                                                ->setStyle(function($sheet) {
                                                    $sheet->getParent()
                                                         ->getDefaultStyle()
                                                         ->getFont()
                                                         ->setName('Ubuntu Mono')
                                                         ->setSize(14);
                                                });

                return $sheetsInfo;
            })->store($path, 'local_storage');

        return [
            'full'  => $path,
            'path'  => $dir,
            'file'  => $filename . '.' . $extension,
            'title' => $filename,
            'ext'   => $extension
        ];
    }

    /**
     * Write the content to excel file
     *
     * @param array  $content      Content of file
     * @param string $name         Name of file
     * @param array  $columnFormat Column format of file
     * @param string $extension    Extension of file to be saved as
     * @param string $path         Path of file to be stored as
     * @param string $sheetName    Name of the sheet in the excel file
     *
     * @return array containg full file path of excel file stored
     */
    public static function writeToExcelFile($content, $name, $columnFormat, $headers, $extension, $dir, $sheetName)
    {
        $fileMetadata = self::createExcelObject($content, $dir, $name, $extension, $columnFormat, $headers, $sheetName);

        // $fileMetadata = $excel->store($extension, $path, true);

        return $fileMetadata;
    }
}
