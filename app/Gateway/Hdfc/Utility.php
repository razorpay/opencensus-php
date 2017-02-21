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

    /**
     * First checks for error code field.
     * If it's set then gets error related fields otherwise
     * gets other normal field values.
     *
     * @param array $response
     */
    public static function parseResponseXml(array & $response)
    {
        $xml = $response['xml'];

        $errorCode = self::getFieldFromXML($xml, 'error_code_tag');

        if ($errorCode !== null)
        {
            // There is an error, only set error fields.
            $response['error'] = [
                'code' => $errorCode,
                'text' => self::getFieldFromXML($xml, 'error_text'),
                'result' => self::getFieldFromXML($xml, 'result'),
            ];
        }
        else
        {
            $response['data'] = self::getFieldsFromXML($xml, $response['fields']);
        }
    }

    public static function getFieldsFromXML($xml, $fields)
    {
        $data = [];

        foreach ($fields as $field)
        {
            $data[$field] = getTextBetweenStrings($xml, "<$field>", "</$field>");
        }

        return $data;
    }

    public static function getFieldFromXML($xml, $field)
    {
        return getTextBetweenStrings($xml, "<$field>", "</$field>");
    }

    /**
     * Unsets specified fields
     *
     * @param $data
     * @param $fields
     *
     * @return null
     */
    public static function unsetFields($data, $fields)
    {
        if ($data === null)
        {
            return null;
        }

        $data = array_diff_key($data, array_flip($fields));

        return $data;
    }
}
