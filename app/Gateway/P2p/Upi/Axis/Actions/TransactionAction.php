<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;
use RZP\Gateway\P2p\Upi\Axis\S2sDirect;

class TransactionAction extends Action
{
    const SEND_MONEY                                = 'SEND_MONEY';

    const REQUEST_MONEY                             = 'REQUEST_MONEY';

    const PAY_COLLECT                               = 'PAY_COLLECT';

    const DECLINE_COLLECT                           = 'DECLINE_COLLECT';

    const RAISE_QUERY                               = 'RAISE_QUERY';

    const QUERY_STATUS                              = 'QUERY_STATUS';

    const PAY                                       = 'PAY';

    const MAP = [
        self::SEND_MONEY => [
            self::VALIDATOR => [
                Fields::MERCHANT_REQUEST_ID         => 'required',
                Fields::MERCHANT_CUSTOMER_ID        => 'required',
                Fields::CUSTOMER_VPA                => 'required',
                Fields::PAYEE_VPA                   => 'required',
                Fields::PAYEE_NAME                  => 'sometimes',
                Fields::AMOUNT                      => 'required',
                Fields::UPI_REQUEST_ID              => 'required',
                Fields::ACCOUNT_REFERENCE_ID        => 'required',
                Fields::REMARKS                     => 'sometimes',
                Fields::TIME_STAMP                  => 'required',
                Fields::PAY_TYPE                    => 'required',
                Fields::CURRENCY                    => 'required',
                Fields::MCC                         => 'sometimes',
                Fields::REF_URL                     => 'sometimes',
                Fields::TRANSACTION_REFERENCE       => 'sometimes',
            ],

            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CURRENCY,
                Fields::CUSTOMER_VPA,
                Fields::MCC,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::PAYEE_NAME,
                Fields::PAYEE_VPA,
                Fields::PAY_TYPE,
                Fields::REF_URL,
                Fields::REMARKS,
                Fields::TIME_STAMP,
                Fields::TRANSACTION_REFERENCE,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID
            ],

            self::RESPONSE => [

                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BANK_CODE,
                    Fields::CUSTOMER_MOBILE_NUMBER,
                    Fields::CUSTOMER_VPA,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_TRANSACTION_ID,
                    Fields::MASKED_ACCOUNT_NUMBER,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::PAY_TYPE,
                    Fields::TRANSACTION_TIME_STAMP,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],

        self::REQUEST_MONEY => [
            self::VALIDATOR => [
                Fields::MERCHANT_REQUEST_ID     => 'required',
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_VPA            => 'required',
                Fields::PAYER_VPA               => 'required',
                Fields::PAYER_NAME              => 'sometimes',
                Fields::COLLECT_REQ_EXPIRY_MINS => 'required',
                Fields::AMOUNT                  => 'required',
                Fields::ACCOUNT_REFERENCE_ID    => 'required',
                Fields::REMARKS                 => 'sometimes',
                Fields::UPI_REQUEST_ID          => 'required',
                Fields::TIME_STAMP              => 'required',
            ],
            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::COLLECT_REQ_EXPIRY_MINS,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::PAYER_NAME,
                Fields::PAYER_VPA,
                Fields::REMARKS,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID,
            ],

            self::RESPONSE => [

                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BANK_CODE,
                    Fields::CUSTOMER_MOBILE_NUMBER,
                    Fields::CUSTOMER_VPA,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_TRANSACTION_ID,
                    Fields::MASKED_ACCOUNT_NUMBER,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::PAY_TYPE,
                    Fields::TRANSACTION_TIME_STAMP,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],

        self::PAY_COLLECT => [
            self::VALIDATOR => [
                Fields::MERCHANT_REQUEST_ID     => 'required',
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_VPA            => 'required',
                Fields::PAYEE_VPA               => 'required',
                Fields::AMOUNT                  => 'required',
                Fields::ACCOUNT_REFERENCE_ID    => 'required',
                Fields::REMARKS                 => 'sometimes',
                Fields::UPI_REQUEST_ID          => 'required',
                Fields::TIME_STAMP              => 'required',
            ],
            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::PAYEE_VPA,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID,
            ],

            self::RESPONSE => [

                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BANK_CODE,
                    Fields::CUSTOMER_MOBILE_NUMBER,
                    Fields::CUSTOMER_VPA,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_TRANSACTION_ID,
                    Fields::MASKED_ACCOUNT_NUMBER,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::PAY_TYPE,
                    Fields::TRANSACTION_TIME_STAMP,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],

        self::DECLINE_COLLECT => [
            self::VALIDATOR => [
                Fields::MERCHANT_REQUEST_ID     => 'required',
                Fields::MERCHANT_CUSTOMER_ID    => 'required',
                Fields::CUSTOMER_VPA            => 'required',
                Fields::PAYEE_VPA               => 'required',
                Fields::AMOUNT                  => 'required',
                Fields::ACCOUNT_REFERENCE_ID    => 'required',
                Fields::REMARKS                 => 'sometimes',
                Fields::UPI_REQUEST_ID          => 'required',
                Fields::TIME_STAMP              => 'required',
            ],
            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::PAYEE_VPA,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID,
            ],

            self::RESPONSE => [

                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BANK_CODE,
                    Fields::CUSTOMER_MOBILE_NUMBER,
                    Fields::CUSTOMER_VPA,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_TRANSACTION_ID,
                    Fields::MASKED_ACCOUNT_NUMBER,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::PAY_TYPE,
                    Fields::TRANSACTION_TIME_STAMP,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],

        self::RAISE_QUERY => [
            self::SOURCE    => self::DIRECT,
            self::DIRECT    => [
                S2sDirect::METHOD => 'post'
            ],
        ],

        self::QUERY_STATUS => [
            self::SOURCE    => self::DIRECT,
            self::DIRECT    => [
                S2sDirect::METHOD => 'post'
            ],
        ],

        self::PAY => [
            self::VALIDATOR  => [
                Fields::MERCHANT_REQUEST_ID  => 'required',
                Fields::MERCHANT_CUSTOMER_ID => 'required',
                Fields::CUSTOMER_VPA         => 'required',
                Fields::MERCHANT_VPA         => 'required',
                Fields::AMOUNT               => 'required',
                Fields::ACCOUNT_REFERENCE_ID => 'required',
                Fields::REMARKS              => 'sometimes',
                Fields::UPI_REQUEST_ID       => 'required',
                Fields::TIMESTAMP            => 'required',
            ],
            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::MERCHANT_VPA,
                Fields::REMARKS,
                Fields::TIMESTAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID
            ],
            self::RESPONSE => [
                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BANK_CODE,
                    Fields::CUSTOMER_MOBILE_NUMBER,
                    Fields::CUSTOMER_VPA,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_TRANSACTION_ID,
                    Fields::MASKED_ACCOUNT_NUMBER,
                    Fields::TRANSACTION_TIME_STAMP,
                    Fields::UDF_PARAMETERS,
                ]
            ]
        ]
    ];
}
