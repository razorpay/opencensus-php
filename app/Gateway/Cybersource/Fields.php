<?php

namespace RZP\Gateway\Cybersource;

class Fields
{
    const DECISION                      = 'decision';

    const REASON_CODE                   = 'reasonCode';

    const REQUEST_ID                    = 'requestID';

    const PAYMENT_REQUEST_ID            = 'PaymentRequestID';

    const REQUEST_TOKEN                 = 'requestToken';

    const MERCHANT_ID                   = 'merchantID';

    const TYPE                          = 'type';

    const SUBTYPE                       = 'subtype';

    const VERSION_NUMBER                = 'versionNumber';

    const MERCHANT_REFERENCE_NUMBER     = 'merchantReferenceNumber';

    const MERCHANT_REFERENCE_CODE       = 'merchantReferenceCode';

    const TARGET_DATE                   = 'targetDate';

    const PAYMENT_DATA                  = 'PaymentData';

    const PAYER_AUTHENTICATION_INFO     = 'PayerAuthenticationInfo';

    const XID                           = 'xid';

    const VERES_ENROLLED                = 'veresEnrolled';

    const COMMERCE_INDICATOR            = 'commerceIndicator';

    const ECI                           = 'eci';

    const ECI_RAW                       = 'eciRaw';

    const CAVV                          = 'cavv';

    const CAVV_ALGORITHM                = 'cavvAlgorithm';

    const UCAF                          = 'ucaf';

    const COLLECTION_INDICATOR          = 'collectionIndicator';

    const UCAF_COLLECTION_INDICATOR     = 'ucafCollectionIndicator';

    const UCAF_AUTHENTICATION_DATA      = 'ucafAuthenticationData';

    const AUTHENTICATION_DATA           = 'authenticationData';

    const RECEIPT_NUMBER                = 'receiptNumber';

    const AUTHORIZATION_CODE            = 'authorizationCode';

    const AVS_CODE                      = 'avsCode';

    const AVS_CODE_RAW                  = 'avsCodeRaw';

    const AVS_RESULT                    = 'AVSResult';

    const CARD_CATEGORY                 = 'cardCategory';

    const CARD_GROUP                    = 'cardGroup';

    const CV_CODE                       = 'cvCode';

    const CV_CODE_RAW                   = 'cvCodeRaw';

    const CAVV_RESPONSE_CODE            = 'cavvResponseCode';

    const CAVV_RESPONSE_CODE_RAW        = 'cavvResponseCodeRaw';

    const CV_RESULT                     = 'CVResult';

    const MERCHANT_ADVICE_CODE          = 'merchantAdviceCode';

    const MERCHANT_ADVICE_CODE_RAW      = 'merchantAdviceCodeRaw';

    const GATEWAY_TRANSACTION_ID        = 'gatewayTransactionID';

    const PAYMENT_NETWORK_TXN_ID        = 'paymentNetworkTransactionID';

    const AUTHENTICATION_RESULT         = 'authenticationResult';

    const AUTHENTICATION_STATUS_MESSAGE = 'authenticationStatusMessage';

    const PROCESSOR_RESPONSE            = 'processorResponse';

    const CC_AUTH_SERVICE               = 'ccAuthService';

    const CC_AUTH_REPLY                 = 'ccAuthReply';

    const PA_ENROLL_SERVICE             = 'payerAuthEnrollService';

    const CC_CREDIT_SERVICE             = 'ccCreditService';

    const CC_CREDIT_REPLY               = 'ccCreditReply';

    const CC_CAPTURE_SERVICE            = 'ccCaptureService';

    const CC_CAPTURE_REPLY              = 'ccCaptureReply';

    const CC_AUTH_REVERSAL_SERVICE      = 'ccAuthReversalService';

    const CC_AUTH_REVERSAL_REPLY        = 'ccAuthReversalReply';

    const PA_ENROLL_REPLY               = 'payerAuthEnrollReply';

    const PA_VALIDATE_SERVICE           = 'payerAuthValidateService';

    const PA_VALIDATE_REPLY             = 'payerAuthValidateReply';

    const SIGNED_PA_RES                 = 'signedPARes';

    const CAPTURE_REQUEST_ID            = 'captureRequestID';

    const AUTH_REQUEST_ID               = 'authRequestID';

    const PA_REQ                        = 'paReq';

    const PA_RES                        = 'PaRes';

    const PARES_STATUS                  = 'paresStatus';

    const ACS_URL                       = 'acsURL';

    const FIRST_NAME                    = 'firstName';

    const LAST_NAME                     = 'lastName';

    const STREET                        = 'street1';

    const CITY                          = 'city';

    const STATE                         = 'state';

    const POSTAL_CODE                   = 'postalCode';

    const COUNTRY                       = 'country';

    const EMAIL                         = 'email';

    const RUN                           = 'run';

    const CARD                          = 'card';

    const BILL_TO                       = 'billTo';

    const INVOICE_HEADER                = 'invoiceHeader';

    const MERCHANT_DESCRIPTOR           = 'merchantDescriptor';

    const BUSINESS_RULES                = 'businessRules';

    const IGNORE_AVS_RESULT             = 'ignoreAVSResult';

    const ACCOUNT_NUMBER                = 'accountNumber';

    const EXPIRATION_MONTH              = 'expirationMonth';

    const EXPIRATION_YEAR               = 'expirationYear';

    const CARD_TYPE                     = 'cardType';

    const CVN                           = 'cvNumber';

    const PURCHASE_TOTALS               = 'purchaseTotals';

    const CURRENCY                      = 'currency';

    const GRAND_TOTAL_AMOUNT            = 'grandTotalAmount';

    const AMOUNT                        = 'amount';

    const RECONCILIATION_ID             = 'reconciliationID';

    const REFUND_DATETIME               = 'refundDateTime';

    const AUTHORIZED_DATETIME           = 'authorizedDateTime';

    const REQUEST_DATETIME              = 'requestDateTime';

    const AUTHENTICATION_PATH           = 'authenticationPath';

    const PROOF_XML                     = 'proofXml';

    const PROXY_PAN                     = 'proxyPan';

    const MD                            = 'MD';

    const TERM_URL                      = 'TermUrl';

    const MERCHANT_DEFINED_DATA         = 'merchantDefinedData';

    const MDD_FIELD                     = 'mddField';

    // Verify Fields
    const R_FLAG                        = 'RFlag';

    const R_CODE                        = 'RCode';

    const R_MSG                         = 'RMsg';

    const APPLICATION_REPLIES           = 'ApplicationReplies';

    const APPLICATION_REPLY             = 'ApplicationReply';

    const REQUESTS                      = 'Requests';

    const REQUEST                       = 'Request';

    const NAME                          = 'Name';

    const ATTRIBUTES                    = '@attributes';
}
