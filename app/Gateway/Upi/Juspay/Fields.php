<?php

namespace RZP\Gateway\Upi\Juspay;

class Fields
{
    const MERCHANT_REQUEST_ID           = 'merchantRequestId'; // payment.id
    const CUSTOM_RESPONSE               = 'customResponse';
    const EXPIRY                        = 'expiry';
    const GATEWAY_REFERENCE_ID          = 'gatewayReferenceId';
    const GATEWAY_RESPONSE_CODE         = 'gatewayResponseCode';
    const GATEWAY_RESPONSE_MESSAGE      = 'gatewayResponseMessage';
    const GATEWAY_TRANSACTION_ID        = 'gatewayTransactionId';
    const MERCHANT_CHANNEL_ID           = 'merchantChannelId';
    const MERCHANT_ID                   = 'merchantId';
    const PAYEE_VPA                     = 'payeeVpa';
    const PAYER_NAME                    = 'payerName';
    const PAYER_VPA                     = 'payerVpa';
    const TRANSACTION_TIMESTAMP         = 'transactionTimestamp';
    const TYPE                          = 'type';
    const UDF_PARAMETERS                = 'udfParameters';
    const AMOUNT                        = 'amount'; // payment.amount
    const PAYEE_MCC                     = 'payeeMcc';
    const REF_URL                       = 'refUrl';

    // Callback types
    const MERCHANT_CREDITED_VIA_PAY       = 'MERCHANT_CREDITED_VIA_PAY';
    const MERCHANT_CREDITED_VIA_COLLECT   = 'MERCHANT_CREDITED_VIA_COLLECT';

    // Udf Fields
    const REF_ID           = 'ref_id';

}
