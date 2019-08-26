<?php

namespace RZP\Gateway\Mozart\NetbankingIbk;


class RefundFields
{
    const SR_NO                 = "Sr.No";
    const REFUND_ID             = "Refund Id";
    const BANK_ID               = "Bank Id";
    const MERCHANT_NAME         = "Merchant Name";
    const TXN_DATE              = "Txn Date";
    const REFUND_DATE           = "Refund Date";
    const BANK_MERHCANT_CODE    = "Bank Merchant Code";
    const BANK_REF_NO           = "Bank Ref No.";
    const PGI_REF_NO            = "PGI Reference No.";
    const TXN_AMOUNT            = "Txn Amount (Rs Ps)";
    const REFUND_AMOUNT         = "Refund Amount (Rs Ps)";

    const REFUND_FIELDS = [
        self::SR_NO,
        self::REFUND_ID,
        self::BANK_ID,
        self::MERCHANT_NAME,
        self::TXN_DATE,
        self::REFUND_DATE,
        self::BANK_MERHCANT_CODE,
        self::BANK_REF_NO,
        self::PGI_REF_NO,
        self::TXN_AMOUNT,
        self::REFUND_AMOUNT,
    ];
}
