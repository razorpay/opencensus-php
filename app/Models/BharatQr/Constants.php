<?php

namespace RZP\Models\BharatQr;

class Constants
{
    const VERSION           = '01';
    const STATIC_POI        = '11';
    const DYNAMIC_POI       = '12';
    const MERCHANT_CATEGORY = '5399';
    const CURRENCY_CODE     = '356';
    const COUNTRY_CODE      = 'IN';
    // TODO: All of these need to be dynamically set
    // based on which merchant is making the request
    // issue: https://github.com/razorpay/api/issues/7237
    const MERCHANT_NAME          = 'RAZORPAY SOFTWARE PVT LTD';
    const MERCHANT_CITY          = 'BANGALORE';
    const MERCHANT_PINCODE       = '560030';
    const RUPAY_RID              = 'A000000524';
    const MERCHANT_VPA           = 'razorpaybqr@icici';
    const UPI_PREFIX             = 'RZP';
    const ACCOUNT_NUMBER         = '2223330048827001';
    const IFSC_CODE              = 'YESB0CMSNOC';
}
