<?php

namespace RZP\Reconciliator\NetbankingEquitas;

class Constants
{
    const GATEWAY_REFERENCE_NUMBER      = 'gatewayreferencenumber';
    const BANK_TRANSACTION_ID           = 'banktransactionreferenceno';
    const AMOUNT                        = 'transactionamount';
    const STATUS                        = 'status';
    const DATE_OF_TRANSACTION           = 'transactiondate';
    const ACCOUNT_NUMBER                = 'accno';

    const PAYMENT_STATUS_SUCCESS        = 'SUCCESS';

    const COLUMN_HEADERS = [
        self::GATEWAY_REFERENCE_NUMBER,
        self::BANK_TRANSACTION_ID,
        self::AMOUNT,
        self::STATUS,
        self::DATE_OF_TRANSACTION,
        self::ACCOUNT_NUMBER
    ];
}
