<?php

namespace RZP\Models\FundTransfer\Axis2;

class Headings
{
    // Request headers
    const IDENTIFIER = 'Identifier';
    const PAYMENT_MODE = 'Payment Mode';
    const CORPORATE_CODE = 'Corporate Code';
    const CUSTOMER_REFERENCE_NUMBER = 'Customer Reference Number';
    const DEBIT_ACCOUNT_NUMBER = 'Debit Account Number';
    const VALUE_DATE = 'Value Date';
    const TRANSACTION_CURRENCY = 'Transaction Currency';
    const TRANSACTION_AMOUNT = 'Transaction Amount';
    const BENEFICIARY_NAME = 'Beneficiary Name';
    const BENEFICIARY_CODE = 'Beneficiary Code / Vendor Code';
    const BENEFCIARY_ACCOUNT_TYPE = 'Benefciary Account Type';
    const BENEFICIARY_ADDRESS_1 = 'Beneficiary Address 1';
    const BENEFICIARY_ADDRESS_2 = 'Beneficiary Address 2';
    const BENEFICIARY_ADDRESS_3 = 'Beneficiary Address 3';
    const BENEFICIARY_CITY = 'Beneficiary City';
    const BENEFICIARY_STATE = 'Beneficiary State';
    const BENEFICIARY_PIN_CODE = 'Beneficiary Pin Code';
    const BENEFICIARY_IFSC_CODE = 'Beneficiary IFSC Code';
    const BENEFICIARY_BANK_NAME = 'Beneficiary Bank Name';
    const BASE_CODE = 'Base Code';
    const CHEQUE_DATE = 'Cheque Date';
    const PAYABLE_LOCATION = 'Payable location';
    const PRINT_LOCATION = 'Print Location';
    const BENEFICIARY_EMAIL_ADDRESS_1 = 'Beneficiary Email address 1';
    const BENEFICIARY_EMAIL_ADDRESS_2 = 'Beneficiary Email address 2';
    const BENEFICIARY_MOBILE_NUMBER = 'Beneficiary Mobile Number';
    const CORP_BATCH_NO = 'Corp Batch No';
    const COMPANY_CODE = 'Company Code';
    const EXTRA_1 = 'Extra 1';
    const EXTRA_2 = 'Extra 2';
    const EXTRA_3 = 'Extra 3';
    const EXTRA_4 = 'Extra 4';
    const EXTRA_5 = 'Extra 5';
    const PAYTYPE = 'PayType';
    const CORP_EMAIL_ADDR = 'CORP_EMAIL_ADDR';
    const TRANSMISSION_DATE = 'TRANSMISSION DATE';
    const USER_ID = 'User ID';
    const USER_DEPARTMENT = 'USER DEPARTMENT';
    const CUSTOMER_UNIQUE_NO = 'Customer Unique No';
    const CORP_CODE = 'Corp Code';
    const PAYMENT_RUN_DATE = 'Payment Run Date';

    // Response headers
    const TRANSACTION_UTR_NUMBER = 'Transaction UTR  number';
    const STATUS_CODE = 'Status Code';
    const STATUS_DESCRIPTION = 'Status Description';
    const BATCH_NO = 'Batch No';
    const VENDOR_CODE = 'Vendor Code';
    const TRANSACTION_VALUE_DATE = 'Transaction Value Date';
    const BANK_REFERENCE_NUMBER = 'Bank Reference Number';
    const AMOUNT = 'Amount';
    const CORPORATE_ACCOUNT_NUMBER = 'Corporate Account Number';
    const CORPORATE_IFSC_CODE = 'Corporate IFSC Code';
    const DEBIT_OR_CREDIT_INDICATOR = 'Debit_or_Credit Indicator';
    const CLIENT_BATCH_NO = 'Client Batch No';

    // Common attributes
    const PRODUCT_CODE = 'Product Code';
    const CHEQUE_NUMBER = 'Cheque Number';
    const BENEFICIARY_ACCOUNT_NUMBER = 'Beneficiary Account Number';

