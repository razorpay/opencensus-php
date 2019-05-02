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
    const TOKEN                     = 'token';
    const UPI_REQUEST_ID            = 'upiRequestId';

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

    // --------------- RESPONSE FIELDS --------------- //
    const RESPONSE_CODE                 = 'responseCode';
    const RESPONSE_MESSAGE              = 'responseMessage';
    const GATEWAY_RESPONSE_CODE         = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE      = 'gatewayResponseMessage';

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

    // --------------------- VPA -------------------- //
    const CUSTOMER_VPA                  = 'customerVpa';
    const AVAILABLE                     = 'available';
    const IS_CUSTOMER_VPA_VALID         = 'isCustomerVpaValid';
    const CUSTOMER_NAME                 = 'customerName';

    // ------------------- TRANSACTION ------------- //
    const MERCHANT_REQUEST_ID           = 'merchantRequestId';
    const PAYEE_VPA                     = 'payeeVpa';
    const PAYER_VPA                     = 'payerVpa';
    const PAYER_NAME                    = 'payerName';
    const PAYEE_NAME                    = 'payeeName';
    const REMARKS                       = 'remarks';
    const PAY_TYPE                      = 'payType';
    const AMOUNT                        = 'amount';
    const MERCHANT_CATEGORY_CODE        = 'mcc';
    const TRANSACTION_TIME_STAMP        = 'transactionTimestamp';
    const GATEWAY_TRANSACTION_ID        = 'gatewayTransactionId';
    const GATEWAY_REFERENCE_ID          = 'gatewayReferenceId';
    const MERCHANT_PAYLOAD_SIGNATURE    = 'merchantPayloadSignature';
    const COLLECT_REQ_EXPIRY_MINS       = 'collectRequestExpiryMinutes';

    // ------------- OPTIONAL VALUES IN TRANSACTION FLOW ---------//
    const REF_URL                       = 'refUrl';
    const TRANSACTION_REFERENCE         = 'transactionReference';

    // ------------------- TRANSACTION TYPES ------------- //
    const P2P_PAY                       = 'P2P_PAY';
    const SCAN_PAY                      = 'SCAN_PAY';
    const INTENT_PAY                    = 'INTENT_PAY';
}
