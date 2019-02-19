<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ResponseFields
{
    const API_KEY        = 'api_key';
    const ENCRYPTED_DATA = 'encrypted_data';

    // Fields after decrypting
    const PAYMENT_ID      = 'order_id';
    const RESPONSE_CODE   = 'response_code';
    const BANK_PAYMENT_ID = 'transaction_id';
    const RESPONSE_DESC   = 'response_message';
    const ERROR_DESC      = 'error_desc';
    const DESCRIPTION     = 'description';
    const AMOUNT          = 'amount';
    const CHECKSUM        = 'hash';

    // Verify
    const BANK_CODE = 'bank_code';

    // Refund
    const REFUND_ID           = 'refund_id';
    const MERCHANT_ORDER_ID   = 'merchant_order_id';
    const MERCHANT_REFUND_ID  = 'merchant_refund_id';
    const REFUND_REFERENCE_NO = 'refund_reference_no';
    const ERROR_MESSAGE       = 'message';

    //Verify Refund
    const REFUND_AMOUNT      = 'refund_amount';
    const REFUND_DETAILS     = 'refund_details';
    const REFUND_STATUS      = 'refund_status';
    const TRANSACTION_AMOUNT = 'transaction_amount';

    //error
    const CODE = 'code';
}
