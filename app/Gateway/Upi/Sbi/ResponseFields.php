<?php

namespace RZP\Gateway\Upi\Sbi;

class ResponseFields
{
    const MESSAGE                    = 'msg';
    const RESPONSE                   = 'resp';
    const API_RESPONSE               = 'apiResp';

    /**
     * Razorpay Payment ID
     */
    const PSP_REFERENCE_NO           = 'pspRefNo';

    /**
     * Unique UPI Transaction Reference number - mapped to Gateway Payment ID
     */
    const UPI_TRANS_REFERENCE_NO     = 'upiTransRefNo';

    /**
     * Unique number assigned by NPCI - mapped to NPCI Reference ID
     */
    const NPCI_TRANSACTION_ID        = 'npciTransId';

    /**
     * RRN Number, which is unique in the UPI platform - mapped to Customer Reference ID
     */
    const CUSTOMER_REFERENCE_NO      = 'custRefNo';

    /**
     * Transaction approval number - core bank reference number
     * TODO: Is this used for anything? Do we need to save this?
     */
    const APPROVAL_NUMBER            = 'approvalNumber';

    const RESPONSE_CODE              = 'responseCode';
    const AMOUNT                     = 'amount';
    const TRANSACTION_AUTH_DATE      = 'txnAuthDate';
    const STATUS                     = 'status';
    const STATUS_DESCRIPTION         = 'statusDesc';
    const ADDITIONAL_INFO            = 'addInfo';
    const PAYER_VPA                  = 'payerVPA';
    const PAYEE_VPA                  = 'payeeVPA';
    const PG_MERCHANT_ID             = 'pgMerchantId';

    /**
     * Refund response specific constants below
     */

    const REQUEST_INFO               = 'requestInfo';
    const REFUND_STATUS              = 'refund_status';
    const REFUND_STATUS_DESC         = 'refund_status_desc';
    const REFUND_DATE                = 'refund_date';
    const RESPONSE_CODE_LC           = 'response_code';

    /**
     * Transaction approval number of the refund request
     */
    const APPROVAL_NUMBER_LC         = 'approval_number';

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

    /**
     * Npci Transaction ID of the original upi payment, for which we are raising a refund request
     */
    const NPCI_TRANSACTION_ID_LC     = 'npci_txn_id';

    const TRANSACTION_REMARKS        = 'txn_remarks';
    const CURRENCY_CODE              = 'currency_code';
    const PAYMENT_TYPE               = 'payment_type';
    const PAYEE_INFO                 = 'payee_info';

    /**
     * Payer info fields below
     */
    const PAYER_INFO                 = 'payer_info';
    const VIRTUAL_ADDRESS            = 'virtualAddress';
    const NAME                       = 'name';
    const ACCOUNT_NUMBER             = 'accountNo';
    const IFSC_CODE                  = 'ifsc';
}