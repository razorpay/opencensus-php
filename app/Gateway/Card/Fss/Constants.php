<?php

namespace RZP\Gateway\Card\Fss;

class Constants
{
    const PURCHASE              = 'PURCHASE';

    const LANGUAGE_USA          = 'USA';

    const TRACK_ID              = 'TrackID';

    const CANCELLED             = 'CANCELLED';

    const UDF1                  = 'UDF1';

    const UDF2                  = 'UDF2';

    const UDF4                  = 'UDF4';

    // ErrorCodes start with the below text.
    public static $errorMessageStart = [
        'IPAY',
        'GW',
        'GV',
        'PY',
    ];
}
