<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Ebs;
use RZP\Gateway\Ebs\ResponseConstants as RESP;

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

        if (array_key_exists(RESP::ERROR, $fields))
        {
            $err = array(
                RESP::ERRORCODE => $fields[RESP::ERRORCODE],
                RESP::ERROR => $fields[RESP::ERROR],
            );

            return $err;
        }
        else
        {
            $fields[RESP::ERROR] = false;
            $fields[RESP::ERRORCODE] = 0;

            return $fields;
        }
    }
}
