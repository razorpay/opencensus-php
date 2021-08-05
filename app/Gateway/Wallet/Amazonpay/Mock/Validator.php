<?php

namespace RZP\Gateway\Wallet\Amazonpay\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Amazonpay\RequestFields;

final class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::PAYLOAD      => 'required|string',
        RequestFields::KEY          => 'required|string',
        RequestFields::IV           => 'required|string',
        RequestFields::REDIRECT_URL => 'required|string|url'
    ];

    protected static $decryptedAuthSignRules = [
        RequestFields::TOTAL_AMOUNT      => 'required|string',
        RequestFields::CURRENCY_CODE     => 'required|string|in:INR',
        RequestFields::ORDER_ID          => 'required|string|size:14',
        RequestFields::IS_SANDBOX        => 'required|string|in:true,false',
        RequestFields::TXN_TIMEOUT       => 'required|string|in:300',
        RequestFields::AWS_ACCESS_KEY_ID => 'required|string',
        RequestFields::SELLER_ID         => 'required|string',
        RequestFields::START_TIME        => 'required|string|integer',
        RequestFields::SIGNATURE         => 'required|string|size:44',
        RequestFields::SELLER_NOTE       => 'required|string|max:255',
        RequestFields::SELLER_STORE_NAME => 'required|string|max:255',
    ];

    protected static $verifyRules = [
        RequestFields::AWS_ACCESS_KEY_ID => 'required|string',
        RequestFields::PAYMENT_DOMAIN    => 'required|string|in:IN_INR',
        RequestFields::QUERY_ID          => 'required|string|size:14',
        RequestFields::QUERY_ID_TYPE     => 'required|string|in:SellerOrderId',
        RequestFields::UC_SELLER_ID      => 'required|string',
        RequestFields::SIGNATURE_METHOD  => 'required|string|in:HmacSHA256',
        RequestFields::SIGNATURE_VERSION => 'required|string|in:2',
        RequestFields::TIMESTAMP         => 'required|string|date_format:Y-m-d\TH:i:s.\\0\\0\\0\\Z',
        RequestFields::SIGNATURE         => 'required|string|size:44',
        'CreatedTimeRange_StartTime'     => 'required|string|date',
        'CreatedTimeRange_EndTime'       => 'required|string|date',
        RequestFields::IS_SANDBOX        => 'required|string|in:true,false',
    ];

    protected static $refundRules = [
        RequestFields::AWS_ACCESS_KEY_ID => 'required|string',
        RequestFields::AMAZON_TRAN_ID    => 'required|string|size:19',
        RequestFields::AMAZON_TRAN_TYPE  => 'required|string|in:OrderReferenceId',
        'RefundAmount_Amount'            => 'required|string',
        'RefundAmount_CurrencyCode'      => 'required|string',
        RequestFields::REFUND_REF_ID     => 'required|string|size:14',
        RequestFields::UC_SELLER_ID      => 'required|string',
        RequestFields::SIGNATURE_METHOD  => 'required|string|in:HmacSHA256',
        RequestFields::SIGNATURE_VERSION => 'required|string|in:2',
        RequestFields::TIMESTAMP         => 'required|string|date_format:Y-m-d\TH:i:s.\\0\\0\\0\\Z',
        RequestFields::SIGNATURE         => 'required|string|size:44',
        RequestFields::IS_SANDBOX        => 'required|string|in:true,false',
    ];

    protected static $verifyRefundRules = [
        RequestFields::AWS_ACCESS_KEY_ID => 'required|string',
        'AmazonRefundId'                 => 'required|string',
        RequestFields::UC_SELLER_ID      => 'required|string',
        RequestFields::SIGNATURE_METHOD  => 'required|string|in:HmacSHA256',
        RequestFields::SIGNATURE_VERSION => 'required|string|in:2',
        RequestFields::TIMESTAMP         => 'required|string|date_format:Y-m-d\TH:i:s.\\0\\0\\0\\Z',
        RequestFields::SIGNATURE         => 'required|string|size:44',
        RequestFields::IS_SANDBOX        => 'required|string|in:true,false',
    ];
}
