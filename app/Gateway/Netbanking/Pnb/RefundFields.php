<?php

namespace RZP\Gateway\Netbanking\Pnb;

class RefundFields
{
    const PAYMENT_ID             = 'aggregator_refernce_no';
    const REFUND_OR_CANCELLATION = 'refund_or_cancellation';
    const REFUND_AMOUNT          = 'refund_amount';
    const BANK_PAYMENT_ID        = 'bank_refernce_no';
    const DATE                   = 'date';
    const TRANSACTION_AMOUNT     = 'transaction_amount';
    const REFUND_ID              = 'refund_id';
}
