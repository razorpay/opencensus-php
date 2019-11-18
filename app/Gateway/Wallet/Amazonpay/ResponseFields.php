<?php

namespace RZP\Gateway\Wallet\Amazonpay;

final class ResponseFields
{
    /**
     * Authorize response fields
     */
    const AMAZON_ORDER_ID                       = 'amazonOrderId';
    const SELLER_ORDER_ID                       = 'sellerOrderId';
    const REASON_CODE                           = 'reasonCode';
    const DESCRIPTION                           = 'description';
    const SIGNATURE                             = 'signature';
    const STATUS                                = 'status';
    const AMOUNT                                = 'orderTotalAmount';
    const CURRENCY_CODE                         = 'orderTotalCurrencyCode';
    const TRANSACTION_DATE                      = 'transactionDate';
    const CUSTOM_INFORMATION                    = 'customInformation';

    /**
     * Verify response fields
     */
    const ORDER_REFERENCE_RESULT                = 'ListOrderReferenceResult';
    const ORDER_REFERENCE_LIST                  = 'OrderReferenceList';
    const ORDER_REFERENCE                       = 'OrderReference';
    const ORDER_REFERENCE_STATUS                = 'OrderReferenceStatus';
    const AMAZON_REFERENCE_ID                   = 'AmazonOrderReferenceId';
    const UC_REASON_CODE                        = 'ReasonCode';
    const SOFT_DESCRIPTOR                       = 'SoftDescriptor';
    const REASON_DESCRIPTION                    = 'ReasonDescription';
    const RESPONSE_METADATA                     = 'ResponseMetadata';
    const REQUEST_ID                            = 'RequestId';
    const REQUEST_UC_ID                         = 'RequestID';
    const REFUND_TYPE                           = 'RefundType';
    const ERROR                                 = 'Error';
    const SELLER_ORDER_ATTRS                    = 'SellerOrderAttributes';//nsdkf kdf;
    const UC_SELLER_ORDER_ID                    = 'SellerOrderId';
    const ORDER_TOTAL                           = 'OrderTotal';
    const ORDER_AMOUNT                          = 'Amount';
    const CREATION_TIMESTAMP                    = 'CreationTimestamp';
    const LAST_UPDATE_TIMESTAMP                 = 'LastUpdateTimestamp';

    /**
     * Refund response fields
     */
    const REFUND_PAYMENT_RESULT_WRAPPER         = 'RefundPaymentResult.RefundDetails.RefundDetail';
    const REFUND_DETAILS                        = 'RefundDetails';
    const REFUND_DETAIL                         = 'RefundDetail';
    const REFUND_REF_ID                         = 'RefundReferenceId';
    const REFUND_STATUS                         = 'RefundStatus';
    const REFUND_STATE                          = 'State';

    /**
     * Amount refunded by the gateway
     */
    const FEE_REFUNDED                          = 'FeeRefunded';

    const REFUNDED_AMOUNT                       = 'Amount';
    const AMAZON_REFUND_ID                      = 'AmazonRefundId';

    /**
     * Amount requested for refund
     */
    const REFUND_AMOUNT                         = 'RefundAmount';

    /**
     * Refund Verify wrapper
     */
    const GET_REFUND_DETAILS_RESULT_WRAPPER     = 'GetRefundDetailsResult.RefundDetails';
}
