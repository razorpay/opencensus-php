<?php

namespace RZP\Models\PaperMandate;

class Status
{
    const CREATED       = 'created';
    const AUTHENTICATED = 'authenticated';

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
