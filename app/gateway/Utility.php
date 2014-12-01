<?php

namespace Gateway;

class Utility
{
    /**
     * Checks whether the requests exception that we caught
     * is actually because of timeout in the network call.
     *
     * @param  Requests_Exception $e The caught requests exception
     *
     * @return boolean               true/false
     */
    public static function checkTimeout(\Requests_Exception $e)
    {
        $msg = $e->getMessage();
        $msg = strtolower($msg);

        //
        // check if timeout has occured
        //
        if ((strpos($msg, 'operation timed out')  !== false) or
            (strpos($msg, 'network is unreachable') !==false) or
            (strpos($msg, 'name or service not known') !== false) or
            (strpos($msg, 'failed to connect') !== false) or
            (strpos($msg, 'could not resolve host') !== false) or
            (strpos($msg, 'resolving timed out') !== false) or
            (strpos($msg, 'name lookup timed out' !== false)))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}