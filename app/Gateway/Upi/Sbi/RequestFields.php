<?php

namespace RZP\Gateway\Upi\Sbi;

class RequestFields
{
    const PG_MERCHANT_ID             = 'pgMerchantId';

    /**
     * Razorpay Payment ID
     */
    const PSP_REFERENCE_NO           = 'pspRefNo';

    /**
     * RRN Number, which is unique in the UPI platform - mapped to Customer Reference ID
     */
    const CUSTOMER_REFERENCE_NO      = 'custRefNo';

    const TRANSACTION_NOTE           = 'transactionNote';
    const REQUEST_INFO               = 'requestInfo';
    const PAYER_TYPE                 = 'payerType';
    const VIRTUAL_ADDRESS            = 'virtualAddress';
    const EXPIRY_TIME                = 'expiryTime';
    const AMOUNT                     = 'amount';
    const ADDITIONAL_INFO            = 'addInfo';
    const ADDITIONAL_INFO1           = 'addInfo1';
    const ADDITIONAL_INFO9           = 'addInfo9';
    const ADDITIONAL_INFO10          = 'addInfo10';
    const REQUEST_MESSAGE            = 'requestMsg';

    /**
     * Refund specific request fields below
     */

    const REFUND_TRANSACTION_DETAIL  = 'refund_trn_detail';

    /**
     * Order number of the refund request - mapped to refund id
     */
    const ORDER_NUMBER               = 'order_number';

    /**
     * Order number of the original payment - mapped to payment id
     */
    const ORG_ORDER_NUMBER           = 'org_order_number';

    /**
     * Original upi transaction reference number - mapped to gateway payment id in the upi entity
     */
    const ORG_TRANSACTION_REF_NUMBER = 'org_trn_ref_number';

    /**
     * Original customer reference number saved in the upi entity
     */
    const ORG_CUSTOMER_REF_NUMBER    = 'org_cust_ref_number';

    const TRANSACTION_REMARKS        = 'txn_remarks';

    /**
     * Always INR
     */
    const CURRENCY_CODE              = 'currency_code';

    /**
     * Always P2P
     */
    const PAYMENT_TYPE               = 'payment_type';

    /**
     * Used in refund API, so this field is always Refund
     */
    const TRANSACTION_TYPE           = 'txn_type';
}