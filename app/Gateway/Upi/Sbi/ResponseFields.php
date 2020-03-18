<?php

namespace RZP\Gateway\Upi\Sbi;

class ResponseFields
{
    /**
     * @see https://drive.google.com/a/razorpay.com/file/d/0B1kf6HOmx7JBUVJ3WUZmdGpnOXNWb21uZi1VWkpJdklVLUZJ/view?usp=sharing
     */

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

    const REQUEST_INFO               = 'requestInfo';

    /**
     * Transaction approval number - core bank reference number
     */
    const APPROVAL_NUMBER            = 'approvalNumber';

    const RESPONSE_CODE              = 'responseCode';
    const AMOUNT                     = 'amount';
    const TRANSACTION_AUTH_DATE      = 'txnAuthDate';
    const STATUS                     = 'status';
    const STATUS_DESCRIPTION         = 'statusDesc';
    const ADDITIONAL_INFO            = 'addInfo';
    const ADDITIONAL_INFO2           = 'addInfo2';
    const PAYER_VPA                  = 'payerVPA';
    const PAYEE_VPA                  = 'payeeVPA';
    const PG_MERCHANT_ID             = 'pgMerchantId';
    const PAYEE_TYPE                 = 'payeeType';
    const VIRTUAL_ADDRESS            = 'virtualAddress';
    const NAME                       = 'name';
}
