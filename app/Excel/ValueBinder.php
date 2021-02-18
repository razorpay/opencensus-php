<?php

namespace RZP\Excel;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class ValueBinder extends DefaultValueBinder
{
    public static function dataTypeForValue($pValue)
    {
        /**
         * We need this to match the functionality of
         * PHPExcel 2.1
         */
        if ((is_string($pValue) === true) and
            (is_numeric($pValue) === true) and
            (is_integer($pValue) === false))
        {
            return DataType::TYPE_STRING;
        }

        return parent::dataTypeForValue($pValue);
    }
}