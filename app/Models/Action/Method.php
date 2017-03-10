<?php

namespace RZP\Models\Action;

class Method
{
    const POST  = 'POST';
    const PUT   = 'PUT';
    const PATCH = 'PATCH';

    public static function exists($method)
    {
        return defined(get_class() . '::' . strtoupper($method));
    }
}
