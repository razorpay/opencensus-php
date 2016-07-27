<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Ebs;

class Utility extends \RZP\Gateway\Utility
{
    public static function returnFields($response)
    {
        $parser = xml_parser_create();
        xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
        xml_parse_into_struct($parser, $response, $vals, $index);
        return $vals[0]['attributes'];
    }

    public static function parseResponseXml($response)
    {
        $fields = self::returnFields($response);

        if (array_key_exists('error', $fields))
        {
            $err = array(
                'errorCode' => $fields['errorCode'],
                'error' => $fields['error'],
            );

            return $err;
        }
        else
        {
            $fields['error'] = false;
            $fields['errorCode'] = 0;

            return $fields;
        }
    }
}
