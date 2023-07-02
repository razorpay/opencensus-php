<?php

namespace RZP\Gateway\Upi\Mindgate;

class ResponseFields
{
    const AMOUNT                    = 'amount';
    const APPROVAL_NO               = 'approval_no';
    const CALLBACK_RESPONSE_KEY     = 'meRes';
    const CALLBACK_RESPONSE_PGMID   = 'pgMerchantId';
    const CUSTOMER_REFERENCE_ID     = 'customer_reference_id';
    const NPCI_UPI_TXN_ID           = 'npci_upi_txn_id';
    const PAYEE_VA                  = 'payee_va';
    const PAYER_VA                  = 'payer_va';
    const PAYER_NAME                = 'payer_name';
    const PAYMENT_ID                = 'payment_id';
    const REFERENCE_ID              = 'reference_id';
    const REFUND_ID                 = 'refund_id';
    const RESPCODE                  = 'respcode';
    const STATUS                    = 'status';
    const STATUS_DESCRIPTION        = 'status_description';
    const TXN_AUTH_DATE             = 'txn_auth_date';
    const UPI_TXN_ID                = 'upi_txn_id';
    const VPA_STATUS                = 'vpa_status';
    const IFSC_CODE                 = 'ifsc_code';
    const ACCOUNT_NUMBER            = 'account_number';
    const REFERENCE_1               = 'reference_1';
    const REFERENCE_2               = 'reference_2';
    const REFERENCE_3               = 'reference_3';
    const REFERENCE_4               = 'reference_4';
    const REFERENCE_5               = 'reference_5';
    const BANK_REFERENCE            = 'bank_reference';
    const BANK_NAME                 = 'bank_name';
    const PHONE_NUMBER              = 'phone_number';
    const NO_BANK_DETAIL            = 'NA';
    const REFERENCE_6               = 'reference_6';
    const REFERENCE_7               = 'reference_7';
    const REFERENCE_8               = 'reference_8';
    const REFERENCE_9               = 'reference_9';
    const PAYER_ACCOUNT_TYPE        = 'payer_account_type';

    /**
     * These are the expected field orders
     * for the response we get from the API
     *
     * @see https://drive.google.com/drive/u/0/folders/0B1MTSXtR53PfYldqNUIyLXlnSjA
     */
    const COLLECT       = [
        self::PAYMENT_ID,
        self::UPI_TXN_ID,
        self::AMOUNT,
        self::STATUS,
        self::STATUS_DESCRIPTION,
        self::PAYER_VA,
        self::PAYEE_VA,
    ];

    const VERIFY        = [
        self::UPI_TXN_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::TXN_AUTH_DATE,
        self::STATUS,
        self::STATUS_DESCRIPTION,
        self::RESPCODE,
        self::APPROVAL_NO,
        self::PAYER_VA,
        self::NPCI_UPI_TXN_ID,
        self::REFERENCE_ID,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::REFERENCE_3,
        self::REFERENCE_4,
        self::REFERENCE_5,
        self::BANK_REFERENCE,
    ];

    const REFUND        = [
        self::UPI_TXN_ID,
        self::REFUND_ID,
        self::AMOUNT,
        self::TXN_AUTH_DATE,
        self::STATUS,
        self::STATUS_DESCRIPTION,
        self::RESPCODE,
        self::APPROVAL_NO,
        self::PAYER_VA,
        self::NPCI_UPI_TXN_ID,
        self::REFERENCE_ID,
    ];

    const CALLBACK      = [
        self::UPI_TXN_ID,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::TXN_AUTH_DATE,
        self::STATUS,
        self::STATUS_DESCRIPTION,
        self::RESPCODE,
        self::APPROVAL_NO,
        self::PAYER_VA,
        self::NPCI_UPI_TXN_ID,
        self::REFERENCE_ID,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::REFERENCE_3,
        self::REFERENCE_4,
        self::REFERENCE_5,
        self::BANK_REFERENCE,
        self::REFERENCE_6,
        self::REFERENCE_7,
        self::PAYER_ACCOUNT_TYPE,
    ];

    const VALIDATE_VPA  = [
        self::REFERENCE_ID,
        self::PAYER_VA,
        self::PAYER_NAME,
        self::VPA_STATUS,
        self::STATUS_DESCRIPTION
    ];

    const BANK_DETAILS = [
        self::BANK_NAME,
        self::ACCOUNT_NUMBER,
        self::IFSC_CODE,
        self::PHONE_NUMBER,
    ];

    const PAYEE_VA_DETAILS = [
        self::PAYEE_VA,
    ];

    const INTENT_TPV = [
        self::PAYMENT_ID,
        self::STATUS,
        self::STATUS_DESCRIPTION,
        self::REFERENCE_1,
        self::REFERENCE_2,
        self::REFERENCE_3,
        self::REFERENCE_4,
        self::REFERENCE_5,
        self::REFERENCE_6,
        self::ACCOUNT_NUMBER,
        self::REFERENCE_7,
        self::REFERENCE_8,
        self::REFERENCE_9,
    ];

    //In gateway response Bank Name, Account Number, IFSC Code, Phone Number are send as string with ! separator
    //eg - PNB!10000000000!PNBI1111111!8966829290
    const BANK_REFERENCE_SEPARATOR  = '!';
}
