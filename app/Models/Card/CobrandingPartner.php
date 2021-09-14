<?php

namespace RZP\Models\Card;

class CobrandingPartner
{
    const ONECARD = 'onecard';

    public static function isValid($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
