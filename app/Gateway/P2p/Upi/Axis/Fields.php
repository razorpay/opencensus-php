<?php

namespace RZP\Gateway\P2p\Upi\Axis;

class Fields
{
    const ID                        = 'id';
    const PAYLOAD                   = 'payload';
    const ACTION                    = 'action';
    const SDK                       = 'sdk';
    const CALLBACK                  = 'callback';
    const CONTENT                   = 'content';
    const VALIDATE                  = 'validate';
    const API_ACTION                = 'api_action';
    const TOKEN                     = 'token';

    // --------------------------- DEVICE --------------- //
    const SIM_ID                        = 'simId';
    const UDF_PARAMETERS                = 'udfParameters';
    const STATUS                        = 'status';
    const IS_DEVICE_BOUND               = 'isDeviceBound';
    const IS_DEVICE_ACTIVATED           = 'isDeviceActivated';
    const CUSTOMER_MOBILE_NUMBER        = 'customerMobileNumber';
    const DEVICE_FINGERPRINT            = 'deviceFingerPrint';
    const ERROR_CODE                    = 'errorCode';
    const ERROR_DESCRIPTION             = 'errorDescription';
    const MERCHANT_CUSTOMER_ID          = 'merchantCustomerId';
    const SHOULD_ACTIVATE               = 'shouldActivate';
    const TIME_STAMP                    = 'timestamp';
    const MERCHANT_SIGNATURE            = 'merchantSignature';
    const VPA_ACCOUNTS                  = 'vpaAccounts';
    const TIMESTAMP                     = 'timestamp';
    const ACCOUNTS                      = 'accounts';
    const VPA_SUGGESTIONS               = 'vpaSuggestions';
    const DEVICE_DATA                   = 'device_data';
    const GATEWAY_DATA                  = 'gateway_data';
    const SDK_DATA                      = 'sdk_data';
    const ERROR                         = 'error';
    const MERCHANT_ID                   = 'merchantId';
    const CURRENCY                      = 'currency';
    const MCC                           = 'mcc';
    const MERCHANT_CHANNEL_ID           = 'merchantChannelId';
}
