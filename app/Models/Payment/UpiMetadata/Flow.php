<?php

namespace RZP\Models\Payment\UpiMetadata;

class Flow
{
    const COLLECT     = 'collect';

    const INTENT      = 'intent';

    const OMNICHANNEL = 'omnichannel';

    public static function isCollect(string $flow)
    {
        return ($flow === self::COLLECT);
    }

    public static function isIntent(string $flow)
    {
        return ($flow === self::INTENT);
    }
}
