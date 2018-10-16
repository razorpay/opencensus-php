<?php

namespace RZP\Gateway\Upi\Hulk;

class Fields
{
    const ID                    = 'id';
    const STATUS                = 'status';
    const RRN                   = 'rrn';
    const TXN_ID                = 'txn_id';
    const REF_ID                = 'ref_id';
    const SENDER_ID             = 'sender_id';
    const SENDER_TYPE           = 'sender_type';
    const RECEIVER_ID           = 'receiver_id';
    const RECEIVER_TYPE         = 'receiver_type';
    const TRANSACTION_TYPE      = 'transaction_type';
    const AMOUNT                = 'amount';
    const EXPIRE_AT             = 'expire_at';
    const NOTES                 = 'notes';
    const SENDER                = 'sender';
    const ADDRESS               = 'address';
    const DESCRIPTION           = 'description';
    const TYPE                  = 'type';
    const CURRENCY              = 'currency';
    const RECEIVER              = 'receiver';
    const CALLER_ACCOUNT_NUMBER = 'caller_account_number';
    const CALLER_IFSC_CODE      = 'caller_ifsc_code';
    const MERCHANT_REFERENCE_ID = 'merchant_reference_id';
    const CATEGORY_CODE         = 'category_code';

    // Used callback
    const DATA                  = 'data';
    const SIGNATURE             = 'signature';
    const RAW                   = 'raw';
    const CALLBACK_DATA         = 'callback_data';
    const QR_DATA               = 'qr_data';
    const CONTENT               = 'content';

    // Error Fields
    const ERROR_CODE            = 'error_code';
    const ERROR_DESCRIPTION     = 'error_description';
    const INTERNAL_ERROR_CODE   = 'internal_error_code';

    const RESPONSE_CODE         = 'responseCode';
    const BANK_RRN              = 'bank_rrn';
    const TRANSACTION_STATUS    = 'transaction_status';
}
