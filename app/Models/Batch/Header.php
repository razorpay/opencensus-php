<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as EnachRblDebitHeadings;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as HdfcEMDebitHeadings;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as HdfcEMRegisterHeadings;

class Header
{
    const INPUT             = 'input';
    const OUTPUT            = 'output';

    //
    // Refund Headers
    //
    const PAYMENT_ID        = 'Payment Id';
    const AMOUNT            = 'Amount';
    const REFUND_ID         = 'Refund Id';
    const REFUNDED_AMOUNT   = 'Refunded Amount';
    const STATUS            = 'Status';
    const ERROR_CODE        = 'Error Code';
    const ERROR_DESCRIPTION = 'Error Description';

    //
    // Payment Link Headers
    //
    const INVOICE_NUMBER      = 'Invoice Number';
    const CUSTOMER_NAME       = 'Customer Name';
    const CUSTOMER_EMAIL      = 'Customer Email';
    const CUSTOMER_CONTACT    = 'Customer Contact';
    const DESCRIPTION         = 'Description';
    const EXPIRE_BY           = 'Expire By';
    const PARTIAL_PAYMENT     = 'Partial Payment';
    const PAYMENT_LINK_ID     = 'Payment Link Id';
    const SHORT_URL           = 'Payment Link Short URL';

    //
    // IRCTC Headers
    //
    const MERCHANT_REFERENCE = 'merchant_reference';
    const REFUND_TYPE        = 'refund_type';
    const REFUND_AMOUNT      = 'refund_amount';
    const CANCELLATION_DATE  = 'cancellation_date';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const CANCELLATION_ID    = 'cancellation_id';
    const PAYMENT_DATE       = 'payment_date';
    const REFUND_DATE        = 'refund_date';

    //
    // Linked Account / Sub-merchant Headers
    //
    const BUSINESS_NAME       = 'business_name';
    const BANK_ACCOUNT_TYPE   = 'bank_account_type';
    const BANK_ACCOUNT_NAME   = 'bank_account_name';
    const BANK_BRANCH_IFSC    = 'bank_branch_ifsc';
    const BANK_ACCOUNT_NUMBER = 'bank_account_number';
    const REFERENCE_ID        = 'reference_id';
    const ACCOUNT_ID          = 'account_id';

    // Sub-merchant headers
    const MERCHANT_NAME            = 'merchant_name';
    const MERCHANT_EMAIL           = 'merchant_email';
    const MERCHANT_ID              = 'merchant_id';
    const CONTACT_NAME             = 'contact_name';
    const CONTACT_EMAIL            = 'contact_email';
    const TRANSACTION_REPORT_EMAIL = 'transaction_report_email';
    const CONTACT_MOBILE           = 'contact_mobile';
    const ORGANIZATION_TYPE        = 'organization_type';
    const BILLING_LABEL            = 'billing_label';
    const INTERNATIONAL            = 'international';
    const PAYMENTS_FOR             = 'payments_for';
    const BUSINESS_MODEL           = 'business_model';
    const REGISTERED_ADDRESS       = 'registered_address';
    const REGISTERED_CITY          = 'registered_city';
    const REGISTERED_STATE         = 'registered_state';
    const REGISTERED_PINCODE       = 'registered_pincode';
    const OPERATIONAL_ADDRESS      = 'operational_address';
    const OPERATIONAL_CITY         = 'operational_city';
    const OPERATIONAL_STATE        = 'operational_state';
    const OPERATIONAL_PINCODE      = 'operational_pincode';
    const DOE                      = 'doe';
    const GSTIN                    = 'gstin';
    const EXPECTED_ANNUAL_VOLUME   = 'expected_annual_volume';
    const AVG_TRANSACTION_VALUE    = 'average_transaction_value';
    const PROMOTER_PAN             = 'promoter_pan';
    const PROMOTER_PAN_NAME        = 'promoter_pan_name';
    const WEBSITE_URL              = 'website';
    const WEBSITE_ABOUT            = 'website_about';
    const WEBSITE_CONTACT          = 'website_contact';
    const WEBSITE_PRICING          = 'website_pricing';
    const WEBSITE_PRIVACY          = 'website_privacy';
    const WEBSITE_REFUND           = 'website_refund';
    const WEBSITE_TERMS            = 'wesbite_terms';
    const BANK_ACCOUNT_ADDRESS_1   = 'bank_address_1';
    const BANK_ACCOUNT_CITY        = 'bank_account_city';
    const BANK_ACCOUNT_STATE       = 'bank_account_state';
    const BANK_ACCOUNT_PINCODE     = 'bank_account_pincode';

