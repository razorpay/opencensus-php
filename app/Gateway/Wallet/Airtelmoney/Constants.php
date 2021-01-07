<?php

namespace RZP\Gateway\Wallet\Airtelmoney;

class  Constants
{
    const TIME_FORMAT = 'dmYhis';

    const REVERSAL = 'ECOMM_REVERSAL';

    const INQUIRY = 'ECOMM_INQ';

    const ACTION_ERROR = 'Action not set correctly';

    const SERVICE_TYPE = 'WT';

    // undefined as checksum in response (in case of cancelled by user)
    const UNDEFINED             = 'undefined';
}
