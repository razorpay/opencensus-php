<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

/**
 * Class Fields
 * Fields used for axis olive integration
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class Fields
{
    //---------------------------INTERNAL_CLIENT_DATA--------------//
    const MERCH_ID                     = 'merchantId';
    const MERCH_CHANNEL_ID             = 'merchantChannelId';
    const SUB_MERCH_ID                 = 'subMerchantId';
    const MERCHANT_CUSTOMER_ID         = 'merchantCustomerId';

    // --------------------------- DEVICE --------------- //
    const EMAIL_ID                      = 'emailId';
    const MERCHANT_ID                   = 'merchant_id';
    const MCC                           = 'mcc';
    const MCC_CODE                      = 'mcc_code';
    const MERCHANT_CHANNEL_ID           = 'merchant_channel_id';
    const SUB_MERCHANT_ID               = 'sub_merchant_id';
    const UNIQUE_CUSTOMER_ID            = 'unique_customer_id';
    const MOBILE_NUMBER                 = 'mobile_number';
    const UNIQUE_TRANSACTION_ID         = 'unique_transaction_id';
    const TIMESTAMP                     = 'timestamp';
    const MERCHANT_CHECK_SUM            = 'merchant_check_sum';
    const CUSTOMER_NAME                 = 'customer_name';
    const PRIORITY                      = 'priority';
    const GATEWAY_TOKEN                 = 'gateway_token';
    const EXPIRE_AT                     = 'expire_at';

    // --------------------------- RESPONSE --------------- //
    const CODE                          = 'code';
    const RESULT                        = 'result';
    const MERCHANT_AUTH_TOKEN           = 'merchantauthtoken';
    const STATUS                        = 'status';
    const RESPONSE_CODE                 = 'responseCode';
    const RESPONSE_MESSAGE              = 'responseMessage';
    const PAYLOAD                       = 'payload';
    const DATA                          = 'data';
    const CHECK_SUM                     = 'checkSum';
    const RISK_SCORE_VALUE              = 'riskScoreValue';
    const ERROR                         = 'error';
    const RAW                           = '_raw';
    const NEXT                          = 'next';
    const SUCCESS                       = 'success';
    const GATEWAYS                      = 'gateways';

}
