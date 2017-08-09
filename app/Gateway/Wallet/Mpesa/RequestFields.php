<?php

namespace RZP\Gateway\Wallet\Mpesa;

class RequestFields
{
    // POST params
    const GATEWAY_PARAM             = 'gatewayparam';
    const CHECKSUM                  = 'checksum';

    // XML params
    const PAYMENT_GATEWAY_REQUEST   = 'PaymentGatewayRequest';
    const MERCHANT_CODE             = 'MCODE';
    const TRANSACTION_DATE          = 'TXNDATE';
    const TRANSACTION_TYPE          = 'TXNTYPE';
    const TRANSACTION_REFERENCE     = 'TRANSREFNO';
    const AMOUNT                    = 'AMT';
    const NARRATION                 = 'NARRATION';
    const RETURN_URL                = 'RETURNURL';
    const SURCHARGE                 = 'SURCHARGE';
    const FILLER3                   = 'FILLER3';

    // S2S params
    const QUERY_TRANSACTION_DATE    = 'txnDate';
    const QUERY_TRANSACTION_REF     = 'transRefNo';
    const COM_TRANSACTION_ID        = 'mcomPgTransID';
    const PMT_TRANSACTION_REFERENCE = 'paymentTransRefNo';
    const CMDID                     = 'CMDID';

    // Customer Validate params
    const COMMON_SERVICE_DATA       = 'commonServiceData';
    const CHANNEL_ID                = 'channelID';
    const REQUEST_ID                = 'requestId';
    const MOBILE_NUMBER             = 'MSISDN';

    // Otp params
    const ENTITY_TYPE_ID            = 'entityTypeId';
    const MERCHANT_ID               = 'MrchntId';
    const OTP                       = 'OTP';
    const MCOM_PAYMENT_REQ          = 'McomMrchntPymtReq';
    const FROM_ENTITY_TYPE          = 'fromEnttyType';
    const TO_ENTITY_TYPE            = 'toEnttyType';
    const COMMAND_ID                = 'cmdId';
    const OTP_REF_NUMBER            = 'OTPREFNUM';

    // Refund params
    const S2S_AMOUNT                = 'amt';
    const REVERSAL_TYPE             = 'reversalType';
    const REFUND_NARRATION          = 'narration';
}
