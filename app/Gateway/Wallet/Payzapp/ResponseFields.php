<?php

namespace RZP\Gateway\Wallet\Payzapp;

class ResponseFields
{
    const RRN             = 'rrn';
    const STATUS          = 'status';
    const ERROR_CODE      = 'pg_error_code';
    const ERROR_DETAIL    = 'pg_error_detail';
    const TRANSACTION_ID  = 'new_transaction_id';
    const MERCHANT_REF_NO = 'new_merchant_reference_no';
}
