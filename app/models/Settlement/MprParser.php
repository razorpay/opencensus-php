<?php

namespace Models\Settlement;

use Excel;

class MprParser
{
    public static function parseMprFile($mprFile)
    {
        $data = self::getDataFromMprFile($mprFile);

        return self::parseMprFileDataIntoAssocArray($data);
    }

    protected static function getDataFromMprFile($mprFile)
    {
        $filePath = $mprFile->getRealPath();

        $data = Excel::load($filePath)
                      ->noHeading()
                      ->ignoreEmpty()
                      ->formatDates(false)
                      ->toArray();

        if ((count($data) === 3) and
            (count($data[1]) === 0))
        {
            //
            // For excel files, with 3 sheets, we get the
            // data for first sheet only, discarding other sheets.
            // The simple check to determine sheets is that they will 3
            // in number and data in second sheet should be empty.
            //
            $data = $data[0];
        }

        return $data;
    }

    protected static function parseMprFileDataIntoAssocArray($data)
    {
        $assocArray = array();

        $headings = array_shift($data);

        // Change headings to camelcase values
        foreach($headings as &$attr)
        {
            $attr = strtolower($attr);
            $attr = str_replace(' ', '_', $attr);

            if (in_array($this->headings, $attr) === false)
            {
                throw new Exception\LogicException(
                    'Hdfc mpr: heading mis-match. Value: ' . $attr);
            }
        }

        $headingCount = count($headings);

        $lgrs = array();

        $r = range(1, $headingCount);

        foreach ($data as $row)
        {
            foreach($r as $i)
            {
                // Some keys may have corresponding blank columns
                // In such cases, excel does not provide a value for it.
                // So, we manually set those keys to 'null'
                if (isset($row[$i]) === false)
                {
                    $row = array_slice($row, 0, $i - 1, true) +
                           array($i => null) +
                           array_slice($row, $i - 1, null, true);
                }
            }


            array_push($assocArray, array_combine($headings, $row));
        }

        return $assocArray;
    }
}
