<?php

namespace RZP\Gateway\Upi\Icici;

class Status
{
    const TXN_INITIATED = 92;

    const TXN_SUCCESS = 0;

    const INVALID_VPA   = 5007;

    const INVALID_PSP   = 5008;

    const SUCCESS    = 'SUCCESS';

    const PENDING    = 'PENDING';

    const FAILURE    = 'FAILURE';

    const REJECT     = 'REJECT';

    const NO_RECORDS = 'original record not found';

    const NO_RECORDS2 = 'merchant tranid is not available';
}
