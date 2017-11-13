<?php

namespace RZP\Gateway\Fss;

class Utility
{
    public static function createRequestXml($array)
    {
        $xml = "<request>";

        foreach ($array as $key => $value)
        {
            $xml .= "<$key>$value</$key>";
        }

        return $xml . "</request>";
    }
}