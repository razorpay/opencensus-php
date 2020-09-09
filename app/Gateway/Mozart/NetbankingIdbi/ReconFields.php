<?php

namespace RZP\Gateway\Mozart\NetbankingIdbi;

class ReconFields
{
    const PAYMENT_BANK              = 'Bank';
    const PAYMENT_DATE              = 'TRANSACTIONDATE';
    const PAYMENT_ITC               = 'PaymentGateway';
    const PAYMENT_AMOUNT            = 'TransactionAmount';
    const PAYMENT_ID                = 'PaymentGatewayReferenceNumber';
    const BANK_REFERENCE_NUMBER     = 'BankTransactionReferenceNo';

    const RECON_FIELDS = [
        self::PAYMENT_BANK,
        self::PAYMENT_DATE,
        self::PAYMENT_ITC,
        self::PAYMENT_AMOUNT,
        self::PAYMENT_ID,
        self::BANK_REFERENCE_NUMBER,
    ];
}
