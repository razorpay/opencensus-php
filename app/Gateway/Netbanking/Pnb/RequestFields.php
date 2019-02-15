<?php

namespace RZP\Gateway\Netbanking\Pnb;

class RequestFields
{
    const API_KEY        = 'api_key';
    const ENCRYPTED_DATA = 'encrypted_data';

    // fields to be encrypted
    const PAYMENT_ID     = 'order_id';
    const MODE           = 'mode';
    const AMOUNT         = 'amount';
    const CURRENCY       = 'currency';
    const DESCRIPTION    = 'description';
    const NAME           = 'name';
    const EMAIL          = 'email';
    const PHONE          = 'phone';
    const ADDRESS_LINE_1 = 'address_line_1';
    const ADDRESS_LINE_2 = 'address_line_2';
    const CITY           = 'city';
    const STATE          = 'state';
    const COUNTRY        = 'country';
    const ZIP_CODE       = 'zip_code';
    const BANK_CODE      = 'bank_code';
    const RETURN_URL     = 'return_url';
    const CHECKSUM       = 'hash';

    // for verify
    const BANK_PAYMENT_ID = 'transaction_id';
    const RESPONSE_CODE   = 'response_code';

    //for refund
    const MERCHANT_REFUND_ID  = 'merchant_refund_id';

    //for verify refund
    const MERCHANT_ORDER_ID = 'merchant_order_id';
}
