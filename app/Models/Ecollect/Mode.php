<?php

namespace RZP\Models\Ecollect;

use RZP\Exception;

class Mode
{
    const RTGS = 'rtgs';
    const NEFT = 'neft';
    const IMPS = 'imps';
    const IFT  = 'ift';


    public static function isValid($mode)
    {
        return defined(__CLASS__ . '::' . strtoupper($mode));
    }

    public static function validateMode($mode)
    {
        if (self::isValid($mode) === false)
        {
            throw new Exception\InvalidArgumentException(
                'Not a valid Ecollect Mode: ' . $mode);
        }
    }
}
