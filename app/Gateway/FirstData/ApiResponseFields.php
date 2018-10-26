<?php

namespace RZP\Gateway\FirstData;

class ApiResponseFields
{

    const APPROVAL_CODE                         = 'ApprovalCode';
    const AVS_RESPONSE                          = 'AVSResponse';
    const BRAND                                 = 'Brand';
    const BUILDTIME                             = 'BuildTime';
    const COMMERCIAL_SERVICE_PROVIDER           = 'CommercialServiceProvider';
    const COUNTRY                               = 'Country';
    const IPG_TRANSACTION_ID                    = 'IpgTransactionId';
    const ORDER_ID                              = 'OrderId';
    const PAYMENT_TYPE                          = 'PaymentType';
    const PROCESSOR_APPROVAL_CODE               = 'ProcessorApprovalCode';
    const PROCESSOR_RESPONSE_CODE               = 'ProcessorResponseCode';
    const PROCESSOR_RESPONSE_MESSAGE            = 'ProcessorResponseMessage';
    const REFERENCED_TDATE                      = 'ReferencedTDate';
    const TDATE                                 = 'TDate';
    const TDATE_FORMATTED                       = 'TDateFormatted';
    const TERMINAL_ID                           = 'TerminalID';
    const TRANSACTION_RESULT                    = 'TransactionResult';
    const TRANSACTION_TIME                      = 'TransactionTime';
    const VERSION                               = 'Version';
    const TRANSACTION_STATE                     = 'TransactionState';
    const TRANSACTION_VALUES                    = 'TransactionValues';
    const ACSURL                                = 'AcsURL';
    const MD                                    = 'MD';
    const PA_RES                                = 'PaRes';
    const VERIFICATION_REDIRECT_RESPONSE        = 'VerificationRedirectResponse';
    const SECURE_3D_RESPONSE                    = 'Secure3DResponse';
    const SECURE_3D_VERIFICATION_RESPONSE       = 'Secure3DVerificationResponse';
    const SOAP_ENV_BODY                         = 'SOAP-ENVBody';
    const SOAP_ENV_FAULT                        = 'SOAP-ENVFault';
    const DETAIL                                = 'detail';
    const RESPONSE_CODE_3D_SECURE               = 'ResponseCode3dSecure';
    const TERM_URL                              = 'TermUrl';
    const PA_REQ                                = 'PaReq';
    const ERROR_MESSAGE                         = 'ErrorMessage';
    const TRANSACTION_ID                        = 'IpgTransactionId';
    const V1                                    = 'v1';
    const V1_SECURE_3D_VERIFICATION_RESPONSE    = 'v1VerificationRedirectResponse';
    const V1_VERIFICATION_REDIRECT_RESPONSE     = 'v1Secure3DVerificationResponse';
    const V1_ACS_URL                            = 'v1AcsURL';
    const V1_MD                                 = 'v1MD';
    const V1_PA_REQ                             = 'v1PaReq';

    const IPGAPI                                = 'ipgapi';
    const IPGAPI_TRANSACTION_ID                 = 'ipgapiIpgTransactionId';
    const IPGAPI_TRANSACTION_RESULT             = 'ipgapiTransactionResult';
    const IPGAPI_APPROVAL_CODE                  = 'ipgapiApprovalCode';
    const IPGAPI_BRAND                          = 'ipgapiBrand';
    const IPGAPI_COUNTRY                        = 'ipgapiCountry';
    const IPGAPI_COMMERCIAL_SERVICE_PROVIDER    = 'ipgapiCommercialServiceProvider';
    const IPGAPI_ORDER_ID                       = 'ipgapiOrderId';
    const IPGAPI_IPG_TRANSACTION_ID             = 'ipgapiIpgTransactionId';
    const IPGAPI_PAYMENT_TYPE                   = 'ipgapiPaymentType';
    const IPGAPI_TDATE                          = 'ipgapiTDate';
    const IPGAPI_TDATE_FORMATTED                = 'ipgapiTDateFormatted';
    const IPGAPI_TRANSACTION_TIME               = 'ipgapiTransactionTime';
    const IPGAPI_SECURE_3D_RESPONSE             = 'ipgapiSecure3DResponse';
    const IPGAPI_TERMINAL_ID                    = 'ipgapiTerminalID';
    const IPGAPI_AMOUNT                         = 'ipgapiAmount';
    const IPGAPI_ORDER_RESPONSE                 = 'ipgapiIPGApiOrderResponse';
}