    //
    // Virtual Account Bulk Creation Headers
    //
    const VA_CUSTOMER_ID         = 'customer_id';
    const VA_CUSTOMER_NAME       = 'customer_name';
    const VA_CUSTOMER_CONTACT    = 'customer_contact';
    const VA_CUSTOMER_EMAIL      = 'customer_email';
    const VA_ID                  = 'virtual_account_id';
    const VA_DESCRIPTOR          = 'virtual_account_descriptor';
    const VA_DESCRIPTION         = 'virtual_account_description';
    const VA_NOTES               = 'virtual_account_notes';
    const VA_BANK_ACCOUNT_ID     = 'bank_account_id';
    const VA_BANK_ACCOUNT_NAME   = 'bank_account_name';
    const VA_BANK_ACCOUNT_NUMBER = 'bank_account_number';
    const VA_BANK_ACCOUNT_IFSC   = 'bank_account_ifsc';

    //
    // HDFC Emandate Debit Response File Headers
    //
    const HDFC_EM_DEBIT_TRANSACTION_REF_NO  = HdfcEMDebitHeadings::TRANSACTION_REF_NO;
    const HDFC_EM_DEBIT_MANDATE_ID          = HdfcEMDebitHeadings::MANDATE_ID;
    const HDFC_EM_DEBIT_ACCOUNT_NO          = HdfcEMDebitHeadings::ACCOUNT_NO;
    const HDFC_EM_DEBIT_AMOUNT              = HdfcEMDebitHeadings::AMOUNT;
    const HDFC_EM_DEBIT_SIP_DATE            = HdfcEMDebitHeadings::SIP_DATE;
    const HDFC_EM_DEBIT_FREQUENCY           = HdfcEMDebitHeadings::FREQUENCY;
    const HDFC_EM_DEBIT_FROM_DATE           = HdfcEMDebitHeadings::FROM_DATE;
    const HDFC_EM_DEBIT_TO_DATE             = HdfcEMDebitHeadings::TO_DATE;
    const HDFC_EM_DEBIT_STATUS              = HdfcEMDebitHeadings::STATUS;
    const HDFC_EM_DEBIT_REJECTION_REMARKS   = HdfcEMDebitHeadings::REJECTION_REMARKS;

    //
    // Bank Transfer Bulk Insertion
    //
    const PROVIDER       = 'provider';
    const PAYER_NAME     = 'payer_name';
    const PAYER_ACCOUNT  = 'payer_account';
    const PAYER_IFSC     = 'payer_ifsc';
    const PAYEE_ACCOUNT  = 'payee_account';
    const PAYEE_IFSC     = 'payee_ifsc';
    const MODE           = 'mode';
    const UTR            = 'utr';
    const TIME           = 'time';

    //
    // Batch recurring payments
    //
    const RECURRING_CHARGE_TOKEN       = 'token';
    const RECURRING_CHARGE_CUSTOMER_ID = 'customer_id';
    const RECURRING_CHARGE_AMOUNT      = 'amount';
    const RECURRING_CHARGE_CURRENCY    = 'currency';
    const RECURRING_CHARGE_RECEIPT     = 'receipt';
    const RECURRING_CHARGE_DESCRIPTION = 'description';
    const RECURRING_CHARGE_NOTES_1     = 'notes_1';
    const RECURRING_CHARGE_NOTES_2     = 'notes_2';
    const RECURRING_CHARGE_NOTES_3     = 'notes_3';
    const RECURRING_CHARGE_NOTES_4     = 'notes_4';
    const RECURRING_CHARGE_NOTES_5     = 'notes_5';
    const RECURRING_CHARGE_PAYMENT_ID  = 'payment_id';
    const RECURRING_CHARGE_ORDER_ID    = 'order_id';

