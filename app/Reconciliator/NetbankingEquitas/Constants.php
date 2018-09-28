<?php

namespace RZP\Reconciliator\NetbankingEquitas;

class Constants
{
    const GATEWAY_REFERENCE_NUMBER      = 'GatewayReferenceNumber';
    const BANK_REFERENCE_NUMBER         = 'BankTransactionReferenceNo';
    const AMOUNT                        = 'TransactionAmount';
    const STATUS                        = 'STATUS';
    const DATE_OF_TRANSACTION           = 'TRANSACTIONDATE';
    const ACCOUNT_NUMBER                = 'ACCNO';

    const PAYMENT_STATUS_SUCCESS        = 'SUCCESS';

    const COLUMN_HEADERS = [
        self::GATEWAY_REFERENCE_NUMBER,
        self::BANK_REFERENCE_NUMBER,
        self::AMOUNT,
        self::STATUS,
        self::DATE_OF_TRANSACTION,
        self::ACCOUNT_NUMBER
    ];
}
