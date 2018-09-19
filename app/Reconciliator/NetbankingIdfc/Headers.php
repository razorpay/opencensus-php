<?php

namespace RZP\Reconciliator\NetbankingIdfc;

class Headers
{
    //Reconciliation File Header
    const TXN_INITIATE_DATE_TIME    = 'TXN INITIATE DATE TIME';
    const RIB_TXN_ID                = 'RIB TXN ID';
    const MERCHANT_ID               = 'MERCHANT ID';
    const MERCHANT_NAME             = 'MERCHANT NAME';
    const CUST_ACC_NO               = 'CUST ACC NUMBER';
    const ECOMM_ACC_NO              = 'E-COMM ACCT NUMBER - BGL Account';
    const TXN_AMT                   = 'TXN AMOUNT';
    const SERVICE_CHARGE            = 'Service Charge';
    const SERVICE_TAX               = 'Service Tax';
    const COMMISSION                = 'Commission';
    const PAYMENT_TYPE              = 'PAYMENT TYPE';
    const TXN_COMPLETION_DATE_TIME  = 'TXN COMPLETION DATE TIME';
    const RIB_TXN_STATUS            = 'RIB TXN STATUS';
    const BANK_REFERENCE_NUMBER     = 'BANK REFERENCE NUMBER';
    const AGGREGATOR_REF_NO         = 'AGGREGATOR/MERCHANT TXN REFERENCE NO';
    const PAYMENT_STATUS            = 'E-COMM PAYMENT STATUS';
    const ERROR_CODE                = 'ERROR CODE';
    const ERROR_MSG                 = 'ERROR REASON';

    const COLUMN_HEADERS = [
        self::TXN_INITIATE_DATE_TIME,
        self::RIB_TXN_ID,
        self::MERCHANT_ID,
        self::MERCHANT_NAME,
        self::CUST_ACC_NO,
        self::ECOMM_ACC_NO,
        self::TXN_AMT,
        self::SERVICE_CHARGE,
        self::SERVICE_TAX,
        self::COMMISSION,
        self::PAYMENT_TYPE,
        self::TXN_COMPLETION_DATE_TIME,
        self::RIB_TXN_STATUS,
        self::BANK_REFERENCE_NUMBER,
        self::AGGREGATOR_REF_NO,
        self::PAYMENT_STATUS,
        self::ERROR_CODE,
        self::ERROR_MSG,
    ];
}