    //
    // HDFC Emandate Register Response File Headers
    //
    const HDFC_EM_REGISTER_CLIENT_NAME                      = HdfcEMRegisterHeadings::CLIENT_NAME;
    const HDFC_EM_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO     = HdfcEMRegisterHeadings::MERCHANT_UNIQUE_REFERENCE_NO;
    const HDFC_EM_REGISTER_CUSTOMER_NAME                    = HdfcEMRegisterHeadings::CUSTOMER_NAME;
    const HDFC_EM_REGISTER_ACCOUNT_NUMBER                   = HdfcEMRegisterHeadings::CUSTOMER_ACCOUNT_NUMBER;
    const HDFC_EM_REGISTER_AMOUNT                           = HdfcEMRegisterHeadings::AMOUNT;
    const HDFC_EM_REGISTER_AMOUNT_TYPE                      = HdfcEMRegisterHeadings::AMOUNT_TYPE;
    const HDFC_EM_REGISTER_START_DATE                       = HdfcEMRegisterHeadings::START_DATE;
    const HDFC_EM_REGISTER_END_DATE                         = HdfcEMRegisterHeadings::END_DATE;
    const HDFC_EM_REGISTER_FREQUENCY                        = HdfcEMRegisterHeadings::FREQUENCY;
    const HDFC_EM_REGISTER_MANDATE_SERIAL_NUMBER            = HdfcEMRegisterHeadings::MANDATE_SERIAL_NUMBER;
    const HDFC_EM_REGISTER_MERCHANT_REQUEST_NO              = HdfcEMRegisterHeadings::MERCHANT_REQUEST_NO;
    const HDFC_EM_REGISTER_MANDATE_ID                       = HdfcEMRegisterHeadings::MANDATE_ID;
    const HDFC_EM_REGISTER_STATUS                           = HdfcEMRegisterHeadings::STATUS;
    const HDFC_EM_REGISTER_REMARK                           = HdfcEMRegisterHeadings::REMARK;

    //
    // eNach eMandate Register Response File Headers
    //
    const ENACH_REGISTER_SRNO               = 'SRNO';
    const ENACH_REGISTER_MANDATE_DATE       = 'MANDATE_DATE';
    const ENACH_REGISTER_MANDATE_ID         = 'MANDATE_ID';
    const ENACH_REGISTER_UMRN               = 'UMRN';
    const ENACH_REGISTER_CUST_REFNO         = 'CUST_REFNO';
    const ENACH_REGISTER_SCH_REFNO          = 'SCH_REFNO';
    const ENACH_REGISTER_REF_1              = 'REF_1';
    const ENACH_REGISTER_CUST_NAME          = 'CUST_NAME';
    const ENACH_REGISTER_BANK               = 'BANK';
    const ENACH_REGISTER_BRANCH             = 'BRANCH';
    const ENACH_REGISTER_BANK_CODE          = 'BANK_CODE';
    const ENACH_REGISTER_AC_TYPE            = 'AC_TYPE';
    const ENACH_REGISTER_ACNO               = 'ACNO';
    const ENACH_REGISTER_UPDATE_DATE        = 'UPDATE_DATE';
    const ENACH_REGISTER_AMOUNT             = 'AMOUNT';
    const ENACH_REGISTER_FREQUENCY          = 'FREQUENCY';
    const ENACH_REGISTER_COLLECTION_TYPE    = 'COLLECTION_TYPE';
    const ENACH_REGISTER_START_DATE         = 'START_DATE';
    const ENACH_REGISTER_END_DATE           = 'END_DATE';
    const ENACH_REGISTER_TEL_NO             = 'TEL_NO';
    const ENACH_REGISTER_MOBILE_NO          = 'MOBILE_NO';
    const ENACH_REGISTER_MAIL_ID            = 'MAIL_ID';
    const ENACH_REGISTER_UPLOAD_BATCH       = 'UPLOAD_BATCH';
    const ENACH_REGISTER_UPLOAD_DATE        = 'UPLOAD_DATE';
    const ENACH_REGISTER_RESPONSE_DATE      = 'RESPONSE_DATE';
    const ENACH_REGISTER_UTILITY_CODE       = 'UTILITY_CODE';
    const ENACH_REGISTER_UTILITY_NAME       = 'UTILITY_NAME';
    const ENACH_REGISTER_NODAL_ACNO         = 'NODAL_ACNO';
    const ENACH_REGISTER_STATUS             = 'STATUS';
    const ENACH_REGISTER_RETURN_CODE        = 'RETURN_CODE';
    const ENACH_REGISTER_CODE_DESC          = 'CODE_DESC';

