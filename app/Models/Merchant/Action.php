<?php

namespace RZP\Models\Merchant;

class Action
{
    const ARCHIVE   = 'archive';
    const UNARCHIVE = 'unarchive';
    const SUSPEND   = 'suspend';
    const UNSUSPEND = 'unsuspend';
    const CREATED   = 'created';
    const ACTIVATED = 'activated';

    public static function exists($action)
    {
        return defined(get_class() . '::' . strtoupper($action));
    }
}
