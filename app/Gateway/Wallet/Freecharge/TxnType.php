<?php

namespace RZP\Gateway\Wallet\Freecharge;

/**
 * TxnType is the txn type which we need to pass
 * to get transaction status
 *
 */
class TxnType
{
    const CUSTOMER_PAYMENT    = 'CUSTOMER_PAYMENT';
    const ADD_CASH            = 'ADD_CASH';
    const CANCELLATION_REFUND = 'CANCELLATION_REFUND';
}
