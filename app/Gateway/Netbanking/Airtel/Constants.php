<?php

namespace RZP\Gateway\Netbanking\Airtel;

class Constants
{
    const REVERSAL              = 'ECOMM_REVERSAL';

    const INQUIRY               = 'ECOMM_INQ';

    const TIME_FORMAT           = 'dmYhis';

    const ACTION_ERROR          = 'Action not set correctly';

    const MERCHANT_NAME         = 'Razorpay';

    // undefined as checksum in response (in case of cancelled by user)
    const UNDEFINED             = 'undefined';
}
