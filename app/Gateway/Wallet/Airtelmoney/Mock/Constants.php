<?php

namespace RZP\Gateway\Wallet\Airtelmoney\Mock;

class Constants
{
    const DUMMY_MSG             = 'SUCCESS';

    const TIME_FORMAT           = 'dmYhis';

    const INVALID_MERCHANT_ID   = '902';

    const MERCHANT_ID_NOT_FOUND = '920';

    const SUCCESS               = '000';

    // undefined as checksum in response (in case of cancelled by user)
    const UNDEFINED             = 'undefined';
}