    //
    // eNach Debit Response File Headers
    //
    const ENACH_DEBIT_SERIAL_NO             = EnachRblDebitHeadings::SERIAL_NO;
    const ENACH_DEBIT_ECS_DATE              = EnachRblDebitHeadings::ECS_DATE;
    const ENACH_DEBIT_SETTLEMENT_DATE       = EnachRblDebitHeadings::SETTLEMENT_DATE;
    const ENACH_DEBIT_CUST_REFNO            = EnachRblDebitHeadings::CUST_REFNO;
    const ENACH_DEBIT_SCH_REFNO             = EnachRblDebitHeadings::SCH_REFNO;
    const ENACH_DEBIT_CUSTOMER_NAME         = EnachRblDebitHeadings::CUSTOMER_NAME;
    const ENACH_DEBIT_REFNO                 = EnachRblDebitHeadings::REFNO;
    const ENACH_DEBIT_STATUS                = EnachRblDebitHeadings::STATUS;
    const ENACH_DEBIT_AMOUNT                = EnachRblDebitHeadings::AMOUNT;
    const ENACH_DEBIT_UMRN                  = EnachRblDebitHeadings::UMRN;
    const ENACH_DEBIT_UPLOAD_DATE           = EnachRblDebitHeadings::UPLOAD_DATE;
    const ENACH_DEBIT_ACKUPD_DATE           = EnachRblDebitHeadings::ACKUPD_DATE;
    const ENACH_DEBIT_RESPONSE_RECEIVED     = EnachRblDebitHeadings::RESPONSE_RECEIVED;
    const ENACH_DEBIT_REASON_CODE           = EnachRblDebitHeadings::REASON_CODE;
    const ENACH_DEBIT_REASON_DESCRIPTION    = EnachRblDebitHeadings::REASON_DESCRIPTION;

    //
    // Payout headers
    //
    const PAYOUT_CUSTOMER_ID         = 'customer_id';
    const PAYOUT_CUSTOMER_NAME       = 'customer_name';
    const PAYOUT_CUSTOMER_CONTACT    = 'customer_contact';
    const PAYOUT_CUSTOMER_EMAIL      = 'customer_email';
    const PAYOUT_BANK_ACCOUNT_ID     = 'bank_account_id';
    const PAYOUT_BANK_ACCOUNT_NUMBER = 'bank_account_number';
    const PAYOUT_BANK_IFSC           = 'bank_ifsc';
    const PAYOUT_ID                  = 'payout_id';
    const PAYOUT_METHOD              = 'payout_method';
    const PAYOUT_AMOUNT              = 'payout_amount';
    const PAYOUT_CURRENCY            = 'payout_currency';
    const PAYOUT_NOTES               = 'payout_notes';
    const PAYOUT_FEE                 = 'payout_fee';
    const PAYOUT_TAX                 = 'payout_tax';

    // Direct Debit headers
    const DIRECT_DEBIT_EMAIL            =   'email';
    const DIRECT_DEBIT_PHONE            =   'phone';
    const DIRECT_DEBIT_CARD             =   'card';
    const DIRECT_DEBIT_EXPIRY_MONTH     =   'expiry_month';
    const DIRECT_DEBIT_EXPIRY_YEAR      =   'expiry_year';
    const DIRECT_DEBIT_CARDHOLDER_NAME  =   'cardholder_name';
    const DIRECT_DEBIT_AMOUNT           =   'Amount';
    const DIRECT_DEBIT_CURRENCY         =   'currency';
    const DIRECT_DEBIT_RECEIPT          =   'receipt';
    const DIRECT_DEBIT_NOTES1           =   'notes_1';
    const DIRECT_DEBIT_NOTES2           =   'notes_2';
    const DIRECT_DEBIT_NOTES3           =   'notes_3';


    //output
    const DIRECT_DEBIT_PAYMENT_ID   =   'payment_id';
    const DIRECT_DEBIT_REMARKS      =   'remarks';

    const ELFIN_LONG_URL             = 'Long Url';
    const ELFIN_SHORT_URL            = 'Short Url';

