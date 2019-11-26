<?php

namespace RZP\Gateway\Mozart\NetbankingKvb;

class RefundFields
{
    const SR_NO                 = 'Sr.No.';
    const TRANSACTION_DATE      = 'Trans Date';
    const REFUND_DATE           = 'Refund Date';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No.';
    const PGI_REFERENCE_NO      = 'PGI Reference No.';
    const PAYMENT_AMOUNT        = 'Txn Amount (Rs Ps)';
    const REFUND_AMOUNT         = 'Refund Amount (Rs Ps)';
    const ACCOUNT_NUMBER        = 'Bank Account No.';

    const REFUND_FIELDS = [
        self::SR_NO,
        self::TRANSACTION_DATE,
        self::REFUND_DATE,
        self::BANK_REFERENCE_NUMBER,
        self::PGI_REFERENCE_NO,
        self::ACCOUNT_NUMBER,
        self::PAYMENT_AMOUNT,
        self::REFUND_AMOUNT,
    ];
}
