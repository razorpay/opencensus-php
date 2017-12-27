<?php

namespace RZP\Gateway;

use Requests_Exception;

class Utility
{
    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param Requests_Exception $e The caught requests exception
     *
     * @return boolean              true/false
     */
    public static function checkTimeout(\Requests_Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occurred
        //
        if ((strpos($msg, 'operation timed out') !== false) or
            (strpos($msg, 'network is unreachable') !== false) or
            (strpos($msg, 'name or service not known') !== false) or
            (strpos($msg, 'failed to connect') !== false) or
            (strpos($msg, 'could not resolve host') !== false) or
            (strpos($msg, 'resolving timed out') !== false) or
            (strpos($msg, 'name lookup timed out') !== false) or
            (strpos($msg, 'connection timed out') !== false) or
            (strpos($msg, 'aborted due to timeout') !== false))
        {
            return true;
        }

        return false;
    }

    public static function checkActualTimeout(\Requests_Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occurred
        //
        if (strpos($msg, 'operation timed out') !== false)
        {
            return true;
        }

        return false;
    }

    /**
     * Checks whether the SoapFault exception is a timeout exception
     * If so, this should be treated as a gateway failure
     *
     * @param \SoapFault $sf
     *
     * @return bool
     */
    public static function checkSoapTimeout(\SoapFault $sf)
    {
        $msg = strtolower($sf->getMessage());

        if ((strpos($msg, 'could not connect to host') !== false) or
            (strpos($msg, 'connection timed out') !== false) or
            (strpos($msg, 'error fetching http headers') !== false)
        )
        {
            return true;
        }

        return false;
    }

    public static function isXml($xml)
    {
        $xml = trim($xml);

        return (mb_substr($xml, 0, 5) === '<?xml');
    }

    public static function stripEmailSpecialChars(string $email)
    {
        return preg_replace("/[^a-zA-Z0-9]+/", "", $email);
    }
}
