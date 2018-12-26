<?php

namespace RZP\Models\BharatQr;

class Tags
{
    const VERSION               = '00';
    const POINT_OF_INITIATION   = '01';
    const VISA                  = '02';
    const MASTERCARD            = '04';
    const MERCHANT_ACCOUNT      = '08';
    // const AMEX                  = '11';
    const RUPAY                 = '06';
    const UPI_VPA               = '26';
    // Under 26
    const UPI_VPA_RUPAY_RID     = '00';
    // Under 26
    const UPI_VPA_MERCHANT_VPA  = '01';
    const UPI_VPA_AMOUNT        = '02';
    const UPI_VPA_REFERENCE     = '27';
    // Under 27
    const UPI_VPA_REFERENCE_TR  = '01';
    const MERCHANT_CATEGORY     = '52';
    const CURRENCY_CODE         = '53';
    const AMOUNT                = '54';
    const COUNTRY_CODE          = '58';
    const MERCHANT_NAME         = '59';
    const MERCHANT_CITY         = '60';
    const MERCHANT_PIN_CODE     = '61';
    const ADDITIONAL_DETAIL     = '62';
    // Under 62
    const ADDITIONAL_DETAIL_ID  = '05';
    const TERMINAL_ID           = '07';
    const CRC                   = '63';
}