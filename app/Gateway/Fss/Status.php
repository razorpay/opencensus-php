<?php

namespace RZP\Gateway\Fss;

class Status
{
    const CAPTURED          = 'CAPTURED';

    const NOT_CAPTURED      = 'NOT CAPTURED';

    const SUCCESS           = 'SUCCESS';

    protected static $successStates = [
        self::CAPTURED,
        self::SUCCESS
    ];


}
