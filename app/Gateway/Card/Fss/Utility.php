<?php

namespace RZP\Gateway\Card\Fss;

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

    /**
     * By default decrypted comes with only fields instead of nested, to let simple xml understand the data.
     * We wrap around response.
     * Example: response with out a parent node = <result>SUCCESS</result><auth>0123</auth><payid>12314241</payid>
     * Respoonse should be with a parent node for xml to array conversion
     * <response><result>SUCCESS</result><auth>0123</auth><payid>12314241</payid></response>
     *
     * @param string $string
     *
     * @return array
     */
    public static function createResponseArray($string)
    {
        $string = "<response>" . $string . "</response>";

        $result = (array) simplexml_load_string($string);

        return $result;
    }
}
