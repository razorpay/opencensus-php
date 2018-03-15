<?php

namespace RZP\Constants;

class Mode
{
    const TEST = 'test';
    const LIVE = 'live';

    public static function exists(string $mode = null): bool
    {
        return defined(get_class() . '::' . strtoupper($mode));
    }
}
