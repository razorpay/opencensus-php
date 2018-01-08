<?php

namespace RZP\Gateway\Fss;

class Constants
{
    const PURCHASE              = 'PURCHASE';

    // Actions have there own representation as per fss.
    const ACTION_PURCHASE       = '1';
    const ACTION_REFUND         = '2';
    const ACTION_INQUIRY        = '8';

    const LANGUAGE_USA          = 'USA';

    const TRACK_ID              = 'TrackID';

    const ERROR_MESSAGE_START   = 'IPAY';

    const CANCELLED             = 'CANCELLED';
}
