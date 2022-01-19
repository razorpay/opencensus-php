<?php

namespace RZP\Models\Payment\Fraud;

class Constants
{
    const PAYMENT_ID    = 'payment_id';
    const CODE          = 'code';
    const REASON        = 'reason';
    const TYPES         = 'types';
    const SUB_TYPES     = 'sub_types';
    const REPORTED_BY   = 'reported_by';

    const HAS_CHARGEBACK        = 'has_chargeback';
    const SKIP_MERCHANT_EMAIL   = 'skip_merchant_email';

    const BUYER_RISK    = 'buyer_risk';
    const MERCHANT_RISK = 'merchant_risk';

    const REPORTED_BY_CYBERSAFE     = 'Cybersafe';
    const REPORTED_BY_CYBERCELL     = 'Cybercell';
    const REPORTED_BY_ISSUER        = 'Issuer';
    const REPORTED_BY_VISA          = 'Visa';
    const REPORTED_BY_MASTERCARD    = 'MasterCard';
    const REPORTED_BY_NETWORK       = 'Network';
    const INTERNAL_CHARGEBACK_CODE  = 'RZP_INT';

    const REPORTED_BY_VALUES = [
        self::BUYER_RISK    =>  [self::REPORTED_BY_CYBERSAFE, self::REPORTED_BY_CYBERCELL, self::REPORTED_BY_ISSUER],
        self::MERCHANT_RISK =>  [self::REPORTED_BY_VISA, self::REPORTED_BY_MASTERCARD, self::REPORTED_BY_NETWORK],
    ];

    const REPORTED_BY_CSV = self::REPORTED_BY_CYBERSAFE . ',' . self::REPORTED_BY_CYBERCELL . ',' . self::REPORTED_BY_ISSUER
        . ',' . self::REPORTED_BY_VISA . ',' . self::REPORTED_BY_MASTERCARD . ',' . self::REPORTED_BY_NETWORK;

    const FRAUD_TYPES_CSV = BankCodes::FRAUD_CODE_0 . ',' . BankCodes::FRAUD_CODE_1 . ',' . BankCodes::FRAUD_CODE_2 . ',' . BankCodes::FRAUD_CODE_3 . ',' .
                            BankCodes::FRAUD_CODE_4 . ',' . BankCodes::FRAUD_CODE_5 . ',' . BankCodes::FRAUD_CODE_6 . ',' . BankCodes::FRAUD_CODE_9 . ',' .
                            BankCodes::FRAUD_CODE_A . ',' . BankCodes::FRAUD_CODE_B . ',' . BankCodes::FRAUD_CODE_00 . ',' . BankCodes::FRAUD_CODE_01 . ',' .
                            BankCodes::FRAUD_CODE_02 . ',' . BankCodes::FRAUD_CODE_03 . ',' . BankCodes::FRAUD_CODE_04 . ',' . BankCodes::FRAUD_CODE_05 . ',' .
                            BankCodes::FRAUD_CODE_06 . ',' . BankCodes::FRAUD_CODE_07. ',' . BankCodes::FRAUD_CODE_51;

    const FRAUD_SUB_TYPES_CSV = BankCodes::FRAUD_CODE_K . ',' . BankCodes::FRAUD_CODE_N . ','
                                . BankCodes::FRAUD_CODE_P . ',' . BankCodes::FRAUD_CODE_U;

    const DEFAULT_FRAUD_TYPES = [
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_0,
            self::REASON    =>  FraudReasons::CARD_REPORTED_LOST,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_1,
            self::REASON    =>  FraudReasons::STOLEN,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_2,
            self::REASON    =>  FraudReasons::NOT_RECEIVED_AS_ISSUED,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_3,
            self::REASON    =>  FraudReasons::FRAUDULENT_APPLICATION,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_4,
            self::REASON    =>  FraudReasons::ISSUER_COUNTERFEIT,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_5,
            self::REASON    =>  FraudReasons::MISCELLANEOUS,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_6,
            self::REASON    =>  FraudReasons::FRAUDULENT_USE_OF_ACCOUNT_NO,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_9,
            self::REASON    =>  FraudReasons::ACQUIRER_COUNTERFEIT,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_A,
            self::REASON    =>  FraudReasons::INCORRECT_PROCESSING,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_B,
            self::REASON    =>  FraudReasons::ACC_OR_CREDS_TAKEOVER,
        ],
    ];

    const VISA_FRAUD_TYPES = self::DEFAULT_FRAUD_TYPES;

    const MASTERCARD_FRAUD_TYPES = [
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_00,
            self::REASON    =>  FraudReasons::LOST_FRAUD,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_01,
            self::REASON    =>  FraudReasons::STOLEN_FRAUD,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_02,
            self::REASON    =>  FraudReasons::NEVER_RECEIVED_FRAUD,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_03,
            self::REASON    =>  FraudReasons::FRAUDULENT_APPLICATIONS,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_04,
            self::REASON    =>  FraudReasons::COUNTERFEIT_CARD_FRAUD,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_05,
            self::REASON    =>  FraudReasons::ACCOUNT_TAKER_FRAUD,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_06,
            self::REASON    =>  FraudReasons::CARD_NOT_PRESENT,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_07,
            self::REASON    =>  FraudReasons::CARD_MULTIPLE_IMPRINT,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_51,
            self::REASON    =>  FraudReasons::BUST_OUT_COLLUSIVE_MERCHANT,
        ],
    ];

    const MASTERCARD_FRAUD_SUB_TYPES = [
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_K,
            self::REASON    =>  FraudReasons::CONVENIENCE_OR_BALANCE_TRANSFER_CHECK_TRANSACTION,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_N,
            self::REASON    =>  FraudReasons::PIN_NOT_USED,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_P,
            self::REASON    =>  FraudReasons::PIN_USED,
        ],
        [
            self::CODE      =>  BankCodes::FRAUD_CODE_U,
            self::REASON    =>  FraudReasons::UNKNOWN,
        ],
    ];
}
