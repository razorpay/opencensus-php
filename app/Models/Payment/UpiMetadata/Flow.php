<?php

namespace RZP\Models\Payment\UpiMetadata;

class Flow
{
    const COLLECT     = 'collect';

    const INTENT      = 'intent';

    const OMNICHANNEL = 'omnichannel';

    public static function isFlowCollect(string $flow)
    {
        return ($flow === self::COLLECT);
    }

    public static function isFlowIntent(string $flow)
    {
        return ($flow === self::INTENT);
    }
}
