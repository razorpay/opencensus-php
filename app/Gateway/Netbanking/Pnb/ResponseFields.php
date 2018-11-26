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
    const DESCRPTION      = 'description';
    const AMOUNT          = 'amount';
    const CHECKSUM        = 'hash';

    // Verify
    const BANK_CODE = 'bank_code';

    // Refund
    const REFUND_REFERENCE_NO = 'refund_reference_no';
    const ERROR_MESSAGE       = 'message';
}
