<?php

namespace RZP\Models\Action;

class Type
{
    const MAKER   = 'maker';
    const CHECKER = 'checker';

    public static function exists($type)
    {
        return defined(get_class() . '::' . strtoupper($type));
    }
}
