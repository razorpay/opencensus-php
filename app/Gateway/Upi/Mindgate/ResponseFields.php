<?php

namespace RZP\Gateway\Upi\Mindgate;

class ResponseFields
{
    const AMOUNT                = 'amount';
    const APPROVAL_NO           = 'approval_no';
    const CALLBACK_RESPONSE_KEY = 'meRes';
    const CUSTOMER_REFERENCE_ID = 'customer_reference_id';
    const NPCI_UPI_TXN_ID       = 'npci_upi_txn_id';
    const PAYEE_VA              = 'payee_va';
    const PAYER_VA              = 'payer_va';
    const PAYER_NAME            = 'payer_name';
    const PAYMENT_ID            = 'payment_id';
    const REFERENCE_ID          = 'reference_id';
    const REFUND_ID             = 'refund_id';
    const RESPCODE              = 'respcode';
    const STATUS                = 'status';
    const STATUS_DESCRIPTION    = 'status_description';
    const TXN_AUTH_DATE         = 'txn_auth_date';
    const UPI_TXN_ID            = 'upi_txn_id';
    const VPA_STATUS            = 'vpa_status';

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
        self::REFERENCE_ID
    ];

    const VALIDATE_VPA  = [
        self::REFERENCE_ID,
        self::PAYER_VA,
        self::PAYER_NAME,
        self::VPA_STATUS,
        self::STATUS_DESCRIPTION
    ];
}
