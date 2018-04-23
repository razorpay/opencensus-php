<?php

namespace RZP\Models\FundTransfer\Kotak;

class Headings
{
    // Request-file heading names
    const CLIENT_CODE               = 'Client_Code';
    const PRODUCT_CODE              = 'Product_Code';
    const PAYMENT_TYPE              = 'Payment_Type';
    const PAYMENT_REF_NO            = 'Payment_Ref_No.';
    const PAYMENT_DATE              = 'Payment_Date';
    const INSTRUMENT_DATE           = 'Instrument Date';
    const DR_AC_NO                  = 'Dr_Ac_No';
    const AMOUNT                    = 'Amount';
    const BANK_CODE_INDICATOR       = 'Bank_Code_Indicator';
    const BENEFICIARY_CODE          = 'Beneficiary_Code';
    const BENEFICIARY_NAME          = 'Beneficiary_Name';
    const BENEFICIARY_BANK          = 'Beneficiary_Bank';
    const IFSC_CODE                 = 'IFSC Code';
    const BENEFICIARY_ACC_NO        = 'Beneficiary_Acc_No';
    const LOCATION                  = 'Location';
    const PRINT_LOCATION            = 'Print_Location';
    const INSTRUMENT_NUMBER         = 'Instrument_Number';
    const BENEFICIARY_ADDRESS_1     = 'Beneficiary_Address_1';
    const BENEFICIARY_ADDRESS_2     = 'Beneficiary_Address_2';
    const BENEFICIARY_ADDRESS_3     = 'Beneficiary_Address_3';
    const BENEFICIARY_ADDRESS_4     = 'Beneficiary_Address_4';
    const BENEFICIARY_EMAIL         = 'Beneficiary_Email';
    const BENEFICIARY_MOBILE        = 'Beneficiary_Mobile';
    const DEBIT_NARRATION           = 'Debit_Narration';
    const CREDIT_NARRATION          = 'Credit_Narration';
    const PAYMENT_DETAILS_1         = 'Payment Details 1';
    const PAYMENT_DETAILS_2         = 'Payment Details 2';
    const PAYMENT_DETAILS_3         = 'Payment Details 3';
    const PAYMENT_DETAILS_4         = 'Payment Details 4';
    const ENRICHMENT_1              = 'Enrichment_1';
    const ENRICHMENT_2              = 'Enrichment_2';
    const ENRICHMENT_3              = 'Enrichment_3';
    const ENRICHMENT_4              = 'Enrichment_4';
    const ENRICHMENT_5              = 'Enrichment_5';
    const ENRICHMENT_6              = 'Enrichment_6';
    const ENRICHMENT_7              = 'Enrichment_7';
    const ENRICHMENT_8              = 'Enrichment_8';
    const ENRICHMENT_9              = 'Enrichment_9';
    const ENRICHMENT_10             = 'Enrichment_10';
    const ENRICHMENT_11             = 'Enrichment_11';
    const ENRICHMENT_12             = 'Enrichment_12';
    const ENRICHMENT_13             = 'Enrichment_13';
    const ENRICHMENT_14             = 'Enrichment_14';
    const ENRICHMENT_15             = 'Enrichment_15';
    const ENRICHMENT_16             = 'Enrichment_16';
    const ENRICHMENT_17             = 'Enrichment_17';
    const ENRICHMENT_18             = 'Enrichment_18';
    const ENRICHMENT_19             = 'Enrichment_19';
    const ENRICHMENT_20             = 'Enrichment_20';

    // Extra headings in response file
    const STATUS_OF_TRANSACTION     = 'Status Of transaction';
    const UTR_NUMBER                = 'UTR number';
    const REMARKS                   = 'Remarks';
    const DATE_TIME                 = 'DateTime';
    const CMS_REF_NO                = 'Cms. ref no.';
    const DUMMY                     = 'Dummy';

    protected static $requestFileheadings = [
        self::CLIENT_CODE,
        self::PRODUCT_CODE,
        self::PAYMENT_TYPE,
        self::PAYMENT_REF_NO,
        self::PAYMENT_DATE,
        self::INSTRUMENT_DATE,
        self::DR_AC_NO,
        self::AMOUNT,
        self::BANK_CODE_INDICATOR,
        self::BENEFICIARY_CODE,
        self::BENEFICIARY_NAME,
        self::BENEFICIARY_BANK,
        self::IFSC_CODE,
        self::BENEFICIARY_ACC_NO,
        self::LOCATION,
        self::PRINT_LOCATION,
        self::INSTRUMENT_NUMBER,
        self::BENEFICIARY_ADDRESS_1,
        self::BENEFICIARY_ADDRESS_2,
        self::BENEFICIARY_ADDRESS_3,
        self::BENEFICIARY_ADDRESS_4,
        self::BENEFICIARY_EMAIL,
        self::BENEFICIARY_MOBILE,
        self::DEBIT_NARRATION,
        self::CREDIT_NARRATION,
        self::PAYMENT_DETAILS_1,
        self::PAYMENT_DETAILS_2,
        self::PAYMENT_DETAILS_3,
        self::PAYMENT_DETAILS_4,
        self::ENRICHMENT_1,
        self::ENRICHMENT_2,
        self::ENRICHMENT_3,
        self::ENRICHMENT_4,
        self::ENRICHMENT_5,
        self::ENRICHMENT_6,
        self::ENRICHMENT_7,
        self::ENRICHMENT_8,
        self::ENRICHMENT_9,
        self::ENRICHMENT_10,
        self::ENRICHMENT_11,
        self::ENRICHMENT_12,
        self::ENRICHMENT_13,
        self::ENRICHMENT_14,
        self::ENRICHMENT_15,
        self::ENRICHMENT_16,
        self::ENRICHMENT_17,
        self::ENRICHMENT_18,
        self::ENRICHMENT_19,
        self::ENRICHMENT_20,
    ];

    protected static $extraHeadingsInResponseFile = [
        self::STATUS_OF_TRANSACTION,
        self::UTR_NUMBER,
        self::REMARKS,
        self::DATE_TIME,
        self::CMS_REF_NO,
        self::DUMMY,
    ];

    public static function getRequestFileHeadings()
    {
        return static::$requestFileheadings;
    }

    public static function getResponseFileHeadings()
    {
        return array_merge(static::$requestFileheadings, static::$extraHeadingsInResponseFile);
    }
}
