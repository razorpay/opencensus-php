<?php

namespace RZP\Models\User;

class Action
{
    const ATTACH = 'attach';
    const DETACH = 'detach';
    const UPDATE = 'update';

    public static function exists($action)
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
