<?php

namespace RZP\Gateway\Wallet\Amazonpay;

final class RequestFields
{
    /**
     * The request fields are developed as per API contract
     * @see https://drive.google.com/open?id=1eYrSunmXQ3JFSQ5qm3pHYvaAxsq3LENT
     */

    // transaction request mandatory fields
    const TOTAL_AMOUNT        = 'orderTotalAmount';
    const CURRENCY_CODE       = 'orderTotalCurrencyCode';
    const ORDER_ID            = 'sellerOrderId';
    const START_TIME          = 'startTime';

    // transaction request optional fields are below
    const IS_SANDBOX          = 'isSandbox';

    /**
     * These parameters get added to the authorize request via the SDK
     */
    const AWS_ACCESS_KEY_ID   = 'AWSAccessKeyId';
    const SELLER_ID           = 'sellerId';
    const SIGNATURE           = 'Signature';

    /**
     * The timeout in seconds for a transaction
     * We are setting this to 300s or 5 minutes
     */
    const TXN_TIMEOUT         = 'transactionTimeout';

    const CUSTOM_INFO         = 'customInformation';
    const SELLER_NOTE         = 'sellerNote';
    const SELLER_STORE_NAME   = 'sellerStoreName';

    /**
     * Authorize POST parameters
     */
    const PAYLOAD             = 'payload';
    const KEY                 = 'key';
    const IV                  = 'iv';
    const REDIRECT_URL        = 'redirectUrl';

    /**
     * Below are the parameters sent across in the verify API
     */
    const PAYMENT_DOMAIN      = 'PaymentDomain';
    const QUERY_ID            = 'QueryId';
    const QUERY_ID_TYPE       = 'QueryIdType';
    const ACTION              = 'Action';
    const UC_SELLER_ID        = 'SellerId';
    const SIGNATURE_METHOD    = 'SignatureMethod';
    const SIGNATURE_VERSION   = 'SignatureVersion';
    const TIMESTAMP           = 'Timestamp';
    const VERSION             = 'Version';
    const VERIFY_START_TIME   = 'CreatedTimeRange.StartTime';
    const VERIFY_END_TIME     = 'CreatedTimeRange.EndTime';

    /**
     * Below are the parameters sent across in the refund API
     */
    const AMAZON_REFUND_ID    = 'AmazonRefundId';
    const AMAZON_TRAN_TYPE    = 'AmazonTransactionIdType';
    const AMAZON_TRAN_ID      = 'AmazonTransactionId';
    const REFUND_REF_ID       = 'RefundReferenceId';
    const REFUND_AMOUNT       = 'RefundAmount.Amount';
    const REFUND_CURRENCY     = 'RefundAmount.CurrencyCode';
}
