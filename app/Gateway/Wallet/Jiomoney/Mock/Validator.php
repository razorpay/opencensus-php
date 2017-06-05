<?php

namespace RZP\Gateway\Wallet\Jiomoney\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Jiomoney\RequestFields;

class Validator extends Base\Validator
{
    protected static $authorizeRules = [
        RequestFields::MERCHANT_ID                                     => 'required|string',
        RequestFields::CLIENT_ID                                       => 'required|string',
        RequestFields::CHANNEL                                         => 'required|in:WEB',
        RequestFields::CALLBACK_URL                                    => 'required|url',
        RequestFields::TOKEN                                           => 'present',
        RequestFields::TRANSACTION . '.' . RequestFields::PAYMENT_ID   => 'sometimes|string',
        RequestFields::TRANSACTION . '.' . RequestFields::TIMESTAMP    => 'sometimes|string|size:14',
        RequestFields::TRANSACTION . '.' . RequestFields::TXN_TYPE     => 'sometimes|in:PURCHASE',
        RequestFields::TRANSACTION . '.' . RequestFields::AMOUNT       => 'sometimes|string',
        RequestFields::TRANSACTION . '.' . RequestFields::CURRENCY     => 'sometimes|in:INR',
        RequestFields::SUBSCRIBER . '.' . RequestFields::CUSTOMER_NAME => 'sometimes|string',
        RequestFields::SUBSCRIBER . '.' . RequestFields::EMAIL         => 'sometimes|email',
        RequestFields::SUBSCRIBER . '.' . RequestFields::CONTACT       => 'sometimes|string',
        RequestFields::CHECKSUM                                        => 'sometimes|string'
    ];

    protected static $refundRules = [
        RequestFields::MERCHANT_ID  => 'required|string',
        RequestFields::CLIENT_ID    => 'required|string',
        RequestFields::CHANNEL      => 'required|in:WEB',
        RequestFields::TOKEN        => 'present',
        RequestFields::CALLBACK_URL => 'required|in:NA',
        RequestFields::TRANSACTION  => 'required|array|custom',
        RequestFields::CHECKSUM     => 'required|string',
        RequestFields::REFUND_INFO  => 'required|string'
    ];

    protected static $statusQueryRules = [
        'request_header' => 'required|array|custom',
        'payload_data'   => 'required|array|custom',
        'checksum'       => 'required|string'
    ];

    protected static $checkTxnStatusRules = [
        RequestFields::APINAME       => 'required|string|in:CHECKPAYMENTSTATUS,GETREQUESTSTATUS',
        RequestFields::MODE          => 'required|string|in:2',
        RequestFields::REQUEST_ID    => 'required|string',
        RequestFields::STARTDATETIME => 'required|string|in:NA',
        RequestFields::ENDDATETIME   => 'required|string|in:NA',
        RequestFields::MERCHANT_ID   => 'required|string',
        RequestFields::PAYMENT_ID    => 'required|string',
        RequestFields::CHECKSUM      => 'required|string'
    ];

    protected static $refundTransactionAttributeRules = [
        RequestFields::PAYMENT_ID => 'required|string',
        RequestFields::TIMESTAMP  => 'required|string|size:14',
        RequestFields::TXN_TYPE   => 'required|string|in:REFUND',
        RequestFields::AMOUNT     => 'required|string',
        RequestFields::CURRENCY   => 'required|in:INR'
    ];

    protected static $statusQueryRequestHeaderRules = [
        'version'       => 'required|string|in:1.0',
        'api_name'      => 'required|string|in:STATUSQUERY',
        'txn_not_found' => 'sometimes'
    ];

    protected static $statusQueryPayloadRules = [
        'client_id'   => 'required|string',
        'merchant_id' => 'required|string',
        'tran_ref_no' => 'required|string',
        'amount'      => 'sometimes|string'
    ];

    protected function validateTransaction($attribute, $value)
    {
        $this->validateInput('refund_transaction_attribute', $value);
    }

    protected function validatePayloadData($attribute, $value)
    {
        $this->validateInput('status_query_payload', $value);
    }

    protected function validateRequestHeader($attribute, $value)
    {
        $this->validateInput('status_query_request_header', $value);
    }
}
