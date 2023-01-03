<?php

namespace RZP\Gateway\P2p\Upi\AxisOlive;

/**
 * Class Fields
 * Fields used for axis olive integration
 * @package RZP\Gateway\P2p\Upi\AxisOlive
 */
class Fields
{
    //----------------------Common function---------------//
    const CONTEXT                           = 'context';
    const ENTITY                            = 'entity';
    const ACTION                            = 'action';

    //------------------------Callback---------------------//
    const CONTENT                           = 'content';
    const TYPE                              = 'type';
    const GATEWAY_RESPONSE_CODE             = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE          = 'gatewayResponseMessage';
    const GATEWAY_RESPONSE_STATUS           = 'gatewayResponseStatus';

    //---------------------------INTERNAL_CLIENT_DATA--------------//
    const MERCH_ID                          = 'merchantId';
    const MERCH_CHANNEL_ID                  = 'merchantChannelId';
    const SUB_MERCH_ID                      = 'subMerchantId';
    const MERCHANT_CUSTOMER_ID              = 'merchantCustomerId';

    // --------------------------- DEVICE --------------- //
    const EMAIL_ID                          = 'emailId';
    const MERCHANT_ID                       = 'merchant_id';
    const MCC                               = 'mcc';
    const MCC_CODE                          = 'mcc_code';
    const MERCHANT_CHANNEL_ID               = 'merchant_channel_id';
    const SUB_MERCHANT_ID                   = 'sub_merchant_id';
    const UNIQUE_CUSTOMER_ID                = 'unique_customer_id';
    const MOBILE_NUMBER                     = 'mobile_number';
    const UNIQUE_TRANSACTION_ID             = 'unique_transaction_id';
    const TIMESTAMP                         = 'timestamp';
    const MERCHANT_CHECK_SUM                = 'merchant_check_sum';
    const CUSTOMER_NAME                     = 'customer_name';
    const PRIORITY                          = 'priority';
    const GATEWAY_TOKEN                     = 'gateway_token';
    const EXPIRE_AT                         = 'expire_at';

    // --------------------------- RESPONSE --------------- //
    const CODE                              = 'code';
    const RESULT                            = 'result';
    const MERCHANT_AUTH_TOKEN               = 'merchantauthtoken';
    const STATUS                            = 'status';
    const RESPONSE_CODE                     = 'responseCode';
    const RESPONSE_MESSAGE                  = 'responseMessage';
    const PAYLOAD                           = 'payload';
    const DATA                              = 'data';
    const CHECK_SUM                         = 'checkSum';
    const RISK_SCORE_VALUE                  = 'riskScoreValue';
    const ERROR                             = 'error';
    const RAW                               = '_raw';
    const NEXT                              = 'next';
    const SUCCESS                           = 'success';
    const GATEWAYS                          = 'gateways';


    // ----------------------COMPLAINT RESPONSE --------------- //
    const COMPLAINT                         = 'complaint';
    const ORG_TXN_ID                        = 'orgTxnId';
    const ORG_RRN                           = 'orgRrn';
    const ORG_TXN_DATE                      = 'orgTxnDate';
    const REF_ADJ_FLAG                      = 'refAdjFlag';
    const REF_ADJ_CODE                      = 'refAdjCode';
    const REF_ADJ_AMOUNT                    = 'refAdjAmount';
    const REF_ADJ_REMARKS                   = 'refAdjRemarks';
    const CRN                               = 'crn';
    const REF_ADJ_TS                        = 'refAdjTs';
    const REF_ADJ_REF_ID                    = 'refAdjRefId';
    const GATEWAY_TRANSACTION_ID            = 'gateway_transaction_id';
    const RRN                               = 'rrn';
    const TRANSACTION_TIME_STAMP            = 'transaction_time_stamp';
    const REFERENCE_ADJ_FLAG                = 'reference_adj_flag';
    const REFERENCE_ADJ_CODE                = 'reference_adj_code';
    const REFERENCE_ADJ_AMOUNT              = 'reference_adj_amount';
    const REFERENCE_ADJ_REMARKS             = 'reference_adj_remarks';
    const REQ_ADJ_FLAG                      = 'reqAdjFlag';
    const REQ_ADJ_CODE                      = 'reqAdjCode';
    const REQ_ADJ_AMOUNT                    = 'reqAdjAmount';
    const REQUESTED_ADJ_FLAG                = 'requested_adj_flag';
    const REQUESTED_ADJ_CODE                = 'requested_adj_code';
    const REQUESTED_ADJ_AMOUNT              = 'requested_adj_amount';
    const REQUESTED_ADJ_REMARKS             = 'requested_adj_remarks';
    const TRANSACTION_ORIGINATION_TIME      = 'transaction_origination_time';
    const GATEWAY_COMPLAINT_REFERENCE_ID    = 'gateway_complaint_reference_id';
    const INITIATION_MODE                   = 'initiation_mode';
    const INIT_MODE                         = 'init_mode';
    const SUB_TYPE                          = 'sub_type';
    const SUBTYPE                           = 'subType';
}
