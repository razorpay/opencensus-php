<?php

namespace RZP\Gateway\Fss;

class Utility
{
    public static function createRequestXml($array, $wrapRequest = true)
    {
        $xml = '';

        foreach ($array as $key => $value)
        {
            $xml .= "<$key>$value</$key>";
        }

        if ($wrapRequest === true)
        {
            $xml = "<request>" . $xml . "</request>";
        }

        return $xml;
    }
}