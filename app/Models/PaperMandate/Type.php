<?php

namespace RZP\Models\PaperMandate;

class Type
{
    const CREATE = 'create';
    const MODIFY = 'modify';
    const CANCEL = 'cancel';

    public static function isValidType($type)
    {
        return (defined(__CLASS__.'::'.strtoupper($type)));
    }
}
