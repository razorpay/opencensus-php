<?php

namespace RZP\Gateway\P2p\Upi\Axis\Actions;

use RZP\Gateway\P2p\Upi\Axis\Fields;

/**
 * This is the class for the mandate action , contains all the flows and their requests
 * Class MandateAction
 *
 * @package RZP\Gateway\P2p\Upi\Axis\Actions
 */
class MandateAction extends Action
{
    const APPROVE_DECLINE_MANDATE  = 'APPROVE_DECLINE_MANDATE';
    const PAUSE_UNPAUSE_MANDATE    = 'PAUSE_UNPAUSE_MANDATE';
    const UPDATE_OR_REVOKE_MANDATE = 'UPDATE_OR_REVOKE_MANDATE';

    const CREATE            = 'CREATE';
    const APPROVE           = 'APPROVE';
    const DECLINE           = 'DECLINE';
    const REVOKE            = 'REVOKE';
    const REVOKED           = 'REVOKED';
    const SUCCESS           = 'SUCCESS';
    const DECLINED          = 'DECLINED';
    const PAUSE             = 'PAUSE';
    const UNPAUSE           = 'UNPAUSE';
    const PAUSED            = 'PAUSED';
    const UNPAUSED          = 'UNPAUSED';
    const FAILURE           = 'FAILURE';
    const COMPLETED         = 'COMPLETED';

