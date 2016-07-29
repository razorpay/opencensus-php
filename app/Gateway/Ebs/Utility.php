<?php

namespace RZP\Gateway\Ebs;

use RZP\Gateway\Ebs\ResponseConstants as RESP;

class Utility extends \RZP\Gateway\Utility
{
    public function returnFields($response)
    {
        $arrayResponse = (array)simplexml_load_string($response);

        return $arrayResponse['@attributes'];
    }

    public function parseResponseXml($response)
    {
        $fields = $this->returnFields($response);

        if (array_key_exists(RESP::ERROR, $fields))
        {
            $err = array(
                RESP::ERRORCODE     => $fields[RESP::ERRORCODE],
                RESP::ERROR         => $fields[RESP::ERROR],
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
