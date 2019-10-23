<?php

namespace RZP\Gateway\Netbanking\Sbi\ReconFields;

class RefundReconFields
{
    const SBI_REF_NO      = 'sbi_ref_no';
    const MERCHANT_REF_NO = 'merchant_ref_no';
    const REFUND_REF_NO   = 'refund_ref_no';
    const REFUND_TIME     = 'refund_time';
    const SEQ_NO          = 'sequence_no';
    const AMOUNT          = 'amount';
    const STATUS          = 'status';
    const REMARKS         = 'remarks';

    const REFUND_COLUMN_HEADERS = [
        self::SBI_REF_NO,
        self::MERCHANT_REF_NO,
        self::REFUND_REF_NO,
        self::REFUND_TIME,
        self::SEQ_NO,
        self::AMOUNT,
        self::STATUS,
        self::REMARKS,
    ];
}
