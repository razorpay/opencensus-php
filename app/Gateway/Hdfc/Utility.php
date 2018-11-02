<?php

namespace RZP\Gateway\Hdfc;

use RZP\Gateway\Hdfc;

class Utility extends \RZP\Gateway\Utility
{
    public static function createXml($array)
    {
        $xml = "";

        foreach ($array as $key => $value)
        {
            $xml .= "<$key>$value</$key>\n";
        }

        return $xml;
    }

    public static function getAndParseError(array &$response)
    {
        $error = self::getFieldFromXML($response['xml'], 'error_code_tag');

        if ($error === null)
        {
            return false;
        }

        $response['error']['code'] = $error;
        $response['error']['text'] = self::getFieldFromXML($response['xml'], 'error_text');
        $response['error']['result'] = self::getFieldFromXML($response['xml'], 'result');

        return true;
    }

    public static function parseResponseXml(array &$response)
    {
        if (self::getAndParseError($response))
        {
            return;
        }

        self::getFieldsFromXML(
            $response['xml'],
            $response['fields'],
            $response['data']);
    }

    public static function getFieldsFromXML($xml, $fields, &$array)
    {
        foreach ($fields as $field)
        {
            $array[$field] = getTextBetweenStrings($xml, "<$field>", "</$field>");
        }
    }

    public static function getFieldFromXML($xml, $field)
    {
        return getTextBetweenStrings($xml, "<$field>", "</$field>");
    }

    /**
     * Unsets specified fields
     */
    public static function unsetFields($data, $fields)
    {
        if ($data === null)
            return null;

        $data = array_diff_key($data, array_flip($fields));

        return $data;
    }
}
