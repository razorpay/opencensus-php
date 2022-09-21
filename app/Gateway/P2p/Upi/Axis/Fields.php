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
    const RID                       = 'rid';
    const RSH                       = 'rsh';
    const TOKEN                     = 'token';
    const UPI_REQUEST_ID            = 'upiRequestId';
    const UPI_RESPONSE_ID           = 'upiResponseId';

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
    const ACCOUNT                       = 'account';
    const ACCOUNTS                      = 'accounts';
    const VPA_SUGGESTIONS               = 'vpaSuggestions';
    const DEVICE_DATA                   = 'device_data';
    const GATEWAY_DATA                  = 'gateway_data';
    const SDK_DATA                      = 'sdk_data';
    const ERROR                         = 'error';
    const MERCHANT_ID                   = 'merchantId';
    const CURRENCY                      = 'currency';
    const MCC                           = 'mcc';
    const PAYEE_MCC                     = 'payeeMcc';
    const MERCHANT_CHANNEL_ID           = 'merchantChannelId';

    // --------------- RESPONSE FIELDS --------------- //
    const RESPONSE_CODE                 = 'responseCode';
    const RESPONSE_MESSAGE              = 'responseMessage';
    const GATEWAY_RESPONSE_CODE         = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE      = 'gatewayResponseMessage';
    const GATEWAY_RESPONSE_STATUS       = 'gatewayResponseStatus';

    // ------------------ BANK ACCOUNT --------------- //
    const BANK_CODE                     = 'bankCode';
    const BANK_NAME                     = 'bankName';
    const MASKED_ACCOUNT_NUMBER         = 'maskedAccountNumber';
    const MPIN_LENGTH                   = 'mpinLength';
    const MPIN_SET                      = 'mpinSet';
    const REFERENCE_ID                  = 'referenceId';
    const TYPE                          = 'type';
    const IFSC                          = 'ifsc';
    const NAME                          = 'name';
    const BRANCH_NAME                   = 'branchName';
    const BANK_ACCOUNT_UNIQUE_ID        = 'bankAccountUniqueId';
    const OTP_LENGTH                    = 'otpLength';
    const ATM_PIN_LENGTH                = 'atmPinLength';
    const ACCOUNT_REFERENCE_ID          = 'accountReferenceId';
    const CARD                          = 'card';
    const EXPIRY                        = 'expiry';
    const BALANCE                       = 'balance';

    const BANKS                         = 'banks';
    const CODE                          = 'code';
    const UPI_ENABLED                   = 'upiEnabled';

    // --------------------- VPA -------------------- //
    const VPA                           = 'vpa';
    const CUSTOMER_VPA                  = 'customerVpa';
    const CUSTOMER_PRIMARY_VPA          = 'customerPrimaryVpa';
    const AVAILABLE                     = 'available';
    const IS_CUSTOMER_VPA_VALID         = 'isCustomerVpaValid';
    const IS_DEFAULT                    = 'isDefault';
    const CUSTOMER_NAME                 = 'customerName';
    const SHOULD_BLOCK                  = 'shouldBlock';
    const SHOULD_SPAM                   = 'shouldSpam';
    const BLOCKED_VPAS                  = 'blockedVpas';
    const BLOCKED_AT                    = 'blockedAt';

    // ------------------- TRANSACTION ------------- //
    const MERCHANT_REQUEST_ID           = 'merchantRequestId';
    const MERCHANT_VPA                  = 'merchantVpa';
    const PAYEE_VPA                     = 'payeeVpa';
    const PAYER_VPA                     = 'payerVpa';
    const PAYER_NAME                    = 'payerName';
    const PAYEE_NAME                    = 'payeeName';
    const REMARKS                       = 'remarks';
    const PAY_TYPE                      = 'payType';
    const AMOUNT                        = 'amount';
    const TRANSACTION_TIME_STAMP        = 'transactionTimestamp';
    const GATEWAY_TRANSACTION_ID        = 'gatewayTransactionId';
    const GATEWAY_REFERENCE_ID          = 'gatewayReferenceId';
    const MERCHANT_PAYLOAD_SIGNATURE    = 'merchantPayloadSignature';
    const COLLECT_REQ_EXPIRY_MINS       = 'collectRequestExpiryMinutes';
    const CUSTOME_RESPONSE              = 'customResponse';
    const IS_VERIFIED_PAYEE             = 'isVerifiedPayee';
    const IS_MARKED_SPAM                = 'isMarkedSpam';
    const QUERY_COMMENT                 = 'queryComment';
    const QUERY_REFERENCE_ID            = 'queryReferenceId';
    const QUERY_CLOSING_TIMESTAMP       = 'queryClosingTimestamp';
    const QUERIES                       = 'queries';
    const LIMIT                         = 'limit';
    const OFFSET                        = 'offset';

    // ------------------- MANDATE -------------------- //
    const AMOUNT_RULE                   = 'amountRule';
    const BLOCK_FUND                    = 'blockFund';
    const INITIATED_BY                  = 'initiatedBy';
    const GATEWAY_MANDATE_ID            = 'gatewayMandateId';
    const MANDATE_CUSTOMER_ID           = 'mandateCustomerId';
    const MANDATE_NAME                  = 'mandateName';
    const MANDATE_TIMESTAMP             = 'mandateTimestamp';
    const MANDATE_TYPE                  = 'mandateType';
    const REQUEST_TYPE                  = 'requestType';
    const MANDATE_APPROVAL_TIMESTAMP    = 'mandateApprovalTimeStamp';
    const MANDATE_REQUEST_ID            = 'mandateRequestId';
    const ORG_MANDATE_ID                = 'orgMandateId';
    const PAYER_REVOCABLE               = 'payerRevocable';
    const RECURRENCE_PATTERN            = 'recurrencePattern';
    const RECURRENCE_RULE               = 'recurrenceRule';
    const RECURRENCE_VALUE              = 'recurrenceValue';
    const ROLE                          = 'role';
    const SHARE_TO_PAYEE                = 'shareToPayee';
    const TRANSACTION_TYPE              = 'transactionType';
    const SEQ_NUMBER                    = 'seqNumber';
    const NEXT_EXECUTION                = 'nextExecution';
    const UMN                           = 'umn';
    const VALIDITY_START                = 'validityStart';
    const VALIDITY_END                  = 'validityEnd';
    const PAUSE_START                   = 'pauseStart';
    const PAUSE_END                     = 'pauseEnd';
    const MANDATE_ID                    = 'mandate_id';

    // ------------- OPTIONAL VALUES IN TRANSACTION FLOW ---------//
    const REF_URL                       = 'refUrl';
    const TRANSACTION_REFERENCE         = 'transactionReference';

    // ------------------- TRANSACTION TYPES ------------- //
    const P2P_PAY                       = 'P2P_PAY';
    const SCAN_PAY                      = 'SCAN_PAY';
    const INTENT_PAY                    = 'INTENT_PAY';

    // ------------------- BANK ACCOUNT TYPES ------------- //
    const SAVINGS                       = 'SAVINGS';
    const CURRENT                       = 'CURRENT';
    const SOD                           = 'SOD';
    const UOD                           = 'UOD';

    // ------------------- CALLBACK FIELDS ------------- //
    const HEADERS                       = 'headers';
    const X_MERCHANT_PAYLOAD_SIGNATURE  = 'x-merchant-payload-signature';
    const PAYEE_MOBILE_NUMBER           = 'payeeMobileNumber';
    const PAYER_MOBILE_NUMBER           = 'payerMobileNumber';
}