    const MAP = [
        self::APPROVE_DECLINE_MANDATE => [
            self::VALIDATOR => [
                Fields::ACCOUNT_REFERENCE_ID        => 'required',
                Fields::MANDATE_REQUEST_ID          => 'required',
                Fields::MERCHANT_CUSTOMER_ID        => 'required',
                Fields::AMOUNT                      => 'required',
                Fields::CUSTOMER_VPA                => 'required',
                Fields::EXPIRY                      => 'sometimes',
                Fields::INITIATED_BY                => 'sometimes',
                Fields::PAYEE_NAME                  => 'sometimes',
                Fields::PAYEE_VPA                   => 'sometimes',
                Fields::MERCHANT_REQUEST_ID         => 'required',
                Fields::REQUEST_TYPE                => 'required',
                Fields::TIME_STAMP                  => 'required'
            ],

            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::MANDATE_REQUEST_ID,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::PAYEE_VPA,
                Fields::REQUEST_TYPE,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS
            ],

            self::RESPONSE => [
                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::AMOUNT_RULE,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BLOCK_FUND,
                    Fields::EXPIRY,
                    Fields::GATEWAY_MANDATE_ID,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_RESPONSE_STATUS,
                    Fields::INITIATED_BY,
                    Fields::MANDATE_APPROVAL_TIMESTAMP,
                    Fields::MANDATE_NAME,
                    Fields::MANDATE_TIMESTAMP,
                    Fields::MANDATE_TYPE,
                    Fields::MERCHANT_CUSTOMER_ID,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::ORG_MANDATE_ID,
                    Fields::PAYEE_MCC,
                    Fields::PAYEE_NAME,
                    Fields::PAYEE_VPA,
                    Fields::PAYER_NAME,
                    Fields::PAYER_REVOCABLE,
                    Fields::PAYER_VPA,
                    Fields::RECURRENCE_PATTERN,
                    Fields::RECURRENCE_RULE,
                    Fields::RECURRENCE_VALUE,
                    Fields::REF_URL,
                    Fields::REMARKS,
                    Fields::ROLE,
                    Fields::SHARE_TO_PAYEE,
                    Fields::TRANSACTION_TYPE,
                    Fields::UMN,
                    Fields::VALIDITY_END,
                    Fields::VALIDITY_START,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],
        self::PAUSE_UNPAUSE_MANDATE => [
            self::VALIDATOR => [
                Fields::ACCOUNT_REFERENCE_ID        => 'required',
                Fields::AMOUNT                      => 'required',
                Fields::CUSTOMER_VPA                => 'required',
                Fields::MERCHANT_CUSTOMER_ID        => 'required',
                Fields::MERCHANT_REQUEST_ID         => 'required',
                Fields::ORG_MANDATE_ID              => 'required',
                Fields::PAUSE_END                   => 'sometimes',
                Fields::PAUSE_START                 => 'sometimes',
                Fields::PAYEE_VPA                   => 'sometimes',
                Fields::PAYEE_NAME                  => 'sometimes',
                Fields::REMARKS                     => 'sometimes',
                Fields::REQUEST_TYPE                => 'required',
                Fields::TIME_STAMP                  => 'required',
                Fields::UPI_REQUEST_ID              => 'required',
            ],

            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::ORG_MANDATE_ID,
                Fields::PAUSE_END,
                Fields::PAUSE_START,
                Fields::PAYEE_VPA,
                Fields::REMARKS,
                Fields::REQUEST_TYPE,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID,
            ],

            self::RESPONSE => [
                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::AMOUNT_RULE,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BLOCK_FUND,
                    Fields::GATEWAY_MANDATE_ID,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_RESPONSE_STATUS,
                    Fields::INITIATED_BY,
                    Fields::MANDATE_NAME,
                    Fields::MANDATE_TIMESTAMP,
                    Fields::MANDATE_TYPE,
                    Fields::MERCHANT_CUSTOMER_ID,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::ORG_MANDATE_ID,
                    Fields::PAUSE_END,
                    Fields::PAUSE_START,
                    Fields::PAYEE_MCC,
                    Fields::PAYEE_NAME,
                    Fields::PAYEE_VPA,
                    Fields::PAYER_NAME,
                    Fields::PAYER_REVOCABLE,
                    Fields::PAYER_VPA,
                    Fields::RECURRENCE_PATTERN,
                    Fields::RECURRENCE_RULE,
                    Fields::RECURRENCE_VALUE,
                    Fields::REF_URL,
                    Fields::REMARKS,
                    Fields::ROLE,
                    Fields::SHARE_TO_PAYEE,
                    Fields::TRANSACTION_TYPE,
                    Fields::UMN,
                    Fields::VALIDITY_END,
                    Fields::VALIDITY_START,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],
        self::UPDATE_OR_REVOKE_MANDATE => [
            self::VALIDATOR => [
                Fields::ACCOUNT_REFERENCE_ID        => 'required',
                Fields::AMOUNT                      => 'required',
                Fields::CUSTOMER_VPA                => 'required',
                Fields::INITIATED_BY                => 'sometimes',
                Fields::EXPIRY                      => 'sometimes',
                Fields::MERCHANT_CUSTOMER_ID        => 'required',
                Fields::MERCHANT_REQUEST_ID         => 'required',
                Fields::ORG_MANDATE_ID              => 'required',
                Fields::PAYEE_NAME                  => 'sometimes',
                Fields::PAYEE_VPA                   => 'sometimes',
                Fields::REMARKS                     => 'sometimes',
                Fields::REQUEST_TYPE                => 'required',
                Fields::TIME_STAMP                  => 'required',
                Fields::UPI_REQUEST_ID              => 'required',
                Fields::VALIDITY_END                => 'sometimes',
            ],

            self::SIGNATURE => [
                Fields::ACCOUNT_REFERENCE_ID,
                Fields::AMOUNT,
                Fields::CUSTOMER_VPA,
                Fields::EXPIRY,
                Fields::INITIATED_BY,
                Fields::MERCHANT_CUSTOMER_ID,
                Fields::MERCHANT_REQUEST_ID,
                Fields::ORG_MANDATE_ID,
                Fields::PAYEE_VPA,
                Fields::REMARKS,
                Fields::REQUEST_TYPE,
                Fields::TIME_STAMP,
                Fields::UDF_PARAMETERS,
                Fields::UPI_REQUEST_ID,
                Fields::VALIDITY_END
            ],

            self::RESPONSE => [
                self::SIGNATURE => [
                    Fields::AMOUNT,
                    Fields::AMOUNT_RULE,
                    Fields::BANK_ACCOUNT_UNIQUE_ID,
                    Fields::BLOCK_FUND,
                    Fields::EXPIRY,
                    Fields::GATEWAY_MANDATE_ID,
                    Fields::GATEWAY_REFERENCE_ID,
                    Fields::GATEWAY_RESPONSE_CODE,
                    Fields::GATEWAY_RESPONSE_MESSAGE,
                    Fields::GATEWAY_RESPONSE_STATUS,
                    Fields::INITIATED_BY,
                    Fields::MANDATE_NAME,
                    Fields::MANDATE_TIMESTAMP,
                    Fields::MANDATE_TYPE,
                    Fields::MERCHANT_CUSTOMER_ID,
                    Fields::MERCHANT_REQUEST_ID,
                    Fields::ORG_MANDATE_ID,
                    Fields::PAYEE_MCC,
                    Fields::PAYEE_NAME,
                    Fields::PAYEE_VPA,
                    Fields::PAYER_NAME,
                    Fields::PAYER_REVOCABLE,
                    Fields::PAYER_VPA,
                    Fields::RECURRENCE_PATTERN,
                    Fields::RECURRENCE_RULE,
                    Fields::RECURRENCE_VALUE,
                    Fields::REF_URL,
                    Fields::REMARKS,
                    Fields::ROLE,
                    Fields::SHARE_TO_PAYEE,
                    Fields::TRANSACTION_TYPE,
                    Fields::UMN,
                    Fields::VALIDITY_END,
                    Fields::VALIDITY_START,
                    Fields::UDF_PARAMETERS,
                ],
            ],
        ],
    ];
}
