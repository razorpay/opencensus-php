<?php

namespace RZP\Gateway\Netbanking\Pnb;

class RequestFields
{
    /**
     * According to the docs, the request and response fields
     * of the payment & verify requests are the same.
     * Also the encryption logic is same.
     *
     * In order to make verify request,
     * we will send the required fields mentioned below.
     */

    // Optional
    const USER_NAME       = 'name';
    const ADDRESS         = 'address';
    const EMAIL           = 'email';
    const PHONE_NUMBER    = 'phone';
    const REMARK          = 'remark';

    // Required
    //
    // CHALLAN_NUMBER corresponds to payment_id
    const CHALLAN_NUMBER  = 'cin';
    const MERCHANT_DATE   = 'MerchantDate';
    const MERCHANT_AMOUNT = 'MerchantAmt';
    const RETURN_URL      = 'RU';
    const ITEM_CODE       = 'ITC';

    // Encryption
    const CHECKSUM        = 'checksum';
    const ENCDATA         = 'encdata';
}