    /**
     * Input and output file headers
     * The keys need to be like <type>_<sub-type>_<gateway>.
     * Above is subject to those value not being empty.
     *
     * @var array
     */
    const HEADER_MAP = [

        Type::REFUND => [

            self::INPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
            ],

            self::OUTPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
                self::REFUND_ID,
                self::REFUNDED_AMOUNT,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYMENT_LINK => [

            self::INPUT => [
                self::INVOICE_NUMBER,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
            ],

            self::OUTPUT => [
                self::INVOICE_NUMBER,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
                self::STATUS,
                self::PAYMENT_LINK_ID,
                self::SHORT_URL,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::IRCTC_REFUND => [

            self::INPUT => [
                self::MERCHANT_REFERENCE,
                self::REFUND_TYPE,
                self::REFUND_AMOUNT,
                self::PAYMENT_ID,
                self::CANCELLATION_DATE,
                self::PAYMENT_AMOUNT,
                self::CANCELLATION_ID,
            ],

            self::OUTPUT => [
                self::MERCHANT_REFERENCE,
                self::REFUND_TYPE,
                self::REFUND_AMOUNT,
                self::PAYMENT_ID,
                self::STATUS,
                self::REFUND_DATE,
                self::REFUND_ID,
                self::CANCELLATION_DATE,
                self::PAYMENT_AMOUNT,
                self::CANCELLATION_ID,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::IRCTC_SETTLEMENT => [

            self::INPUT => [
                self::PAYMENT_ID,
                self::PAYMENT_AMOUNT,
                self::PAYMENT_DATE,
                self::MERCHANT_REFERENCE,
            ],

            self::OUTPUT => [
                self::PAYMENT_ID,
                self::PAYMENT_AMOUNT,
                self::PAYMENT_DATE,
                self::MERCHANT_REFERENCE,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::LINKED_ACCOUNT => [

            self::INPUT => [
                self::BUSINESS_NAME,
                self::BANK_ACCOUNT_TYPE,
                self::BANK_ACCOUNT_NAME,
                self::BANK_BRANCH_IFSC,
                self::BANK_ACCOUNT_NUMBER,
                self::REFERENCE_ID,
                //
                // If this is passed and account with this id exists then we
                // patch the account entity with row data.
                //
                self::ACCOUNT_ID,
            ],

            self::OUTPUT => [
                self::BUSINESS_NAME,
                self::BANK_ACCOUNT_TYPE,
                self::BANK_ACCOUNT_NAME,
                self::BANK_BRANCH_IFSC,
                self::BANK_ACCOUNT_NUMBER,
                self::REFERENCE_ID,
                self::STATUS,
                self::ACCOUNT_ID,
            ],
        ],

        Type::VIRTUAL_BANK_ACCOUNT => [
            self::INPUT => [
                self::VA_CUSTOMER_ID,
                self::VA_CUSTOMER_NAME,
                self::VA_CUSTOMER_CONTACT,
                self::VA_CUSTOMER_EMAIL,
                self::VA_DESCRIPTOR,
                self::VA_DESCRIPTION,
                self::VA_NOTES,
            ],

            self::OUTPUT => [
                self::VA_CUSTOMER_ID,
                self::VA_CUSTOMER_NAME,
                self::VA_CUSTOMER_CONTACT,
                self::VA_CUSTOMER_EMAIL,
                self::VA_ID,
                self::VA_BANK_ACCOUNT_ID,
                self::VA_BANK_ACCOUNT_NAME,
                self::VA_BANK_ACCOUNT_NUMBER,
                self::VA_BANK_ACCOUNT_IFSC,
            ],
        ],

        'emandate_debit_hdfc' => [
            self::INPUT => [
                self::HDFC_EM_DEBIT_TRANSACTION_REF_NO,
                self::HDFC_EM_DEBIT_MANDATE_ID,
                self::HDFC_EM_DEBIT_ACCOUNT_NO,
                self::HDFC_EM_DEBIT_AMOUNT,
                self::HDFC_EM_DEBIT_SIP_DATE,
                self::HDFC_EM_DEBIT_FREQUENCY,
                self::HDFC_EM_DEBIT_FROM_DATE,
                self::HDFC_EM_DEBIT_TO_DATE,
                self::HDFC_EM_DEBIT_STATUS,
                self::HDFC_EM_DEBIT_REJECTION_REMARKS
            ]
        ],

        'emandate_debit_enach_rbl' => [
            self::INPUT => [
                self::ENACH_DEBIT_SERIAL_NO,
                self::ENACH_DEBIT_ECS_DATE,
                self::ENACH_DEBIT_SETTLEMENT_DATE,
                self::ENACH_DEBIT_CUST_REFNO,
                self::ENACH_DEBIT_SCH_REFNO,
                self::ENACH_DEBIT_CUSTOMER_NAME,
                self::ENACH_DEBIT_AMOUNT,
                self::ENACH_DEBIT_REFNO,
                self::ENACH_DEBIT_UMRN,
                self::ENACH_DEBIT_UPLOAD_DATE,
                self::ENACH_DEBIT_ACKUPD_DATE,
                self::ENACH_DEBIT_RESPONSE_RECEIVED,
                self::ENACH_DEBIT_STATUS,
                self::ENACH_DEBIT_REASON_CODE,
                self::ENACH_DEBIT_REASON_DESCRIPTION,
            ]
        ],

        Type::BANK_TRANSFER => [
            self::INPUT => [
                self::PROVIDER,
                self::PAYER_NAME,
                self::PAYER_ACCOUNT,
                self::PAYER_IFSC,
                self::PAYEE_ACCOUNT,
                self::PAYEE_IFSC,
                self::MODE,
                self::UTR,
                self::TIME,
                self::AMOUNT,
                self::DESCRIPTION,
            ],
            self::OUTPUT => [
                self::PROVIDER,
                self::PAYER_NAME,
                self::PAYER_ACCOUNT,
                self::PAYER_IFSC,
                self::PAYEE_ACCOUNT,
                self::PAYEE_IFSC,
                self::MODE,
                self::UTR,
                self::TIME,
                self::AMOUNT,
                self::DESCRIPTION,
                self::STATUS,
            ],
        ],

        Type::RECURRING_CHARGE => [
            self::INPUT => [
                self::RECURRING_CHARGE_TOKEN,
                self::RECURRING_CHARGE_CUSTOMER_ID,
                self::RECURRING_CHARGE_AMOUNT,
                self::RECURRING_CHARGE_CURRENCY,
                self::RECURRING_CHARGE_RECEIPT,
                self::RECURRING_CHARGE_DESCRIPTION,
                self::RECURRING_CHARGE_NOTES_1,
                self::RECURRING_CHARGE_NOTES_2,
                self::RECURRING_CHARGE_NOTES_3,
                self::RECURRING_CHARGE_NOTES_4,
                self::RECURRING_CHARGE_NOTES_5,
            ],
            self::OUTPUT => [
                self::RECURRING_CHARGE_TOKEN,
                self::RECURRING_CHARGE_CUSTOMER_ID,
                self::RECURRING_CHARGE_AMOUNT,
                self::RECURRING_CHARGE_CURRENCY,
                self::RECURRING_CHARGE_RECEIPT,
                self::RECURRING_CHARGE_DESCRIPTION,
                self::RECURRING_CHARGE_NOTES_1,
                self::RECURRING_CHARGE_NOTES_2,
                self::RECURRING_CHARGE_NOTES_3,
                self::RECURRING_CHARGE_NOTES_4,
                self::RECURRING_CHARGE_NOTES_5,
                self::RECURRING_CHARGE_ORDER_ID,
                self::RECURRING_CHARGE_PAYMENT_ID,
            ],
        ],

        'emandate_register_hdfc' => [
            self::INPUT => [
                self::HDFC_EM_REGISTER_ACCOUNT_NUMBER,
                self::HDFC_EM_REGISTER_MANDATE_ID,
                self::HDFC_EM_REGISTER_STATUS,
                self::HDFC_EM_REGISTER_REMARK,
                self::HDFC_EM_REGISTER_CLIENT_NAME,
                self::HDFC_EM_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO,
                self::HDFC_EM_REGISTER_CUSTOMER_NAME,
                self::HDFC_EM_REGISTER_AMOUNT,
                self::HDFC_EM_REGISTER_AMOUNT_TYPE,
                self::HDFC_EM_REGISTER_START_DATE,
                self::HDFC_EM_REGISTER_END_DATE,
                self::HDFC_EM_REGISTER_FREQUENCY,
                self::HDFC_EM_REGISTER_MANDATE_SERIAL_NUMBER,
                self::HDFC_EM_REGISTER_MERCHANT_REQUEST_NO,
            ],
        ],

        'emandate_acknowledge_enach_rbl' => [
            self::INPUT => [
                'data'
            ],
        ],

        'emandate_register_enach_rbl' => [
            self::INPUT => [
                self::ENACH_REGISTER_SRNO,
                self::ENACH_REGISTER_MANDATE_DATE,
                self::ENACH_REGISTER_MANDATE_ID,
                self::ENACH_REGISTER_UMRN,
                self::ENACH_REGISTER_CUST_REFNO,
                self::ENACH_REGISTER_SCH_REFNO,
                self::ENACH_REGISTER_REF_1,
                self::ENACH_REGISTER_CUST_NAME,
                self::ENACH_REGISTER_BANK,
                self::ENACH_REGISTER_BRANCH,
                self::ENACH_REGISTER_BANK_CODE,
                self::ENACH_REGISTER_AC_TYPE,
                self::ENACH_REGISTER_ACNO,
                self::ENACH_REGISTER_UPDATE_DATE,
                self::ENACH_REGISTER_AMOUNT,
                self::ENACH_REGISTER_FREQUENCY,
                self::ENACH_REGISTER_COLLECTION_TYPE,
                self::ENACH_REGISTER_START_DATE,
                self::ENACH_REGISTER_END_DATE,
                self::ENACH_REGISTER_TEL_NO,
                self::ENACH_REGISTER_MOBILE_NO,
                self::ENACH_REGISTER_MAIL_ID,
                self::ENACH_REGISTER_UPLOAD_BATCH,
                self::ENACH_REGISTER_UPLOAD_DATE,
                self::ENACH_REGISTER_RESPONSE_DATE,
                self::ENACH_REGISTER_UTILITY_CODE,
                self::ENACH_REGISTER_UTILITY_NAME,
                self::ENACH_REGISTER_NODAL_ACNO,
                self::ENACH_REGISTER_STATUS,
                self::ENACH_REGISTER_RETURN_CODE,
                self::ENACH_REGISTER_CODE_DESC,
            ],
        ],

        Type::PAYOUT => [
            self::INPUT => [
                self::PAYOUT_CUSTOMER_NAME,
                self::PAYOUT_CUSTOMER_CONTACT,
                self::PAYOUT_CUSTOMER_EMAIL,
                self::PAYOUT_BANK_ACCOUNT_NUMBER,
                self::PAYOUT_BANK_IFSC,
                self::PAYOUT_METHOD,
                self::PAYOUT_AMOUNT,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_NOTES,
            ],

            self::OUTPUT => [
                self::PAYOUT_CUSTOMER_ID,
                self::PAYOUT_CUSTOMER_NAME,
                self::PAYOUT_CUSTOMER_CONTACT,
                self::PAYOUT_CUSTOMER_EMAIL,
                self::PAYOUT_BANK_ACCOUNT_ID,
                self::PAYOUT_BANK_ACCOUNT_NUMBER,
                self::PAYOUT_BANK_IFSC,
                self::PAYOUT_ID,
                self::PAYOUT_METHOD,
                self::PAYOUT_AMOUNT,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_NOTES,
                self::PAYOUT_FEE,
                self::PAYOUT_TAX,
            ],
        ],

        Type::SUB_MERCHANT => [

            self::INPUT  => [
                self::MERCHANT_NAME,
                self::MERCHANT_EMAIL,
                self::CONTACT_NAME,
                self::CONTACT_EMAIL,
                self::TRANSACTION_REPORT_EMAIL,
                self::CONTACT_MOBILE,
                self::ORGANIZATION_TYPE,
                self::BUSINESS_NAME,
                self::BILLING_LABEL,
                self::INTERNATIONAL,
                self::PAYMENTS_FOR,
                self::BUSINESS_MODEL,
                self::REGISTERED_ADDRESS,
                self::REGISTERED_CITY,
                self::REGISTERED_STATE,
                self::REGISTERED_PINCODE,
                self::OPERATIONAL_ADDRESS,
                self::OPERATIONAL_CITY,
                self::OPERATIONAL_STATE,
                self::OPERATIONAL_PINCODE,
                self::DOE,
                self::GSTIN,
                self::EXPECTED_ANNUAL_VOLUME,
                self::AVG_TRANSACTION_VALUE,
                self::PROMOTER_PAN,
                self::PROMOTER_PAN_NAME,
                self::WEBSITE_URL,
                self::WEBSITE_ABOUT,
                self::WEBSITE_CONTACT,
                self::WEBSITE_PRICING,
                self::WEBSITE_PRIVACY,
                self::WEBSITE_REFUND,
                self::WEBSITE_TERMS,
                self::BANK_ACCOUNT_NAME,
                self::BANK_BRANCH_IFSC,
                self::BANK_ACCOUNT_NUMBER,
                self::BANK_ACCOUNT_TYPE,
                self::BANK_ACCOUNT_ADDRESS_1,
                self::BANK_ACCOUNT_CITY,
                self::BANK_ACCOUNT_STATE,
                self::BANK_ACCOUNT_PINCODE,
            ],

            self::OUTPUT => [
                self::MERCHANT_NAME,
                self::MERCHANT_EMAIL,
                self::MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::DIRECT_DEBIT  =>  [
            self::INPUT =>  [
                self::DIRECT_DEBIT_EMAIL,
                self::DIRECT_DEBIT_PHONE,
                self::DIRECT_DEBIT_CARD,
                self::DIRECT_DEBIT_EXPIRY_MONTH,
                self::DIRECT_DEBIT_EXPIRY_YEAR,
                self::DIRECT_DEBIT_CARDHOLDER_NAME,
                self::DIRECT_DEBIT_CURRENCY,
                self::DIRECT_DEBIT_AMOUNT,
                self::DIRECT_DEBIT_RECEIPT,
                self::DIRECT_DEBIT_NOTES1,
                self::DIRECT_DEBIT_NOTES2,
                self::DIRECT_DEBIT_NOTES3,
            ],

            self::OUTPUT    =>  [
                self::DIRECT_DEBIT_EMAIL,
                self::DIRECT_DEBIT_PHONE,
                self::DIRECT_DEBIT_CARD,
                self::DIRECT_DEBIT_EXPIRY_MONTH,
                self::DIRECT_DEBIT_EXPIRY_YEAR,
                self::DIRECT_DEBIT_CARDHOLDER_NAME,
                self::DIRECT_DEBIT_AMOUNT,
                self::DIRECT_DEBIT_CURRENCY,
                self::DIRECT_DEBIT_RECEIPT,
                self::DIRECT_DEBIT_NOTES1,
                self::DIRECT_DEBIT_NOTES2,
                self::DIRECT_DEBIT_NOTES3,
                self::DIRECT_DEBIT_PAYMENT_ID,
                self::DIRECT_DEBIT_REMARKS,
            ],
        ],

        Type::ELFIN => [

            self::INPUT => [
                self::ELFIN_LONG_URL,
            ],

            self::OUTPUT => [
                self::ELFIN_LONG_URL,
                self::ELFIN_SHORT_URL,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],
    ];

    /**
     * Additional headers added against each entry detailing the type of error
     * and its description, if any. Used in Validated file output.
     */
    const VALIDATED_HEADERS = [
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];

    /**
     * Validates headers of batch input file.
     *
     * @param string $headerKey
     * @param array  $keys
     *
     * @throws BadRequestException
     */
    public static function validate(string $headerKey, array $keys)
    {
        $expectedHeaders = self::HEADER_MAP[$headerKey][self::INPUT];

        $headersMissing = (bool) array_diff($expectedHeaders, $keys);

        $extraHeadersInInput = (count($expectedHeaders) !== count($keys));

        if (($headersMissing === true) or ($extraHeadersInInput === true))
        {
            throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                        null,
                        [
                            'expected_headers'  => $expectedHeaders,
                            'input_headers'     => $keys,
                            'headers_missing'   => $headersMissing,
                            'extra_headers'     => $extraHeadersInInput,
                        ]);
        }
    }

    public static function getInputHeadersForType(string $type): array
    {
        return self::HEADER_MAP[$type][self::INPUT] ?? [];
    }

    public static function getOutputHeadersForType(string $type): array
    {
        return self::HEADER_MAP[$type][self::OUTPUT] ?? [];
    }

    public static function getValidatedHeadersForType(string $type): array
    {
        return array_merge(self::HEADER_MAP[$type][self::INPUT] ?? [], self::VALIDATED_HEADERS);
    }

    public static function getHeadersForFileTypeAndBatchType(string $fileType, string $type): array
    {
        switch ($fileType)
        {
            case FileStore\Type::BATCH_INPUT:

                return self::getInputHeadersForType($type);

            case FileStore\Type::BATCH_OUTPUT:

                return self::getOutputHeadersForType($type);

            case FileStore\Type::BATCH_VALIDATED:

                return self::getValidatedHeadersForType($type);

            default:
                throw new LogicException("Invalid file type: $fileType");
        }
    }
}
