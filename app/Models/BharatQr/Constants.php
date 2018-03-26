<?php

namespace RZP\Models\BharatQr;

class Constants
{
    const VERSION               = '01';
    // TODO: Accept this from input
    // issue: https://github.com/razorpay/api/issues/7054
    const STATIC_POI            = '11';
    const DYNAMIC_POI           = '12';
    const MERCHANT_CATEGORY     = '5399';
    const CURRENCY_CODE         = '356';
    const COUNTRY_CODE          = 'IN';
    // TODO: All of these need to be dynamically set
    // based on which merchant is making the request
    // issue: https://github.com/razorpay/api/issues/7237
    const MERCHANT_NAME         = 'PAYMENTS';
    const MERCHANT_CITY         = 'BANGALORE';
    const MERCHANT_PINCODE      = '560030';
    const RUPAY_RID             = 'A000000524';
    const MERCHANT_VPA          = 'razorpaybqr@icici';
    const MUTEX_TIMEOUT         = 60;
    const CARD_CVV              = '123';
    const CARD_NAME             = 'Random';
    const CARD_EXPIRY_MONTH     = '11';
    const CARD_EXPIRY_YEAR      = '2037';
    const UPI_PREFIX            = 'PIL';
}
