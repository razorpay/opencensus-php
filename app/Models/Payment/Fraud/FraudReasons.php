<?php

namespace RZP\Models\Payment\Fraud;

class FraudReasons
{
    const CARD_REPORTED_LOST           = 'Card reported lost';
    const STOLEN                       = 'Stolen';
    const NOT_RECEIVED_AS_ISSUED       = 'Not received as issued (NRI)';
    const FRAUDULENT_APPLICATION       = 'Fraudulent application';
    const ISSUER_COUNTERFEIT           = 'Issuer counterfeit';
    const MISCELLANEOUS                = 'Miscellaneous';
    const FRAUDULENT_USE_OF_ACCOUNT_NO = 'Fraudulent use of account number';
    const ACQUIRER_COUNTERFEIT         = 'Acquirer counterfeit';
    const INCORRECT_PROCESSING         = 'Incorrect processing';
    const ACC_OR_CREDS_TAKEOVER        = 'Account or credentials takeover';

    const LOST_FRAUD                   = 'Lost Fraud';
    const STOLEN_FRAUD                 = 'Stolen Fraud';
    const NEVER_RECEIVED_FRAUD         = 'Never Received Fraud';
    const FRAUDULENT_APPLICATIONS      = 'Fraudulent Applications';
    const COUNTERFEIT_CARD_FRAUD       = 'Counterfeit Card Fraud';
    const ACCOUNT_TAKER_FRAUD          = 'Account Takeover Fraud';
    const CARD_NOT_PRESENT             = 'Card Not Present Fraud';
    const CARD_MULTIPLE_IMPRINT        = 'Multiple Imprint Fraud';
    const BUST_OUT_COLLUSIVE_MERCHANT  = 'Bust-out Collusive Merchant';

    const CONVENIENCE_OR_BALANCE_TRANSFER_CHECK_TRANSACTION    = 'Convenience or Balance Transfer check transaction';
    const PIN_NOT_USED                                         = 'PIN not used in transaction';
    const PIN_USED                                             = 'PIN used in transaction';
    const UNKNOWN                                              = 'Unknown';
}
