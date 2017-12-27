<?php

namespace RZP\Gateway\Aeps\Icici;

class RequestConstants
{
    const REVERSAL_MSG_TYPE     = '0400';
    const REQUEST_MSG_TYPE      = '0100';

    const OFFUS                 = 'OFFUS.APAY';
    const ONUS                  = 'SL.APAY';

    // TODO rename constants starting with F
    const MSG_TYPE              = '0';
    const ACC_NO                = '2';
    const REQ_TYPE              = '3';
    const AMOUNT                = '4';
    const COUNTER               = '11';
    const F22                   = '22';
    const F24                   = '24';
    const F25                   = '25';
    const F36                   = '36';
    const TERMINAL_ID           = '41';
    const F42                   = '42';
    const PID_BLOCK             = '60';
    const TRANS_TYPE            = '125';
    const FP_INFO               = '126';
    const EXTRA_BLOCK           = '127';

    // For refund request's data which gets encrypted
    const REFUND_DATA_ACCOUNT_PROVIDER    = 'account-provider';
    const REFUND_DATA_MOBILE              = 'mobile';
    const REFUND_DATA_PAYER_VA            = 'payer-va';
    const REFUND_DATA_AMOUNT              = 'amount';
    const REFUND_DATA_NOTE                = 'note';
    const REFUND_DATA_DEVICE_ID           = 'device-id';
    const REFUND_DATA_SEQ_NO              = 'seq-no';
    const REFUND_DATA_CHANNEL_CODE        = 'channel-code';
    const REFUND_DATA_PROFILE_ID          = 'profile-id';
    const REFUND_DATA_ACCOUNT_TYPE        = 'account-type';
    const REFUND_DATA_IFSC                = 'ifsc';
    const REFUND_DATA_ACCOUNT_NUMBER      = 'account-number';
    const REFUND_DATA_MPIN                = 'mpin';
    const REFUND_DATA_PRE_APPROVED        = 'pre-approved';
    const REFUND_DATA_USE_DEFAULT_ACC     = 'use-default-acc';
    const REFUND_DATA_DEFAULT_DEBIT       = 'default-debit';
    const REFUND_DATA_DEFAULT_CREDIT      = 'default-credit';
    const REFUND_DATA_GLOBAL_ADDRESS_TYPE = 'global-address-type';
    const REFUND_DATA_PAYEE_AADHAR        = 'payee-aadhar';
    const REFUND_DATA_PAYEE_IIN           = 'payee-iin';
    const REFUND_DATA_PAYEE_NAME          = 'payee-name';
    const REFUND_DATA_MCC                 = 'mcc';
    const REFUND_DATA_MERCHANT_TYPE       = 'merchant-type';

    const REFUND_REQUEST_REQUESTID            = 'requestId';
    const REFUND_REQUEST_SERVICE              = 'service';
    const REFUND_REQUEST_ENCRYPTEDKEY         = 'encryptedKey';
    const REFUND_REQUEST_OAEPHASHINGALGORITHM = 'oaepHashingAlgorithm';
    const REFUND_REQUEST_IV                   = 'iv';
    const REFUND_REQUEST_ENCRYPTEDDATA        = 'encryptedData';
    const REFUND_REQUEST_CLIENTINFO           = 'clientInfo';
    const REFUND_REQUEST_OPTIONALPARAM        = 'optionalParam';
    const REFUND_REQUEST_API_KEY              = 'apikey';
}
