<?php

namespace RZP\Gateway\Upi\Base\Mock;

class ReconFileFields
{
    /**
     * @see https://docs.google.com/spreadsheets/d/187pxQ4jXwKAOh612mRF3ss0amoXWL_MIKJJ78KpPIj0/edit#gid=2032975779
     */

    const PG_MERCHANT_ID                  = 'PG Merchant ID';
    const LEGAL_NAME                      = 'Legal Name';
    const STORE_NAME                      = 'Store Name';
    const MCC                             = 'MCC';
    const ORDER_NO                        = 'Order No';
    const TRANS_REF_NUM                   = 'Trans Ref No.';
    const CUSTOMER_REF_NUM                = 'Customer Ref No.';
    const NPCI_RESPONSE_CODE              = 'NPCI Response Code';
    const TRANS_TYPE                      = 'Trans Type';
    const DR_CR                           = 'DR/CR';
    const TRANSACTION_STATUS              = 'Transaction Status';
    const TRANSACTION_REMARKS             = 'Transaction Remarks';
    const TRANSCTION_DATE                 = 'Transaction Date';
    const TRANSACTION_AMOUNT              = 'Transaction Amount';
    const PAYER_AC_NO                     = 'Payer A/c No.';
    const PAYER_VIRTUAL_ADDRESS           = 'Payer Virtual Address';
    const PAYER_AC_NAME                   = 'Payer A/C Name';
    const PAYER_IFSC_CODE                 = 'Payer IFSC Code';
    const PAYEE_AC_NO                     = 'Payee A/C No';
    const PAYEE_VIRTUAL_ADDRESS           = 'Payee Virtual Address';
    const PAYEE_AC_NAME                   = 'Payee A/C Name';
    const PAYEE_IFSC_CODE                 = 'Payee IFSC Code';
    const PAY_TYPE                        = 'Pay Type';
    const DEVICE_TYPE                     = 'Device Type';

    /**
     * Ignoring all other columns
     */

    protected static $headers = [
        self::PG_MERCHANT_ID,
        self::LEGAL_NAME,
        self::STORE_NAME,
        self::MCC,
        self::ORDER_NO,
        self::TRANS_REF_NUM,
        self::CUSTOMER_REF_NUM,
        self::NPCI_RESPONSE_CODE,
        self::TRANS_TYPE,
        self::DR_CR,
        self::TRANSACTION_STATUS,
        self::TRANSACTION_REMARKS,
        self::TRANSCTION_DATE,
        self::TRANSACTION_AMOUNT,
        self::PAYER_AC_NO,
        self::PAYER_VIRTUAL_ADDRESS,
        self::PAYER_AC_NAME,
        self::PAYER_IFSC_CODE,
        self::PAYEE_AC_NO,
        self::PAYEE_VIRTUAL_ADDRESS,
        self::PAYEE_AC_NAME,
        self::PAYEE_IFSC_CODE,
        self::PAY_TYPE,
        self::DEVICE_TYPE,
    ];

    public static function getHeaders()
    {
        return self::$headers;
    }
}