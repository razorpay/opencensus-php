<?php

namespace RZP\Models\FileStore\Formatter;

use Excel;

class ExcelFormatter
{
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

    public static function writeToExcelFile($content, $name, $columnFormat, $extension, $path)
    {
        $excel = self::createExcelObject($content, $name, $columnFormat);

        $fileMetadata = $excel->store($extension, storage_path($path), true);

        return $fileMetadata;
    }
}
