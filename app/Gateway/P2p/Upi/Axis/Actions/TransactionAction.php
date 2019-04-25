<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

class TransactionAction extends Action
{
    const SEND_MONEY    = 'SEND_MONEY';

    const REQUEST_MONEY = 'REQUEST_MONEY';

    const AUTHORIZE_TRANSACTION = 'authorizeTransaction';

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
            ],

            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CURRENCY,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CATEGORY_CODE,
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
        ],
    ];
}
