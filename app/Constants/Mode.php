<?php

namespace RZP\Constants;

class Mode
{
    const TEST = 'test';
    const LIVE = 'live';

    public static function getAlternateMode(string $mode): string
    {
        return ($mode === self::LIVE) ? self::TEST : self::LIVE;
    }
}
