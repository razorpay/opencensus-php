<?php

namespace RZP\Gateway\Netbanking\Sbi\ReconFields;

class PaymentReconFields
{
    const MERCHANT_ID        = 'merchant_id';
    const GATEWAY_REF_NO     = 'gateway_reference_number';
    const BANK_TRAN_REF_NO   = 'bank_transaction_referenceno';
    const TRANSACTION_AMOUNT = 'transaction_amount';
    const STATUS             = 'status';
    const TRANSACTION_DATE   = 'transaction_date';

    const PAYMENT_COLUMN_HEADERS = [
        self::MERCHANT_ID,
        self::GATEWAY_REF_NO,
        self::BANK_TRAN_REF_NO,
        self::TRANSACTION_AMOUNT,
        self::STATUS,
        self::TRANSACTION_DATE,
    ];
}
