<?php

namespace RZP\Gateway\FirstData\Mock;

class Constants
{
    const DOMESTIC_NOT_ENROLLED_CARD                = '4016130400915681';
    const INTERNATIONAL_CARD                        = '4160210902353047';
    const DOMESTIC_CARD_INSUFFICIENT_BALCANCE       = '4893771001500972';
    const S2S_STORE_ID                              = '3344000400';
    const XML_IPGAPI_BRAND                          = 'ipgapi:Brand';
    const XML_IPGAPI_TRANSACTION_ID                 = 'ipgapi:IpgTransactionId';
    const XML_IPGAPI_TRANSACTION_RESULT             = 'ipgapi:TransactionResult';
    const XML_IPGAPI_APPROVAL_CODE                  = 'ipgapi:ApprovalCode';
    const XML_IPGAPI_COUNTRY                        = 'ipgapi:Country';
    const XML_IPGAPI_COMMERCIAL_SERVICE_PROVIDER    = 'ipgapi:CommercialServiceProvider';
    const XML_IPGAPI_ORDER_ID                       = 'ipgapi:OrderId';
    const XML_IPGAPI_PAYMENT_TYPE                   = 'ipgapi:PaymentType';
    const XML_IPGAPI_TDATE                          = 'ipgapi:TDate';
    const XML_IPGAPI_TDATE_FORMATTED                = 'ipgapi:TDateFormatted';
    const XML_IPGAPI_TRANSACTION_TIME               = 'ipgapi:TransactionTime';
    const XML_IPGAPI_SECURE_3D_RESPONSE             = 'ipgapi:Secure3DResponse';
    const XML_AVS_RESPONSE                          = 'ipgapi:AVSResponse';
    const XML_PROCESSOR_RESPONSE_CODE               = 'ipgapi:ProcessorResponseCode';
    const XML_IPGAPI_TERMINAL_ID                    = 'ipgapi:TerminalID';
    const XML_TRANSACTION_RESULT                    = 'ipgapi:TransactionResult';
    const XML_PROCESSOR_APPROVAL_CODE               = 'ipgapi:ProcessorApprovalCode';
    const XML_PROCESSOR_RESPONSE_MESSAGE            = 'ipgapi:ProcessorResponseMessage';
    const XML_ERROR_MESSAGE                         = 'ipgapi:ErrorMessage';
    const XML_VERIFICATION_REDIRECT_RESPONSE        = 'v1:VerificationRedirectResponse';
    const XML_V1_ACS                                = 'v1:AcsURL';
    const XML_V1_PA_REQ                             = 'v1:PaReq';
    const XML_V1_MD                                 = 'v1:MD';
    const XML_V1_TERM_URL                           = 'v1:TermUrl';
    const XML_V1_SECURE_3D_VERIFICATION_RESPONSE    = 'v1:Secure3DVerificationResponse';
    const XML_V1_RESPONSE_CODE_3D_SECURE            = 'v1:ResponseCode3dSecure';
    const IPGAPI_ORDER_RESPONSE                     = 'IPGApiOrderResponse';
    const IPGAPI_TERMINAL_ID                        = 'ipgapiTerminalID';
    const IPGAPI_AMOUNT                             = 'ipgapiAmount';
    const TRANSACTION                               = 'Transaction';
    const TRANSACTION_DETAILS                       = 'TransactionDetails';
    const TRANSACTION_ID                            = 'IpgTransactionId';
}
