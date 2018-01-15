<?php

namespace RZP\Gateway\Card\Fss;

class Status
{
    const CAPTURED          = 'CAPTURED';

    const NOT_CAPTURED      = 'NOT CAPTURED';

    const SUCCESS           = 'SUCCESS';

    public static $successStates = [
        self::CAPTURED,
        self::SUCCESS
    ];
}
