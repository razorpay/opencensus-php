<?php

namespace RZP\Services\UpiPayment;

class Constants
{
    // Actions
    const UnexpectedPreProcess  = 'unexpected_preprocess';

    // Payment Types
    const API                   = 'API';
    const QR                    = 'QR';
    const UNEXPECTED            = 'UNEXPECTED';
    const Invalid               = 'INVALID';
    const API_UNEXPECTED        = 'API_UNEXPECTED';

    const TYPE                  = 'type';
    const UPI                   = 'upi';
    const DATA                  = 'data';
    const RESPONSE              = 'response';
    const AUTHORIZE             = 'authorize';
    const MODEL                 = 'model';
    const COLUMN_NAME           = 'column_name';
    const VALUES                = 'values';
    const PAYMENT_ID            = 'payment_id';
    const GATEWAY               = 'gateway';
    const REQUIRED_FIELDS       = 'required_fields';
    const CUSTOMER_REFERENCE    = 'customer_reference';
    const MERCHANT_REFERENCE    = 'merchant_reference';
    const AMOUNT                = 'amount';

    //Actions
    const MULTIPLE_ENTITY_FETCH = 'multiple_entity_fetch';
}