    // Bene file fields
    const PRIME_CORP_CODE = 'PRIME_CORP_CODE';
    const BENE_CODE = 'BENE_CODE';
    const BENE_NAME = 'BENE_NAME';
    const BENE_ACC_NUM = 'BENE_ACC_NUM';
    const BENE_IFSC_CODE = 'BENE_IFSC_CODE';
    const BENE_BANK_NAME = 'BENE_BANK_NAME';
    const BENE_EMAIL_ADDR1 = 'BENE_EMAIL_ADDR1';
    const BENE_MOBILE_NO = 'BENE_MOBILE_NO';

    public static function getRequestFileHeadings(): array
    {
        return [
            self::IDENTIFIER,
            self::PAYMENT_MODE,
            self::CORPORATE_CODE,
            self::CUSTOMER_REFERENCE_NUMBER,
            self::DEBIT_ACCOUNT_NUMBER,
            self::VALUE_DATE,
            self::TRANSACTION_CURRENCY,
            self::TRANSACTION_AMOUNT,
            self::BENEFICIARY_NAME,
            self::BENEFICIARY_CODE,
            self::BENEFICIARY_ACCOUNT_NUMBER,
            self::BENEFCIARY_ACCOUNT_TYPE,
            self::BENEFICIARY_ADDRESS_1,
            self::BENEFICIARY_ADDRESS_2,
            self::BENEFICIARY_ADDRESS_3,
            self::BENEFICIARY_CITY,
            self::BENEFICIARY_STATE,
            self::BENEFICIARY_PIN_CODE,
            self::BENEFICIARY_IFSC_CODE,
            self::BENEFICIARY_BANK_NAME,
            self::BASE_CODE,
            self::CHEQUE_NUMBER,
            self::CHEQUE_DATE,
            self::PAYABLE_LOCATION,
            self::PRINT_LOCATION,
            self::BENEFICIARY_EMAIL_ADDRESS_1,
            self::BENEFICIARY_EMAIL_ADDRESS_2,
            self::BENEFICIARY_MOBILE_NUMBER,
            self::CORP_BATCH_NO,
            self::COMPANY_CODE,
            self::PRODUCT_CODE,
            self::EXTRA_1,
            self::EXTRA_2,
            self::EXTRA_3,
            self::EXTRA_4,
            self::EXTRA_5,
            self::PAYTYPE,
            self::CORP_EMAIL_ADDR,
            self::TRANSMISSION_DATE,
            self::USER_ID,
            self::USER_DEPARTMENT,
        ];
    }

    public static function getResponseFileHeadings(): array
    {
        return [
            self::CUSTOMER_UNIQUE_NO,
            self::CORP_CODE,
            self::PAYMENT_RUN_DATE,
            self::PRODUCT_CODE,
            self::TRANSACTION_UTR_NUMBER,
            self::CHEQUE_NUMBER,
            self::STATUS_CODE,
            self::STATUS_DESCRIPTION,
            self::BATCH_NO,
            self::VENDOR_CODE,
            self::TRANSACTION_VALUE_DATE,
            self::BANK_REFERENCE_NUMBER,
            self::AMOUNT,
            self::CORPORATE_ACCOUNT_NUMBER,
            self::CORPORATE_IFSC_CODE,
            self::DEBIT_OR_CREDIT_INDICATOR,
            self::BENEFICIARY_ACCOUNT_NUMBER,
            self::CLIENT_BATCH_NO,
        ];
    }

    public static function getBeneficiaryFileHeadings(): array
    {
        return [
            self::PRIME_CORP_CODE,
            self::CORP_CODE,
            self::BENE_CODE,
            self::BENE_NAME,
            self::BENE_ACC_NUM,
            self::BENE_IFSC_CODE,
            self::BENE_BANK_NAME,
            self::BENE_EMAIL_ADDR1,
            self::BENE_MOBILE_NO,
        ];
    }
}
