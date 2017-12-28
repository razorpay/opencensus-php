<?php

namespace RZP\Gateway\Aeps\Icici;

class ResponseConstants
{

    // Auth response fields
    const AUTH_MESSAGE_TYPE_INDICATOR                = '0';
    const AUTH_PAN                                   = '2';
    const AUTH_PROC_CODE                             = '3';
    const AUTH_AMOUNT                                = '4';
    const AUTH_SYSTEM_TRACE_AUDIT_NO                 = '11';
    const AUTH_TIME                                  = '12';
    const AUTH_DATE                                  = '13';
    const AUTH_NETWORK_INTERNATIONAL_IDENTIFIER      = '24';
    const AUTH_POINT_OF_SERVICE_CONDITION_CODE       = '25';
    const AUTH_ADDITIONAL_DATA                       = '36';
    const AUTH_RRN                                   = '37';
    const AUTH_AUTHORIZATION_IDENTIFICATION_RESPONSE = '38';
    const AUTH_RESPONSE_CODE                         = '39';
    const AUTH_CARD_ACCEPTOR_TERMINAL_IDENTIFICATION = '41';
    const AUTH_CARD_ACCEPTOR_NAME                    = '43';
    const AUTH_ADDITIONAL_AMOUNTS                    = '54';
    const AUTH_AUTHENTICATION_CODE                   = '62';
    const AUTH_BENEF_ACCOUNT_NUMBER                  = '103';
    const AUTH_REMITTER                              = '120';

    // Refund(UPI) response codes
    const REFUND_SUCCESS       = 'success';
    const REFUND_RESPONSE      = 'response';
    const REFUND_MESSAGE       = 'message';
    const REFUND_BANKRRN       = 'BankRRN';
    const REFUND_UPITRANLOGID  = 'UpiTranlogId';
    const REFUND_USERPROFILE   = 'UserProfile';
    const REFUND_SEQNO         = 'SeqNo';
    const REFUND_MOBILEAPPDATA = 'MobileAppData';

    const REFUND_RESPONSE_REQUESTID            = 'requestId';
    const REFUND_RESPONSE_SERVICE              = 'service';
    const REFUND_RESPONSE_ENCRYPTEDKEY         = 'encryptedKey';
    const REFUND_RESPONSE_OAEPHASHINGALGORITHM = 'oaepHashingAlgorithm';
    const REFUND_RESPONSE_IV                   = 'iv';
    const REFUND_RESPONSE_ENCRYPTEDDATA        = 'encryptedData';
    const REFUND_RESPONSE_CLIENTINFO           = 'clientInfo';
    const REFUND_RESPONSE_OPTIONALPARAM        = 'optionalParam';
}
