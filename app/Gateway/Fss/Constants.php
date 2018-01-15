<?php

namespace RZP\Gateway\Fss;

class Constants
{
    const PURCHASE              = 'PURCHASE';

    const LANGUAGE_USA          = 'USA';

    const TRACK_ID              = 'TrackID';

    const CANCELLED             = 'CANCELLED';

    // ErrorCodes start with the below text.
    public static $errorMessageStart = [
        'IPAY',
        'GW',
        'GV',
        'PY',
    ];
}
