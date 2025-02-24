<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Models\FileStore;
use RZP\Models\Merchant\Detail;
use RZP\Models\Settings\Repository as Settings;
use RZP\Models\PaymentLink\PaymentPageItem as PPI;
use RZP\Models\PaymentLink\Repository as PaymentLink;
use RZP\Models\PaymentLink as PL;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Gateway\Enach\Rbl\DebitFileHeadings as EnachRblDebitHeadings;
use RZP\Gateway\Enach\Citi\NachDebitFileHeadings as CitiNachDebitHeadings;
use RZP\Gateway\Enach\Citi\NachRegisterFileHeadings as CitiNachRegisterHeadings;
use RZP\Gateway\Netbanking\Hdfc\EMandateDebitFileHeadings as HdfcEMDebitHeadings;
use RZP\Gateway\Netbanking\Axis\EMandateDebitReconFileHeadings as AxisEMDebitHeadings;
use RZP\Gateway\Netbanking\Sbi\Emandate\RegisterFileHeadings as SbiEMRegisterHeadings;
use RZP\Gateway\Netbanking\Sbi\Emandate\DebitFileHeadings as SbiEMDebitHeadings;
use RZP\Gateway\Netbanking\Hdfc\EMandateRegisterFileHeadings as HdfcEMRegisterHeadings;
use RZP\Models\Batch\Processor\Nach\Migration\NachMigrationFileHeadings as NachMigrationHeadings;
use RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank\DebitFileHeadings as IciciENachDebitHeadings;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;

class Header
{
    const INPUT             = 'input';
    const OUTPUT            = 'output';

    // list headers which are sensitive/pci and should be encrypted before uploading batch file for processing
    // so that batch service don't come under PCI scope, add type in $haveSensitiveData array, if you want to use this
    const SENSITIVE_HEADERS = 'sensitive_headers';

    // A header key which holds notes values(key value pairs)
    const NOTES             = 'notes';
    const PRODUCTS          = 'products';
    // In file, notes columns are expected to be in format: Notes[<key>] & while parsing the file, formatted as above
    const NOTES_REGEX       = '/^notes\[(.*)]$/';
    const PRODUCTS_REGEX    = '/^products_([1-9]|1[0-5])\[(.*)]$/';

    const TERMINAL_CREATION_TYPE_REGEX           = '/^Type\[(.*)]$/';

    const NOTES_PLACE               = 'notes[place]';
    const NOTES_CODE                = 'notes[code]';
    const NOTES_BATCH_REFERENCE_ID  = 'notes[batch_reference_id]';
    const NOTES_FUND_ACCOUNT_NAME   = 'notes[fund_account_name]'; // For MFN Only
    const NOTES_FUND_ACCOUNT_NUMBER = 'notes[fund_account_number]'; // For MFN Only
    const NOTES_CORRELATION_ID      = 'notes[correlation_id]'; // For MFN Only
    const NOTES_STR_VALUE           = 'note[value]'; // on purpose this is not 'notes[value]', to avoid the NotesRegex

    //
    // Refund Headers
    //
    const PAYMENT_ID        = 'Payment Id';
    const AMOUNT            = 'Amount';
    const SPEED             = 'Speed';
    const REFUND_ID         = 'Refund Id';
    const REFUNDED_AMOUNT   = 'Refunded Amount';
    const STATUS            = 'Status';
    const ERROR_CODE        = 'Error Code';
    const ERROR_DESCRIPTION = 'Error Description';
    const REFUND_BENEFICIARY_NAME   = 'Beneficiary Name';
    const REFUND_ACCOUNT_NUMBER     = 'Account Number';
    const REFUND_IFSC               = 'IFSC';
    const REFUND_TRANSFER_MODE      = 'Transfer Mode';
    const RECEIPT                   = 'Receipt';

    //
    // RawAddress Headers
    //
    const RAW_ADDRESS_BULK_NAME = 'name';
    const RAW_ADDRESS_BULK_CONTACT = 'contact';
    const RAW_ADDRESS_BULK_LINE1 = 'line1';
    const RAW_ADDRESS_BULK_LINE2 = 'line2';
    const RAW_ADDRESS_BULK_LANDMARK = 'landmark';
    const RAW_ADDRESS_BULK_CITY = 'city';
    const RAW_ADDRESS_BULK_STATE = 'state';
    const RAW_ADDRESS_BULK_ZIPCODE = 'zipcode';
    const RAW_ADDRESS_BULK_COUNTRY = 'country';
    const RAW_ADDRESS_BULK_TAG = 'tag';
    const RAW_ADDRESS_BULK_STATUS = 'status';

    //
    // FulfillmentOrder Headers
    //
    const FULFILLMENT_ORDER_MERCHANT_ORDER_ID = 'merchant_order_id';
    const FULFILLMENT_ORDER_STATUS = 'status';
    const FULFILLMENT_ORDER_SHIPPING_CHARGES = 'shipping_charges';
    const FULFILLMENT_ORDER_AWB_NUMBER = 'awb_number';
    const FULFILLMENT_ORDER_SHIPPING_PROVIDER_NAME = 'shipping_provider_name';



    // CODEligibilityAttribute Headers
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_TYPE = 'Allowlist Type';
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_VALUE = 'Allowlist Value';
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_TYPE = 'Blocklist Type';
    const ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_VALUE = 'Blocklist Value';
    //
    // Payment Link Headers
    //
    const INVOICE_NUMBER           = 'Invoice Number';
    const CUSTOMER_NAME            = 'Customer Name';
    const CUSTOMER_EMAIL           = 'Customer Email';
    const CUSTOMER_CONTACT         = 'Customer Contact';
    const AMOUNT_IN_PAISE          = 'Amount (In Paise)';
    const DESCRIPTION              = 'Description';
    const EXPIRE_BY                = 'Expire By';
    const PARTIAL_PAYMENT          = 'Partial Payment';
    const PAYMENT_LINK_ID          = 'Payment Link Id';
    const SHORT_URL                = 'Payment Link Short URL';
    const FIRST_PAYMENT_MIN_AMOUNT = 'First Payment Min Amount (In Paise)';
    const CURRENCY                 = 'Currency';

    //
    // IRCTC Headers
    //
    const MERCHANT_REFERENCE      = 'merchant_reference';
    const REFUND_TYPE             = 'refund_type';
    const REFUND_AMOUNT           = 'refund_amount';
    const CANCELLATION_DATE       = 'cancellation_date';
    const PAYMENT_AMOUNT          = 'payment_amount';
    const CANCELLATION_ID         = 'cancellation_id';
    const PAYMENT_DATE            = 'payment_date';
    const REFUND_DATE             = 'refund_date';
    const MERCHANT_TXN_ID         = "MERCHANT_TXN_ID";
    const TRANSACTION_DATE        = "TRANSACTION_DATE";
    const BANK_TRANSACTION_ID     = "BANK_TRANSACTION_ID";
    const REFUND_STATUS           = "REFUND_STATUS";
    const BANK_REMARKS            = "BANK_REMARKS";
    const BANK_ACTUAL_REFUND_DATE = "BANK_ACTUAL_REFUND_DATE";
    const BANK_REFUND_TXN_ID      = "BANK_REFUND_TXN_ID";

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


    // Linked Account Create Headers
    const ACCOUNT_EMAIL                 = 'account_email';
    const DASHBOARD_ACCESS              = 'dashboard_access';
    const CUSTOMER_REFUNDS              = 'customer_refunds';
    const IFSC_CODE                     = 'ifsc_code';
    const ACCOUNT_NUMBER                = 'account_number';
    const BENEFICIARY_NAME              = 'beneficiary_name';
    const ACCOUNT_STATUS                = 'account_status';
    const ACTIVATED_AT                  = 'activated_at';

    // Sub-merchant headers
    const FEE_BEARER               = 'fee_bearer';
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
    const BUSINESS_CATEGORY        = 'business_category';
    const BUSINESS_SUB_CATEGORY    = 'business_sub_category';
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
    const COMPANY_PAN_NAME         = 'company_pan_name';
    const COMPANY_CIN              = 'company_cin';
    const COMPANY_PAN              = 'company_pan';

    //
    //Partner referral fetch headers
    //
    const REFERRAL_ID        = 'referral_id';
    const REF_CODE           = 'ref_code';
    const REFERRAL_PRODUCT   = 'product';
    const INVITER_USER_ID    = 'inviter_user_id';
    const INVITER_EMAIL      = 'inviter_email';
    const REQUEST_KYC_ACCESS = 'request_kyc_access';
    const METADATA           = 'metadata';
    const CLIENT_ID          = 'client_id';
    const APPLICATION_ID     = 'application_id';
    const OAUTH_REFERRAL     = 'oauth_referral';
    const REDIRECT_URI       = 'redirect_uri';
    const SCOPE              = 'scope';

    //
    // Mpan Bulk creation headers
    //
    const MPAN_ID                      =   'mpan_id';
    const MPAN_SERIAL_NUMBER           =   'SrNo';
    const MPAN_ADDED_ON                =   'ADDEDON';
    const MPAN_VISA_PAN                =   'VPAN';
    const MPAN_MASTERCARD_PAN          =   'MPAN';
    const MPAN_RUPAY_PAN               =   'RPAN';

    //
    // Parent config inheritance headers
    //
    const MERCHANT_CONFIG_INHERITANCE_PARENT_MERCHANT_ID = 'Parent Merchant Id';
    const MERCHANT_CONFIG_INHERITANCE_MERHCANT_ID        = 'Merchant Id';

    //
    const PGOS_RMDETAILS_BULK_MERCHANT_ID = 'Merchant ID';
    const PGOS_RMDETAILS_BULK_NAME = 'RM Name';
    const PGOS_RMDETAILS_BULK_EMAILS = 'RM Emails';

    // Bulk journal create headers
    //
    const CURRENCY_LEDGER = 'currency';
    const AMOUNT_LEDGER = 'amount';
    const COMMISSION = 'commission';
    const TAX = 'tax';
    const TRANSACTOR_ID = 'transactor_id';
    const TRANSACTOR_EVENT = 'transactor_event';
    const TRANSACTION_DATE_LEDGER = 'transaction_date';
    const JOURNAL_CREATE_NOTES = 'journal_create_notes';
    const API_TRANSACTION_ID = 'api_transaction_id';
    const ADDITIONAL_PARAMS = 'additional_params';
    const IDENTIFIERS = 'identifiers';
    const MONEY_PARAMS = 'money_params';
    const TENANT = 'tenant';
    const UPDATED_AT = 'updated_at';
    const JOURNAL_ID = 'journal_id';

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
    const VIRTUAL_ACCOUNT_ID     = 'Virtual Account Id';

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
    const NARRATION      = 'narration';

    //
    // Ed Merchant Search
    //
    const MERCHANT_IDS  = 'merchant_ids';

    const TOKEN_ID       = 'token_id';
    //
    // Batch recurring payments
    //
    const RECURRING_CHARGE_TOKEN       = 'token';
    const RECURRING_CHARGE_CUSTOMER_ID = 'customer_id';
    const RECURRING_CHARGE_AMOUNT      = 'amount';
    const RECURRING_CHARGE_CURRENCY    = 'currency';
    const RECURRING_CHARGE_RECEIPT     = 'receipt';
    const RECURRING_CHARGE_DESCRIPTION = 'description';
    const RECURRING_CHARGE_PAYMENT_ID  = 'payment_id';
    const RECURRING_CHARGE_ORDER_ID    = 'order_id';

    //
    // Custom Batch recurring payments for AXIS
    //
    const RECURRING_CHARGE_AXIS_SLNO              = 'slno';
    const RECURRING_CHARGE_AXIS_URNNO             = 'URNNo';
    const RECURRING_CHARGE_AXIS_FOLIO_NO          = 'Folio_No';
    const RECURRING_CHARGE_AXIS_SCHEMECODE        = 'SchemeCode';
    const RECURRING_CHARGE_AXIS_TRANSACTION_NO    = 'TransactionNo';
    const RECURRING_CHARGE_AXIS_INVESTOR_NAME     = 'InvestorName';
    const RECURRING_CHARGE_AXIS_PURCHASE_DAY      = 'Purchase Day';
    const RECURRING_CHARGE_AXIS_PUR_AMOUNT        = 'Pur Amount';
    const RECURRING_CHARGE_AXIS_BANK_ACCOUNTNO    = 'BankAccountNo';
    const RECURRING_CHARGE_AXIS_PURCHASE_DATE     = 'Purchase Date';
    const RECURRING_CHARGE_AXIS_BATCH_REF_NUMBER  = 'Batch Ref Number';
    const RECURRING_CHARGE_AXIS_BRANCH            = 'Branch';
    const RECURRING_CHARGE_AXIS_TR_TYPE           = 'Tr.Type';
    const RECURRING_CHARGE_AXIS_UMRNNO_OR_TOKENID = 'UMRN No / TOKEN ID';
    const RECURRING_CHARGE_AXIS_CREDIT_ACCOUNT_NO = 'Credit Account No';


    // Custom Batch recurring payments for BSE
    //
    const RECURRING_CHARGE_BSE_UNIQUE_REFERENCE_NUMBER = 'Unique Reference Number';
    const RECURRING_CHARGE_BSE_DEBIT_AMOUNT            = 'Debit Amount';
    const RECURRING_CHARGE_BSE_DUE_DATE                = 'Due Date';
    const RECURRING_CHARGE_BSE_ACTUAL_DEBIT_DATE       = 'Actual debit date';
    const RECURRING_CHARGE_BSE_ICCL_REFERENCE          = 'ICCL reference';
    const RECURRING_CHARGE_BSE_TRANSACTION_TYPE        = 'Transaction Type';
    const RECURRING_CHARGE_BSE_UMRN                    = 'UMRN';

    //
    // HDFC Emandate Register Response File Headers
    //
    const HDFC_EM_REGISTER_CLIENT_NAME                      = HdfcEMRegisterHeadings::CLIENT_NAME;
    const HDFC_EM_REGISTER_SUB_MERCHANT_NAME                = HdfcEMRegisterHeadings::SUB_MERCHANT_NAME;
    const HDFC_EM_REGISTER_CUSTOMER_NAME                    = HdfcEMRegisterHeadings::CUSTOMER_NAME;
    const HDFC_EM_REGISTER_ACCOUNT_NUMBER                   = HdfcEMRegisterHeadings::CUSTOMER_ACCOUNT_NUMBER;
    const HDFC_EM_REGISTER_AMOUNT                           = HdfcEMRegisterHeadings::AMOUNT;
    const HDFC_EM_REGISTER_AMOUNT_TYPE                      = HdfcEMRegisterHeadings::AMOUNT_TYPE;
    const HDFC_EM_REGISTER_START_DATE                       = HdfcEMRegisterHeadings::START_DATE;
    const HDFC_EM_REGISTER_END_DATE                         = HdfcEMRegisterHeadings::END_DATE;
    const HDFC_EM_REGISTER_FREQUENCY                        = HdfcEMRegisterHeadings::FREQUENCY;
    const HDFC_EM_REGISTER_MANDATE_ID                       = HdfcEMRegisterHeadings::MANDATE_ID;
    const HDFC_EM_REGISTER_MERCHANT_UNIQUE_REF_NO           = HdfcEMRegisterHeadings::MERCHANT_UNIQUE_REFERENCE_NO;
    const HDFC_EM_REGISTER_MANDATE_SERIAL_NO                = HdfcEMRegisterHeadings::MANDATE_SERIAL_NUMBER;
    const HDFC_EM_REGISTER_MERCHANT_REQUEST_NO              = HdfcEMRegisterHeadings::MERCHANT_REQUEST_NO;
    const HDFC_EM_REGISTER_STATUS                           = HdfcEMRegisterHeadings::STATUS;
    const HDFC_EM_REGISTER_REMARKS                          = HdfcEMRegisterHeadings::REMARK;

    //
    // HDFC Emandate Debit Response File Headers
    //
    const HDFC_EM_DEBIT_SR                  = HdfcEMDebitHeadings::SR;
    const HDFC_EM_DEBIT_TRANSACTION_REF_NO  = HdfcEMDebitHeadings::TRANSACTION_REF_NO;
    const HDFC_EM_DEBIT_SUB_MERCHANT_NAME   = HdfcEMDebitHeadings::SUB_MERCHANT_NAME;
    const HDFC_EM_DEBIT_MANDATE_ID          = HdfcEMDebitHeadings::MANDATE_ID;
    const HDFC_EM_DEBIT_ACCOUNT_NO          = HdfcEMDebitHeadings::ACCOUNT_NO;
    const HDFC_EM_DEBIT_AMOUNT              = HdfcEMDebitHeadings::AMOUNT;
    const HDFC_EM_DEBIT_SIP_DATE            = HdfcEMDebitHeadings::SIP_DATE;
    const HDFC_EM_DEBIT_FREQUENCY           = HdfcEMDebitHeadings::FREQUENCY;
    const HDFC_EM_DEBIT_FROM_DATE           = HdfcEMDebitHeadings::FROM_DATE;
    const HDFC_EM_DEBIT_TO_DATE             = HdfcEMDebitHeadings::TO_DATE;
    const HDFC_EM_DEBIT_STATUS              = HdfcEMDebitHeadings::STATUS;
    const HDFC_EM_DEBIT_REJECTION_REMARKS   = HdfcEMDebitHeadings::REJECTION_REMARKS;
    const HDFC_EM_DEBIT_NARRATION           = HdfcEMDebitHeadings::NARRATION;

    //
    //HDFC ezetap settlements
    //
    const TRANSACTION_SOURCE                = 'Txn.Source';
    const MERCHANT_CODE                     = 'MERCHANT CODE / External MID';
    const TERMINAL_NUMBER                   = 'TERMINAL NUMBER / External TID';
    const CARD_NUMBER_EZETAP                = 'CARD NUMBER  / Payer VPA / Customer Name';
    const MERCHANT_TRACK_ID                 = 'MERCHANT_TRACKID';
    const TRANS_DATE                        = 'TRANS DATE';
    const SETTLE_DATE                       = 'SETTLE DATE';
    const TRANSACTION_AMOUNT                = 'DOMESTIC AMT / Transaction Amount';
    const NET_AMOUNT                        = 'Net Amount';
    const UDF_1                             = 'UDF1';
    const UDF_2                             = 'UDF2';
    const UDF_3                             = 'UDF3';
    const UDF_4                             = 'UDF4';
    const UDF_5                             = 'UDF5';
    const BANK_REFERENCE_NUMBER             = 'TRAN_ID / UPI Trxn ID/ Bank Reference No';
    const SEQUENCE_NUMBER                   = 'SEQUENCE NUMBER / Txn ref no. (RRN)';
    const DEBIT_CREDIT_TYPE                 = 'DEBITCREDIT_TYPE / Trans Type';
    const REC_FMT                           = 'REC FMT / Transaction Type';
    const CGST_AMT                          = 'CGST AMT';
    const SGST_AMT                          = 'SGST AMT';
    const IGST_AMT                          = 'IGST AMT';
    const UTGST_AMT                         = 'UTGST AMT';
    const MSF                               = 'MSF';
    const GSTN_NO                           = 'GSTN_No';
    const MERCHANT_NAME_EZETAP              = 'Merchant Name';
    const BAT_NBR                           = 'BAT NBR';
    const UPVALUE                           = 'UPVALUE';
    const CARD_TYPE                         = 'CARD TYPE';
    const INTNL_AMT                         = 'INTNL AMT';
    const APPROV_CODE                       = 'APPROV CODE';
    const ARN_NO                            = 'ARN NO';
    const SERV_TAX                          = 'SERV TAX';
    const SB_CESS                           = 'SB Cess';
    const KK_CESS                           = 'KK Cess';
    const INVOICE_NUMBER_EZETAP             = 'INVOICE_NUMBER';
    const UPI_MERCHANT_ID                   = 'UPI Merchant ID';
    const MERCHANT_VPA                      = 'Merchant VPA';
    const CUSTOMER_REF_NO                   = 'Customer Ref No. (RRN)';
    const CURRENCY_EZETAP                   = 'Currency';
    const PAY_TYPE                          = 'Pay Type';




    // emandate npci - icici sponsore bank debit Response File Headers
    //
    const ICICI_NPCI_ENACH_DEBIT_ACH_TRANSACTION_CODE             = IciciENachDebitHeadings::ACH_TRANSACTION_CODE;
    const ICICI_NPCI_ENACH_DEBIT_CONTROL_9S                       = IciciENachDebitHeadings::CONTROL_9S;
    const ICICI_NPCI_ENACH_DEBIT_DESTINATION_ACCOUNT_TYPE         = IciciENachDebitHeadings::DESTINATION_ACCOUNT_TYPE;
    const ICICI_NPCI_ENACH_DEBIT_LEDGER_FOLIO_NUMBER              = IciciENachDebitHeadings::LEDGER_FOLIO_NUMBER;
    const ICICI_NPCI_ENACH_DEBIT_CONTROL_15S                      = IciciENachDebitHeadings::CONTROL_15S;
    const ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME  = IciciENachDebitHeadings::BENEFICIARY_ACCOUNT_HOLDER_NAME;
    const ICICI_NPCI_ENACH_DEBIT_CONTROL_9SS                      = IciciENachDebitHeadings::CONTROL_9SS;
    const ICICI_NPCI_ENACH_DEBIT_CONTROL_7S                       = IciciENachDebitHeadings::CONTROL_7S;
    const ICICI_NPCI_ENACH_DEBIT_USER_NAME                        = IciciENachDebitHeadings::USER_NAME;
    const ICICI_NPCI_ENACH_DEBIT_CONTROL_13S                      = IciciENachDebitHeadings::CONTROL_13S;
    const ICICI_NPCI_ENACH_DEBIT_AMOUNT                           = IciciENachDebitHeadings::AMOUNT;
    const ICICI_NPCI_ENACH_DEBIT_ACH_ITEM_SEQ_NO                  = IciciENachDebitHeadings::ACH_ITEM_SEQ_NO;
    const ICICI_NPCI_ENACH_DEBIT_CHECKSUM                         = IciciENachDebitHeadings::CHECKSUM;
    const ICICI_NPCI_ENACH_DEBIT_FLAG                             = IciciENachDebitHeadings::FLAG;
    const ICICI_NPCI_ENACH_DEBIT_REASON_CODE                      = IciciENachDebitHeadings::REASON_CODE;
    const ICICI_NPCI_ENACH_DEBIT_DESTINATION_BANK_IFSC            = IciciENachDebitHeadings::DESTINATION_BANK_IFSC;
    const ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER  = IciciENachDebitHeadings::BENEFICIARY_BANK_ACCOUNT_NUMBER;
    const ICICI_NPCI_ENACH_DEBIT_SPONSOR_BANK_IFSC                = IciciENachDebitHeadings::SPONSOR_BANK_IFSC;
    const ICICI_NPCI_ENACH_DEBIT_USER_NUMBER                      = IciciENachDebitHeadings::USER_NUMBER;
    const ICICI_NPCI_ENACH_DEBIT_TRANSACTION_REFERENCE            = IciciENachDebitHeadings::TRANSACTION_REFERENCE;
    const ICICI_NPCI_ENACH_DEBIT_PRODUCT_TYPE                     = IciciENachDebitHeadings::PRODUCT_TYPE;
    const ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_AADHAR_NUMBER        = IciciENachDebitHeadings::BENEFICIARY_AADHAR_NUMBER;
    const ICICI_NPCI_ENACH_DEBIT_UMRN                             = IciciENachDebitHeadings::UMRN;
    const ICICI_NPCI_ENACH_DEBIT_FILLER                           = IciciENachDebitHeadings::FILLER;

    //
    // Citi Nach Register Response File Headers
    //
    const CITI_NACH_REGISTER_SERIAL_NUMBER                 = CitiNachRegisterHeadings::SERIAL_NUMBER;
    const CITI_NACH_REGISTER_CATEGORY_CODE                 = CitiNachRegisterHeadings::CATEGORY_CODE;
    const CITI_NACH_REGISTER_CATEGORY_DESCRIPTION          = CitiNachRegisterHeadings::CATEGORY_DESCRIPTION;
    const CITI_NACH_REGISTER_START_DATE                    = CitiNachRegisterHeadings::START_DATE;
    const CITI_NACH_REGISTER_END_DATE                      = CitiNachRegisterHeadings::END_DATE;
    const CITI_NACH_REGISTER_CLIENT_CODE                   = CitiNachRegisterHeadings::CLIENT_CODE;
    const CITI_NACH_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO  = CitiNachRegisterHeadings::MERCHANT_UNIQUE_REFERENCE_NO;
    const CITI_NACH_REGISTER_CUSTOMER_ACCOUNT_NUMBER       = CitiNachRegisterHeadings::CUSTOMER_ACCOUNT_NUMBER;
    const CITI_NACH_REGISTER_CUSTOMER_NAME                 = CitiNachRegisterHeadings::CUSTOMER_NAME;
    const CITI_NACH_REGISTER_ACCOUNT_TYPE                  = CitiNachRegisterHeadings::ACCOUNT_TYPE;
    const CITI_NACH_REGISTER_BANK_NAME                     = CitiNachRegisterHeadings::BANK_NAME;
    const CITI_NACH_REGISTER_BANK_IFSC                     = CitiNachRegisterHeadings::BANK_IFSC;
    const CITI_NACH_REGISTER_AMOUNT                        = CitiNachRegisterHeadings::AMOUNT;
    const CITI_NACH_REGISTER_STATUS                        = CitiNachRegisterHeadings::STATUS;
    const CITI_NACH_REGISTER_REMARKS                       = CitiNachRegisterHeadings::REMARKS;
    const CITI_NACH_REGISTER_UMRN                          = CitiNachRegisterHeadings::UMRN;
    const CITI_NACH_REGISTER_LOT                           = CitiNachRegisterHeadings::LOT;
    const CITI_NACH_REGISTER_SOFT_COPY_RECEIVED_DATE       = CitiNachRegisterHeadings::SOFT_COPY_RECEIVED_DATE;
    //
    // Citi Nach Debit Response File Headers
    //
    const CITI_NACH_DEBIT_ACH_TRANSACTION_CODE             = CitiNachDebitHeadings::ACH_TRANSACTION_CODE;
    const CITI_NACH_DEBIT_CONTROL_9S                       = CitiNachDebitHeadings::CONTROL_9S;
    const CITI_NACH_DEBIT_DESTINATION_ACCOUNT_TYPE         = CitiNachDebitHeadings::DESTINATION_ACCOUNT_TYPE;
    const CITI_NACH_DEBIT_LEDGER_FOLIO_NUMBER              = CitiNachDebitHeadings::LEDGER_FOLIO_NUMBER;
    const CITI_NACH_DEBIT_CONTROL_15S                      = CitiNachDebitHeadings::CONTROL_15S;
    const CITI_NACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME  = CitiNachDebitHeadings::BENEFICIARY_ACCOUNT_HOLDER_NAME;
    const CITI_NACH_DEBIT_CONTROL_9SS                      = CitiNachDebitHeadings::CONTROL_9SS;
    const CITI_NACH_DEBIT_CONTROL_7S                       = CitiNachDebitHeadings::CONTROL_7S;
    const CITI_NACH_DEBIT_USER_NAME                        = CitiNachDebitHeadings::USER_NAME;
    const CITI_NACH_DEBIT_CONTROL_13S                      = CitiNachDebitHeadings::CONTROL_13S;
    const CITI_NACH_DEBIT_AMOUNT                           = CitiNachDebitHeadings::AMOUNT;
    const CITI_NACH_DEBIT_ACH_ITEM_SEQ_NO                  = CitiNachDebitHeadings::ACH_ITEM_SEQ_NO;
    const CITI_NACH_DEBIT_CHECKSUM                         = CitiNachDebitHeadings::CHECKSUM;
    const CITI_NACH_DEBIT_FLAG                             = CitiNachDebitHeadings::FLAG;
    const CITI_NACH_DEBIT_REASON_CODE                      = CitiNachDebitHeadings::REASON_CODE;
    const CITI_NACH_DEBIT_DESTINATION_BANK_IFSC            = CitiNachDebitHeadings::DESTINATION_BANK_IFSC;
    const CITI_NACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER  = CitiNachDebitHeadings::BENEFICIARY_BANK_ACCOUNT_NUMBER;
    const CITI_NACH_DEBIT_SPONSOR_BANK_IFSC                = CitiNachDebitHeadings::SPONSOR_BANK_IFSC;
    const CITI_NACH_DEBIT_USER_NUMBER                      = CitiNachDebitHeadings::USER_NUMBER;
    const CITI_NACH_DEBIT_TRANSACTION_REFERENCE            = CitiNachDebitHeadings::TRANSACTION_REFERENCE;
    const CITI_NACH_DEBIT_PRODUCT_TYPE                     = CitiNachDebitHeadings::PRODUCT_TYPE;
    const CITI_NACH_DEBIT_BENEFICIARY_AADHAR_NUMBER        = CitiNachDebitHeadings::BENEFICIARY_AADHAR_NUMBER;
    const CITI_NACH_DEBIT_UMRN                             = CitiNachDebitHeadings::UMRN;
    const CITI_NACH_DEBIT_FILLER                           = CitiNachDebitHeadings::FILLER;

    //
    // nach-migration request headers
    //
    const NACH_MIGRATION_START_DATE             = NachMigrationHeadings::START_DATE;
    const NACH_MIGRATION_END_DATE               = NachMigrationHeadings::END_DATE;
    const NACH_MIGRATION_BANK                   = NachMigrationHeadings::BANK;
    const NACH_MIGRATION_ACCOUNT_NUMBER         = NachMigrationHeadings::ACCOUNT_NUMBER;
    const NACH_MIGRATION_ACCOUNT_HOLDER_NAME    = NachMigrationHeadings::ACCOUNT_HOLDER_NAME;
    const NACH_MIGRATION_ACCOUNT_TYPE           = NachMigrationHeadings::ACCOUNT_TYPE;
    const NACH_MIGRATION_IFSC                   = NachMigrationHeadings::IFSC;
    const NACH_MIGRATION_MAX_AMOUNT             = NachMigrationHeadings::MAX_AMOUNT;
    const NACH_MIGRATION_UMRN                   = NachMigrationHeadings::UMRN;
    const NACH_MIGRATION_DEBIT_TYPE             = NachMigrationHeadings::DEBIT_TYPE;
    const NACH_MIGRATION_FREQ                   = NachMigrationHeadings::FREQ;
    const NACH_MIGRATION_METHOD                 = NachMigrationHeadings::METHOD;
    const NACH_MIGRATION_CUSTOMER_EMAIL         = NachMigrationHeadings::CUSTOMER_EMAIL;
    const NACH_MIGRATION_CUSTOMER_PHONE         = NachMigrationHeadings::CUSTOMER_PHONE;

    //
    // nach-migration response headers
    // response will contains these headers in addition to above request headers
    const NACH_MIGRATION_TOKEN_CREATION_STATUS  = NachMigrationHeadings::TOKEN_CREATION_STATUS;
    const NACH_MIGRATION_TOKEN_ID               = NachMigrationHeadings::TOKEN_ID;
    const NACH_MIGRATION_FAILURE_REASON         = NachMigrationHeadings::FAILURE_REASON;
    const NACH_MIGRATION_CUSTOMER_ID            = NachMigrationHeadings::CUSTOMER_ID;

    //
    // SBI Emandate Register Response File Headers
    //
    const SBI_EM_REGISTER_SR_NO                   = SbiEMRegisterHeadings::SR_NO;
    const SBI_EM_REGISTER_EMANDATE_TYPE           = SbiEMRegisterHeadings::EMANDATE_TYPE;
    const SBI_EM_REGISTER_UMRN                    = SbiEMRegisterHeadings::UMRN;
    const SBI_EM_REGISTER_MERCHANT_ID             = SbiEMRegisterHeadings::MERCHANT_ID;
    const SBI_EM_REGISTER_CUSTOMER_REF_NO         = SbiEMRegisterHeadings::CUSTOMER_REF_NO;
    const SBI_EM_REGISTER_SCHEME_NAME             = SbiEMRegisterHeadings::SCHEME_NAME;
    const SBI_EM_REGISTER_SUB_SCHEME_NAME         = SbiEMRegisterHeadings::SUB_SCHEME;
    const SBI_EM_REGISTER_DEBIT_CUSTOMER_NAME     = SbiEMRegisterHeadings::DEBIT_CUSTOMER_NAME;
    const SBI_EM_REGISTER_DEBIT_ACCOUNT_NUMBER    = SbiEMRegisterHeadings::DEBIT_ACCOUNT_NUMBER;
    const SBI_EM_REGISTER_DEBIT_ACCOUNT_TYPE      = SbiEMRegisterHeadings::DEBIT_ACCOUNT_TYPE;
    const SBI_EM_REGISTER_DEBIT_IFSC              = SbiEMRegisterHeadings::DEBIT_IFSC;
    const SBI_EM_REGISTER_DEBIT_BANK_NAME         = SbiEMRegisterHeadings::DEBIT_BANK_NAME;
    const SBI_EM_REGISTER_AMOUNT                  = SbiEMRegisterHeadings::AMOUNT;
    const SBI_EM_REGISTER_AMOUNT_TYPE             = SbiEMRegisterHeadings::AMOUNT_TYPE;
    const SBI_EM_REGISTER_CUSTOMER_ID             = SbiEMRegisterHeadings::CUSTOMER_ID;
    const SBI_EM_REGISTER_PERIOD                  = SbiEMRegisterHeadings::PERIOD;
    const SBI_EM_REGISTER_PAYMENT_TYPE            = SbiEMRegisterHeadings::PAYMENT_TYPE;
    const SBI_EM_REGISTER_FREQUENCY               = SbiEMRegisterHeadings::FREQUENCY;
    const SBI_EM_REGISTER_START_DATE              = SbiEMRegisterHeadings::START_DATE;
    const SBI_EM_REGISTER_END_DATE                = SbiEMRegisterHeadings::END_DATE;
    const SBI_EM_REGISTER_MOBILE                  = SbiEMRegisterHeadings::MOBILE;
    const SBI_EM_REGISTER_EMAIL                   = SbiEMRegisterHeadings::EMAIL;
    const SBI_EM_REGISTER_OTHER_REF_NO            = SbiEMRegisterHeadings::OTHER_REF_NO;
    const SBI_EM_REGISTER_PAN_NUMBER              = SbiEMRegisterHeadings::PAN_NUMBER;
    const SBI_EM_REGISTER_AUTO_DEBIT_DATE         = SbiEMRegisterHeadings::AUTO_DEBIT_DATE;
    const SBI_EM_REGISTER_AUTHENTICATION_MODE     = SbiEMRegisterHeadings::AUTHENTICATION_MODE;
    const SBI_EM_REGISTER_DATE_PROCESSED          = SbiEMRegisterHeadings::DATE_PROCESSED;
    const SBI_EM_REGISTER_STATUS                  = SbiEMRegisterHeadings::STATUS;
    const SBI_EM_REGISTER_NO_OF_DAYS_PENDING      = SbiEMRegisterHeadings::NO_OF_DAYS_PENDING;
    const SBI_EM_REGISTER_REJECT_REASON           = SbiEMRegisterHeadings::REJECT_REASON;
    const SBI_EM_REGISTER_TRANSACTION_DATE        = SbiEMRegisterHeadings::TRANSACTION_DATE;
    const SBI_EM_REGISTER_CUSTOMER_NAME           = SbiEMRegisterHeadings::CUSTOMER_NAME;
    const SBI_EM_REGISTER_CUSTOMER_ACCOUNT_NUMBER = SbiEMRegisterHeadings::CUSTOMER_ACCOUNT_NUMBER;
    const SBI_EM_REGISTER_MAX_AMOUNT              = SbiEMRegisterHeadings::MAX_AMOUNT;
    const SBI_EM_REGISTER_STATUS_DESCRIPTION      = SbiEMRegisterHeadings::STATUS_DESCRIPTION;
    const SBI_EM_REGISTER_START_DATE_REJECT_FILE  = SbiEMRegisterHeadings::START_DATE_REJECT_FILE;
    const SBI_EM_REGISTER_END_DATE_REJECT_FILE    = SbiEMRegisterHeadings::END_DATE_REJECT_FILE;
    const SBI_EM_REGISTER_UMRN_REJECT_RILE        = SbiEMRegisterHeadings::UMRN_REJECT_RILE;
    const SBI_EM_REGISTER_SBI_REFERENCE_NO        = SbiEMRegisterHeadings::SBI_REFERENCE_NO;
    const SBI_EM_REGISTER_MODE_OF_VERIFICATION    = SbiEMRegisterHeadings::MODE_OF_VERIFICATION;
    const SBI_EM_REGISTER_AMOUNT_TYPE_REJECT_FILE = SbiEMRegisterHeadings::AMOUNT_TYPE_REJECT_FILE;

    //
    // SBI Emandate Debit Response File Headers
    //
    const SBI_EM_DEBIT_RESPONSE_SERIAL_NUMBER    = SbiEMDebitHeadings::SERIAL_NUMBER;
    const SBI_EM_DEBIT_UMRN                      = SbiEMDebitHeadings::UMRN;
    const SBI_EM_DEBIT_CUSTOMER_CODE             = SbiEMDebitHeadings::CUSTOMER_CODE;
    const SBI_EM_DEBIT_CUSTOMER_NAME             = SbiEMDebitHeadings::CUSTOMER_NAME;
    const SBI_EM_DEBIT_TRANSACTION_INPUT_CHANNEL = SbiEMDebitHeadings::TRANSACTION_INPUT_CHANNEL;
    const SBI_EM_DEBIT_FILE_NAME                 = SbiEMDebitHeadings::FILE_NAME;
    const SBI_EM_DEBIT_CUSTOMER_REF_NO           = SbiEMDebitHeadings::CUSTOMER_REF_NO;
    const SBI_EM_DEBIT_MANDATE_HOLDER_NAME       = SbiEMDebitHeadings::MANDATE_HOLDER_NAME;
    const SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER      = SbiEMDebitHeadings::MANDATE_HOLDER_ACCOUNT_NO;
    const SBI_EM_DEBIT_DEBIT_BANK_IFSC           = SbiEMDebitHeadings::DEBIT_BANK_IFSC;
    const SBI_EM_DEBIT_DEBIT_DATE                = SbiEMDebitHeadings::DEBIT_DATE_RESP;
    const SBI_EM_DEBIT_AMOUNT                    = SbiEMDebitHeadings::AMOUNT;
    const SBI_EM_DEBIT_JOURNAL_NUMBER            = SbiEMDebitHeadings::JOURNAL_NUMBER;
    const SBI_EM_DEBIT_PROCESSING_DATE           = SbiEMDebitHeadings::PROCESSING_DATE;
    const SBI_EM_DEBIT_DEBIT_STATUS              = SbiEMDebitHeadings::DEBIT_STATUS;
    const SBI_EM_DEBIT_CREDIT_STATUS             = SbiEMDebitHeadings::CREDIT_STATUS;
    const SBI_EM_DEBIT_REASON                    = SbiEMDebitHeadings::REASON;
    const SBI_EM_DEBIT_CREDIT_DATE               = SbiEMDebitHeadings::CREDIT_DATE;

    //
    // AXIS Emandate Debit Response File Headers
    //
    const AXIS_EM_DEBIT_HEADING_PAYMENT_ID         = AxisEMDebitHeadings::HEADING_PAYMENT_ID;
    const AXIS_EM_DEBIT_HEADING_DEBIT_DATE         = AxisEMDebitHeadings::HEADING_DEBIT_DATE;
    const AXIS_EM_DEBIT_HEADING_MERCHANT_ID        = AxisEMDebitHeadings::HEADING_MERCHANT_ID;
    const AXIS_EM_DEBIT_HEADING_BANK_REF_NUMBER    = AxisEMDebitHeadings::HEADING_BANK_REF_NUMBER;
    const AXIS_EM_DEBIT_HEADING_CUSTOMER_NAME      = AxisEMDebitHeadings::HEADING_CUSTOMER_NAME;
    const AXIS_EM_DEBIT_HEADING_DEBIT_ACCOUNT      = AxisEMDebitHeadings::HEADING_DEBIT_ACCOUNT;
    const AXIS_EM_DEBIT_HEADING_DEBIT_AMOUNT       = AxisEMDebitHeadings::HEADING_DEBIT_AMOUNT;
    const AXIS_EM_DB_HEADING_MIS_INFO3             = AxisEMDebitHeadings::HEADING_MIS_INFO3;
    const AXIS_EM_DEBIT_HEADING_MIS_INFO4          = AxisEMDebitHeadings::HEADING_MIS_INFO4;
    const AXIS_EM_DEBIT_HEADING_FILE_REF           = AxisEMDebitHeadings::HEADING_FILE_REF;
    const AXIS_EM_DEBIT_HEADING_STATUS             = AxisEMDebitHeadings::HEADING_STATUS;
    const AXIS_EM_DEBIT_HEADING_REMARK             = AxisEMDebitHeadings::HEADING_REMARK;
    const AXIS_EM_DEBIT_HEADING_RECORD_IDENTIFIER  = AxisEMDebitHeadings::HEADING_RECORD_IDENTIFIER;

    //
    // eNach Acknowledgement Response File Headers
    //
    const ENACH_ACK_MANDATE_DATE    = 'MANDATE_DATE';
    const ENACH_ACK_BATCH           = 'BATCH';
    const ENACH_ACK_IHNO            = 'IHNO';
    const ENACH_ACK_MANDATE_TYPE    = 'MANDATE_TYPE';
    const ENACH_ACK_UMRN            = 'UMRN';
    const ENACH_ACK_REF_1           = 'REF_1';
    const ENACH_ACK_REF_2           = 'REF_2';
    const ENACH_ACK_CUST_NAME       = 'CUST_NAME';
    const ENACH_ACK_BANK            = 'BANK';
    const ENACH_ACK_BRANCH          = 'BRANCH';
    const ENACH_ACK_BANK_CODE       = 'BANK_CODE';
    const ENACH_ACK_AC_TYPE         = 'AC_TYPE';
    const ENACH_ACK_ACNO            = 'ACNO';
    const ENACH_ACK_ACK_DATE        = 'ACK_DATE';
    const ENACH_ACK_ACK_DESC        = 'ACK_DESC';
    const ENACH_ACK_AMOUNT          = 'AMOUNT';
    const ENACH_ACK_FREQUENCY       = 'FREQUENCY';
    const ENACH_ACK_TEL_NO          = 'TEL_NO';
    const ENACH_ACK_MOBILE_NO       = 'MOBILE_NO';
    const ENACH_ACK_MAIL_ID         = 'MAIL_ID';
    const ENACH_ACK_UPLOAD_BATCH    = 'UPLOAD_BATCH';
    const ENACH_ACK_UPLOAD_DATE     = 'UPLOAD_DATE';
    const ENACH_ACK_UPDATE_DATE     = 'UPDATE_DATE';
    const ENACH_ACK_SOLE_ID         = 'SOLE_ID';

    //
    // eNach Register Response File Headers
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
    const ENACH_REGISTER_RETURN_CODE        = 'RET_CODE';
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
    //enach npci netbanking register headers
    //
    const ENACH_NPCI_NETBANKING_REGISTER_UMRN              = 'UMRN';
    const ENACH_NPCI_NETBANKING_REGISTER_STATUS            = 'STATUS';
    const ENACH_NPCI_NETBANKING_REGISTER_MESSAGE_ID        = 'MESSAGE_ID';
    const ENACH_NPCI_NETBANKING_REGISTER_MANDATE_DATE      = 'MANDATE_DATE';
    const ENACH_NPCI_NETBANKING_REGISTER_MANDATE_ID        = 'MANDATE_ID';
    const ENACH_NPCI_NETBANKING_REGISTER_CUST_REF_NO       = 'CUST_REF_NO';
    const ENACH_NPCI_NETBANKING_REGISTER_SCH_REF_NO        = 'SCH_REF_NO';
    const ENACH_NPCI_NETBANKING_REGISTER_CUST_NAME         = 'CUST_NAME';
    const ENACH_NPCI_NETBANKING_REGISTER_BANK              = 'BANK';
    const ENACH_NPCI_NETBANKING_REGISTER_BRANCH            = 'BRANCH';
    const ENACH_NPCI_NETBANKING_REGISTER_BANK_CODE         = 'BANK_CODE';
    const ENACH_NPCI_NETBANKING_REGISTER_AC_TYPE           = 'AC_TYPE';
    const ENACH_NPCI_NETBANKING_REGISTER_AC_NO             = 'AC_NO';
    const ENACH_NPCI_NETBANKING_REGISTER_AMOUNT            = 'AMOUNT';
    const ENACH_NPCI_NETBANKING_REGISTER_FREQUENCY         = 'FREQUENCY';
    const ENACH_NPCI_NETBANKING_REGISTER_DEBIT_TYPE        = 'DEBIT_TYPE';
    const ENACH_NPCI_NETBANKING_REGISTER_START_DATE        = 'START_DATE';
    const ENACH_NPCI_NETBANKING_REGISTER_END_DATE          = 'END_DATE';
    const ENACH_NPCI_NETBANKING_REGISTER_UNTIL_CANCEL      = 'UNTIL_CANCEL';
    const ENACH_NPCI_NETBANKING_REGISTER_TEL_NO            = 'TEL_NO';
    const ENACH_NPCI_NETBANKING_REGISTER_MOBILE_NO         = 'MOBILE_NO';
    const ENACH_NPCI_NETBANKING_REGISTER_MAIL_ID           = 'MAIL_ID';
    const ENACH_NPCI_NETBANKING_REGISTER_UPLOAD_DATE       = 'UPLOAD_DATE';
    const ENACH_NPCI_NETBANKING_REGISTER_RESPONSE_DATE     = 'RESPONSE_DATE';
    const ENACH_NPCI_NETBANKING_REGISTER_UTILITY_CODE      = 'UTILITY_CODE';
    const ENACH_NPCI_NETBANKING_REGISTER_UTILITY_NAME      = 'UTILITY_NAME';
    const ENACH_NPCI_NETBANKING_REGISTER_STATUS_CODE       = 'STATUS_CODE';
    const ENACH_NPCI_NETBANKING_REGISTER_REASON            = 'REASON';
    const ENACH_NPCI_NETBANKING_REGISTER_MANDATE_REQID     = 'MANDATE_REQID';

    //
    // enach npci netbanking debit headers
    //
    const ENACH_NPCI_NETBANKING_DEBIT_PRESENTATION_DATE = 'Presentation Date';
    const ENACH_NPCI_NETBANKING_DEBIT_UMRN              = 'UMRN';
    const ENACH_NPCI_NETBANKING_DEBIT_PAYMENT_ID        = 'Transaction Ref No';
    const ENACH_NPCI_NETBANKING_DEBIT_UTILITY_CODE      = 'Utility Code';
    const ENACH_NPCI_NETBANKING_DEBIT_BANK_ACC          = 'Bank A/c Number';
    const ENACH_NPCI_NETBANKING_DEBIT_ACCOUNT_NAME      = 'Account Holder Name';
    const ENACH_NPCI_NETBANKING_DEBIT_BANK              = 'Bank';
    const ENACH_NPCI_NETBANKING_DEBIT__IFSC             = 'IFSC/MICR';
    const ENACH_NPCI_NETBANKING_DEBIT_AMOUNT            = 'Amount';
    const ENACH_NPCI_NETBANKING_DEBIT_REF_ONE           = 'Reference 1';
    const ENACH_NPCI_NETBANKING_DEBIT_REF_TWO           = 'Reference 2';
    const ENACH_NPCI_NETBANKING_DEBIT_STATUS            = 'Status';
    const ENACH_NPCI_NETBANKING_DEBIT_ERROR_CODE        = 'Reason Code';
    const ENACH_NPCI_NETBANKING_DEBIT_ERROR_DESCRIPTION = 'Reason Discription';
    const ENACH_NPCI_NETBANKING_DEBIT_USER_REF          = 'User Reference';

    const MERCHANT_ONBOARDING_EMI_SBI_MID         = 'MerchantID';
    const MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_MID = 'GatewayMID';
    const MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_TID = 'GatewayTID';

    const MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_ACQUIRER = 'GatewayAcquirer';
    const MERCHANT_ONBOARDING_EMI_SBI_RZP_TID     = 'TerminalID';

    const DIRECT_DEBIT_EMAIL           = 'email';
    const DIRECT_DEBIT_CONTACT         = 'contact';
    const DIRECT_DEBIT_CARD_NUMBER     = 'card_number';
    const DIRECT_DEBIT_EXPIRY_MONTH    = 'expiry_month';
    const DIRECT_DEBIT_EXPIRY_YEAR     = 'expiry_year';
    const DIRECT_DEBIT_CARDHOLDER_NAME = 'cardholder_name';
    const DIRECT_DEBIT_AMOUNT          = 'amount';
    const DIRECT_DEBIT_CURRENCY        = 'currency';
    const DIRECT_DEBIT_RECEIPT         = 'receipt';
    const DIRECT_DEBIT_DESCRIPTION     = 'description';
    const DIRECT_DEBIT_ORDER_ID        = 'order_id';
    const DIRECT_DEBIT_PAYMENT_ID      = 'payment_id';
    const DIRECT_DEBIT_REMARKS         = 'remarks';

    const ELFIN_LONG_URL               = 'Long Url';
    const ELFIN_SHORT_URL              = 'Short Url';

    //
    // OAuth Migration Token
    // Also uses MERCHANT_ID declared above
    //
    const ACCESS_TOKEN                 = 'access_token';
    const PUBLIC_TOKEN                 = 'public_token';
    const REFRESH_TOKEN                = 'refresh_token';
    const EXPIRES_IN                   = 'expires_in';

    // Partner submerchant headers
    const PARTNER_TYPE              = 'partner_type';
    const SUBMERCHANT_ID            = 'submerchant_id';
    const PARTNER_ID                = 'partner_id';
    const PARTNER_MERCHANT_ID       = 'partner_merchant_id';
    const ACCOUNT_NAME              = 'account_name';
    const IMPLICIT_PLAN_ID          = 'implicit_plan_id';
    const ACCOUNT_CODE              = 'account_code';
    const SUBMERCHANT_TYPE          = 'submerchant_type';
    const ANNUAL_TURNOVER_MIN       = 'annual_turnover_min';
    const ANNUAL_TURNOVER_MAX       = 'annual_turnover_max';
    const BUSINESS_VINTAGE          = 'business_vintage';
    const COMPANY_ADDRESS_LINE_1    = 'company_address_line_1';
    const COMPANY_ADDRESS_LINE_2    = 'company_address_line_2';
    const COMPANY_ADDRESS_CITY      = 'company_address_city';
    const COMPANY_ADDRESS_STATE     = 'company_address_state';
    const COMPANY_ADDRESS_COUNTRY   = 'company_address_country';
    const COMPANY_ADDRESS_PINCODE   = 'company_address_pincode';

    // Entity Mapping headers
    const ENTITY_FROM_ID       = 'entity_from_id';
    const ENTITY_TO_ID         = 'entity_to_id';
    const ENTITY_TO_IDS        = 'entity_to_ids';
    const ENTITY_FROM_TYPE     = 'entity_from_type';
    const ENTITY_TO_TYPE       = 'entity_to_type';


    // Admin Entity Headers
    const ADMIN_ID            = 'id';
    const EMAIL               = 'email';
    const NAME                = 'name';
    const USERNAME            = 'username';
    const PASSWORD            = 'password';
    const USER_TYPE           = 'user_type';
    const EMPLOYEE_CODE       = 'employee_code';
    const BRANCH_CODE         = 'branch_code';
    const DEPARTMENT_CODE     = 'department_code';
    const SUPERVISOR_CODE     = 'supervisor_code';
    const LOCATION_CODE       = 'location_code';
    const GROUPS              = 'groups';
    const ROLES               = 'roles';
    const DISABLED            = 'disabled';
    const LOCKED              = 'locked';
    const ALLOW_ALL_MERCHANTS = 'allow_all_merchants';

    //
    // Auth Link Headers
    //
    const AUTH_LINK_CUSTOMER_NAME       = 'name';
    const AUTH_LINK_CUSTOMER_EMAIL      = 'email';
    const AUTH_LINK_CUSTOMER_PHONE      = 'phone';
    const AUTH_LINK_AMOUNT_IN_PAISE     = 'amount';
    const AUTH_LINK_CURRENCY            = 'currency';
    const AUTH_LINK_TOKEN_EXPIRE_BY     = 'token_expiry_by';
    const AUTH_LINK_METHOD              = 'method';
    const AUTH_LINK_MAX_AMOUNT          = 'token_max_amount';
    const AUTH_LINK_EXPIRE_BY           = 'link_expiry_by';
    const AUTH_LINK_AUTH_TYPE           = 'auth_type';
    const AUTH_LINK_BANK                = 'bank';
    const AUTH_LINK_NAME_ON_ACCOUNT     = 'account_holder_name';
    const AUTH_LINK_IFSC                = 'ifsc';
    const AUTH_LINK_ACCOUNT_NUMBER      = 'account_number';
    const AUTH_LINK_ACCOUNT_TYPE        = 'account_type';
    const AUTH_LINK_RECEIPT             = 'receipt';
    const AUTH_LINK_DESCRIPTION         = 'description';
    const AUTH_LINK_NACH_REFERENCE1     = 'nach_reference1';
    const AUTH_LINK_NACH_REFERENCE2     = 'nach_reference2';
    const AUTH_LINK_NACH_CREATE_FORM    = 'nach_create_form';
    //
    // Auth Link Output Headers
    //
    const AUTH_LINK_ID                   = 'authorization_link_id';
    const AUTH_LINK_SHORT_URL            = 'authorization_link';
    const AUTH_LINK_NACH_PRI_FILLED_FORM = 'prefilled_form';
    const AUTH_LINK_STATUS               = 'link_status';
    const AUTH_LINK_MAIL_SENT            = 'sent_mail';
    const AUTH_LINK_SMS_SENT             = 'sent_sms';
    const AUTH_LINK_CREATED_AT           = 'created_at';

    //
    // Hitachi Bulk Terminal Creation Headers
    //
    const HITACHI_RID          = 'RID';
    const HITACHI_MERCHANT_ID  = 'Merchant ID';
    const HITACHI_SUB_IDS      = 'Sub IDs';
    const HITACHI_MID          = 'MID';
    const HITACHI_TID          = 'TID';
    const HITACHI_PART_NAME    = 'Part Name';
    const HITACHI_ME_NAME      = 'ME Name';
    const HITACHI_LOCATION     = 'Location';
    const HITACHI_CITY         = 'City';
    const HITACHI_STATE        = 'State';
    const HITACHI_COUNTRY      = 'Country';
    const HITACHI_MCC          = 'MCC';
    const HITACHI_TERM_STATUS  = 'Term Status';
    const HITACHI_ME_STATUS    = 'ME Status';
    const HITACHI_ZIPCODE      = 'ZIPCode';
    const HITACHI_SWIPER_ID    = 'Swiper ID';
    const HITACHI_SPONSOR_BANK = 'Sponsor Bank';
    const HITACHI_CURRENCY     = 'Currency';
    const HITACHI_TERMINAL_ID  = 'Terminal ID';
    const FAILURE_REASON       = 'Failure Reason';

    //
    // ICICI netbanking bulk terminal creation headers
    //
    const ICIC_NB_MERCHANT_ID  = 'merchant_id';
    const ICIC_NB_SUB_IDS      = 'Sub IDs';
    const ICIC_NB_GATEWAY_MID  = 'SPID';
    const ICIC_NB_GATEWAY_MID2 = 'Payee ID';
    const ICIC_NB_SECTOR       = 'Sector';
    const ICIC_NB_TERMINAL_ID  = 'Terminal ID';

    //
    // HDFC netbanking bulk terminal creation headers
    //
    const HDFC_NB_MERCHANT_ID          = 'Merchant ID';
    const HDFC_NB_GATEWAY_MERCHANT_ID  = 'HDFC Merchant ID';
    const HDFC_NB_CATEGORY             = 'Category';
    const HDFC_NB_TPV                  = 'Tpv';
    const HDFC_NB_TERMINAL_ID          = 'Terminal ID';

    //
    //  Zest Money bulk terminal creation headers
    //
    const ZESTMONEY_MERCHANT_ID          = 'Merchant ID';
    const ZESTMONEY_GATEWAY_MERCHANT_ID  = 'Gateway Merchant ID';
    const ZESTMONEY_CATEGORY             = 'Terminal Category';
    const ZESTMONEY_GATEWAY_MERCHANT_ID2 = 'Gateway Merchant ID2';
    const ZESTMONEY_TERMINAL_ID          = 'Terminal ID';

    //
    // Flex Money bulk terminal creation headers
    //
    const FLEXMONEY_MERCHANT_ID          = 'Merchant ID';
    const FLEXMONEY_GATEWAY_MERCHANT_ID  = 'Gateway Merchant ID';
    const FLEXMONEY_CATEGORY             = 'Terminal Category';
    const FLEXMONEY_GATEWAY_MERCHANT_ID2 = 'Gateway Merchant ID2';
    const FLEXMONEY_TERMINAL_ID          = 'Terminal ID';


    //
    // Early Salary bulk terminal creation headers
    //
    const EARLYSALARY_MERCHANT_ID          = 'Merchant ID';
    const EARLYSALARY_GATEWAY_MERCHANT_ID  = 'Gateway Merchant ID';
    const EARLYSALARY_CATEGORY             = 'Terminal Category';
    const EARLYSALARY_GATEWAY_MERCHANT_ID2 = 'Gateway Merchant ID2';
    const EARLYSALARY_TERMINAL_ID          = 'Terminal ID';

    //
    // Billdesk bulk terminal creation headers
    //
    const BILLDESK_MERCHANT_ID         = 'Merchant ID';
    const BILLDESK_GATEWAY_MERCHANT_ID = 'Gateway Merchant ID';
    const BILLDESK_CATEGORY            = 'Terminal Category';
    const BILLDESK_TERMINAL_ID         = 'Terminal ID';
    const BILLDESK_NON_RECURRING       = 'Non Recurring';

    //
    // Netbanking Axis bulk terminal creation headers
    //
    const AXIS_NB_MERCHANT_ID         = 'Merchant ID';
    const AXIS_NB_GATEWAY_MERCHANT_ID = 'Gateway Merchant ID';
    const AXIS_NB_CATEGORY            = 'Terminal Category';
    const AXIS_NB_TERMINAL_ID         = 'Terminal ID';
    const AXIS_NB_TPV                 = 'Tpv';
    const AXIS_NB_NON_RECURRING       = 'Non Recurring';

    //
    // Submerchant bulk assign headers
    //
    const TERMINAL_ID      = 'terminal_id';
    const VPA_WHITELISTED = 'vpa_whitelisted';

    // Contact Headers
    const CONTACT_ID                  = 'Contact Id';
    const CONTACT_TYPE                = 'Contact Type';
    // Using small suffix as there exists with snake cased values. :(
    const CONTACT_NAME_2              = 'Contact Name';
    const CONTACT_EMAIL_2             = 'Contact Email';
    const CONTACT_MOBILE_2            = 'Contact Mobile';
    const CONTACT_REFERENCE_ID        = 'Contact Reference Id';
    const CONTACT_ADDRESS             = 'Contact Address 1';
    const CONTACT_CITY                = 'Contact City';
    const CONTACT_ZIPCODE             = 'Contact Zipcode';
    const CONTACT_STATE               = 'Contact State';
    const CONTACT_GSTIN               = 'Contact GSTIN';
    const CONTACT_PAN                 = 'Contact PAN';

    // Fund Account Headers, refer HEADER_MAP for full list of input & output headers.
    const FUND_ACCOUNT_ID             = 'Fund Account Id';
    const FUND_ACCOUNT_TYPE           = 'Fund Account Type';
    const FUND_ACCOUNT_NAME           = 'Fund Account Name';
    const FUND_ACCOUNT_IFSC           = 'Fund Account Ifsc';
    const FUND_ACCOUNT_NUMBER         = 'Fund Account Number';
    const FUND_ACCOUNT_VPA            = 'Fund Account Vpa';
    const FUND_ACCOUNT_PHONE_NUMBER   = 'Fund Account Phone Number';
    const FUND_ACCOUNT_EMAIL          = 'Fund Account Email';
    const FUND_ACCOUNT_PROVIDER       = 'Fund Account Provider';
    const FUND_BANK_ACCOUNT_TYPE      = 'Bank Account Type';

    // Payout Headers, refer HEADER_MAP for full list of input & output headers.
    const RAZORPAYX_ACCOUNT_NUMBER = 'RazorpayX Account Number';
    const PAYOUT_AMOUNT            = 'Payout Amount';
    const PAYOUT_CURRENCY          = 'Payout Currency';
    const PAYOUT_MODE              = 'Payout Mode';
    const PAYOUT_PURPOSE           = 'Payout Purpose';
    const PAYOUT_NARRATION         = 'Payout Narration';
    const PAYOUT_REFERENCE_ID      = 'Payout Reference Id';
    const PAYOUT_ID                = 'Payout Id';
    const PAYOUT_AMOUNT_RUPEES     = 'Payout Amount (in Rupees)';
    const PAYOUT_DATE              = 'Payout Date';

    // Linked Account Reversal Headers
    const TRANSFER_ID              = 'Transfer Id';
    const REVERSAL_ID              = 'Reversal Id';
    const INITIATOR_ID             = 'Initiator Id';

    // Bulk Terminal Creation Headers
    const TERMINAL_CREATION_MERCHANT_ID                 = 'Merchant Id';
    const TERMINAL_CREATION_GATEWAY                     = 'Gateway';
    const TERMINAL_CREATION_GATEWAY_MERCHANT_ID         = 'Gateway Merchant ID';
    const TERMINAL_CREATION_GATEWAY_MERCHANT_ID2        = 'Gateway Merchant ID2';
    const TERMINAL_CREATION_GATEWAY_TERMINAL_ID         = 'Gateway Terminal ID';
    const TERMINAL_CREATION_GATEWAY_ACCESS_CODE         = 'Gateway Access Code';
    const TERMINAL_CREATION_PLAN_ID                     = 'Buy Pricing Plan ID';
    const TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD   = 'Gateway Terminal Password';
    const TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2  = 'Gateway Terminal Password2';
    const TERMINAL_CREATION_GATEWAY_SECURE_SECRET       = 'Gateway Secure Secret';
    const TERMINAL_CREATION_GATEWAY_SECURE_SECRET2      = 'Gateway Secure Secret2';
    const TERMINAL_CREATION_GATEWAY_RECON_PASSWORD      = 'Gateway Recon Password';
    const TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE = 'Gateway Client Certificate';
    const TERMINAL_CREATION_MC_MPAN              = 'Mastercard MPAN';
    const TERMINAL_CREATION_VISA_MPAN            = 'Visa MPAN';
    const TERMINAL_CREATION_RUPAY_MPAN           = 'Rupay MPAN';
    const TERMINAL_CREATION_VPA                  = 'VPA';
    const TERMINAL_CREATION_CATEGORY             = 'Category';
    const TERMINAL_CREATION_CARD                 = 'Card';
    const TERMINAL_CREATION_NETBANKING           = 'Netbanking';
    const TERMINAL_CREATION_EMANDATE             = 'Emandate';
    const TERMINAL_CREATION_EMI                  = 'EMI';
    const TERMINAL_CREATION_UPI                  = 'UPI';
    const TERMINAL_CREATION_OMNICHANNEL          = 'Omnichannel';
    const TERMINAL_CREATION_BANK_TRANSFER        = 'Bank Transfer';
    const TERMINAL_CREATION_AEPS                 = 'AEPS';
    const TERMINAL_CREATION_EMI_DURATION         = 'EMI Duration';
    const TERMINAL_CREATION_TYPE                 = 'Type';
    const TERMINAL_CREATION_MODE                 = 'Mode';
    const TERMINAL_CREATION_TPV                  = 'TPV';
    const TERMINAL_CREATION_DEVICE_ID            = 'Device Id';
    const TERMINAL_CREATION_LABEL                = 'Label';
    const TERMINAL_CREATION_INTERNATIONAL        = 'International';
    const TERMINAL_CREATION_CORPORATE            = 'Corporate';
    const TERMINAL_CREATION_EXPECTED             = 'Expected';
    const TERMINAL_CREATION_EMI_SUBVENTION       = 'EMI Subvention';
    const TERMINAL_CREATION_GATEWAY_ACQUIRER     = 'Gateway Acquirer';
    const TERMINAL_CREATION_NETWORK_CATEGORY     = 'Network Category';
    const TERMINAL_CREATION_CURRENCY             = 'Currency';
    const TERMINAL_CREATION_ACCOUNT_NUMBER       = 'Account Number';
    const TERMINAL_CREATION_IFSC_CODE            = 'IFSC Code';
    const TERMINAL_CREATION_CARDLESS_EMI         = 'Cardless EMI';
    const TERMINAL_CREATION_PAYLATER             = 'Paylater';
    const TERMINAL_CREATION_ENABLED              = 'Enabled';
    const TERMINAL_CREATION_STATUS               = 'Status';
    const TERMINAL_CREATION_CAPABILITY           = 'Capability';

    // Bulk Terminal Creation Headers
    const DEVICE_TERMINAL_MAPPING_TERMINAL_ID                 = 'Terminal Id';
    const DEVICE_TERMINAL_MAPPING_DEVICE_ID                   = 'Device Id';

    // Upi Onboarded Terminal edit Headers
    const UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID           =   'Terminal Id';
    const UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY               =   'Gateway';
    const UPI_ONBOARDED_TERMINAL_EDIT_ONLINE                =   'Online';
    const UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC              =   'Allow CC';
    const UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET          =   'Allow Wallet';
    const UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE     =   'Allow Credit Line';
    const UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE         =   'Merchant Size';
    const UPI_ONBOARDED_TERMINAL_EDIT_MCC                   =   'MCC';
    const UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL         =   'Edit Billing Label';
    const UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER         =   'Edit Mobile Number';
    const UPI_ONBOARDED_TERMINAL_EDIT_RECURRING             =   'Recurring';

    // Upi Terminal Onboarding Headers
    const UPI_TERMINAL_ONBOARDING_MERCHANT_ID   =   'Merchant Id';
    const UPI_TERMINAL_ONBOARDING_GATEWAY       =   'Gateway';
    const UPI_TERMINAL_ONBOARDING_VPA           =   'Vpa';
    const UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID  = 'Gateway Terminal ID2';
    const UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE  = 'Gateway Access Code';
    const UPI_TERMINAL_ONBOARDING_EXPECTED             = 'Expected';
    const UPI_TERMINAL_ONBOARDING_VPA_HANDLE           = 'Vpa Handle';
    const UPI_TERMINAL_ONBOARDING_RECURRING            = 'Recurring';
    const UPI_TERMINAL_ONBOARDING_MCC                  = 'Mcc';
    const UPI_TERMINAL_ONBOARDING_CATEGORY2            = 'Category2';
    const UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE        = 'Merchant Type';
    const UPI_TERMINAL_ONBOARDING_ALLOW_CC             = 'Allow CC';
    const UPI_TERMINAL_ONBOARDING_ALLOW_WALLET         = 'Allow Wallet';
    const UPI_TERMINAL_ONBOARDING_ALLOW_CREDIT_LINE    = 'Allow Credit Line';
    const UPI_TERMINAL_ONBOARDING_DIRECT_PUSH          = 'Direct Push';


    // Pricing Rules Addition Headers
    const PRICING_RULE_PLAN_ID                  = 'plan_id';
    const PRICING_RULE_PLAN_NAME                = 'plan_name';
    const PRICING_RULE_MERCHANT_ID              = 'merchant_id';
    const PRICING_RULE_PRODUCT                  = 'product';
    const PRICING_RULE_FEATURE                  = 'feature';
    const PRICING_RULE_PAYMENT_METHOD           = 'payment_method';
    const PRICING_RULE_PAYMENT_METHOD_TYPE      = 'payment_method_type';
    const PRICING_RULE_PAYMENT_METHOD_SUBTYPE   = 'payment_method_subtype';
    const PRICING_RULE_RECEIVER_TYPE            = 'receiver_type';
    const PRICING_RULE_PAYMENT_ISSUER           = 'payment_issuer';
    const PRICING_RULE_PAYMENT_NETWORK          = 'payment_network';
    const PRICING_RULE_GATEWAY                  = 'gateway';
    const PRICING_RULE_INTERNATIONAL            = 'international';
    const PRICING_RULE_EMI_DURATION             = 'emi_duration';
    const PRICING_RULE_PERCENT_RATE             = 'percent_rate';
    const PRICING_RULE_PERCENT_RATE_SCALE_FACTOR = 'percent_rate_scale_factor';
    const PRICING_RULE_AMOUNT_RANGE_ACTIVE      = 'amount_range_active';
    const PRICING_RULE_AMOUNT_RANGE_MIN         = 'amount_range_min';
    const PRICING_RULE_AMOUNT_RANGE_MAX         = 'amount_range_max';
    const PRICING_RULE_FIXED_RATE               = 'fixed_rate';
    const PRICING_RULE_MIN_FEE                  = 'min_fee';
    const PRICING_RULE_MAX_FEE                  = 'max_fee';
    const PRICING_RULE_PROCURER                  = 'procurer';
    const PRICING_RULE_UPDATE                   = 'update';
    const PRICING_RULE_FEE_BEARER               = 'fee_bearer';

    // Loc withdrawals

    const LOC_WITHDRAWAL_TRANSACTION_TYPE           = 'Transaction Type';
    const LOC_WITHDRAWAL_BENEFICIARY_CODE           = 'Beneficiary Code';
    const LOC_WITHDRAWAL_BENEFICIARY_ACCOUNT_NUMBER = 'Beneficiary Account Number';
    const LOC_WITHDRAWAL_INSTRUMENT_AMOUNT          = 'Instrument Amount';
    const LOC_WITHDRAWAL_BENEFICIARY_NAME           = 'Beneficiary Name';
    const LOC_WITHDRAWAL_DRAWEE_LOCATION            = 'Drawee Location';
    const LOC_WITHDRAWAL_PRINT_LOCATION             = 'Print Location';
    const LOC_WITHDRAWAL_BENE_ADDR_1                = 'Bene Address 1';
    const LOC_WITHDRAWAL_BENE_ADDR_2                = 'Bene Address 2';
    const LOC_WITHDRAWAL_BENE_ADDR_3                = 'Bene Address 3';
    const LOC_WITHDRAWAL_BENE_ADDR_4                = 'Bene Address 4';
    const LOC_WITHDRAWAL_BENE_ADDR_5                = 'Bene Address 5';
    const LOC_WITHDRAWAL_IRN                        = 'Instruction Reference Number';
    const LOC_WITHDRAWAL_CRN                        = 'Customer Reference Number';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_1          = 'Payment details 1';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_2          = 'Payment details 2';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_3          = 'Payment details 3';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_4          = 'Payment details 4';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_5          = 'Payment details 5';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_6          = 'Payment details 6';
    const LOC_WITHDRAWAL_PAYMENT_DETAILS_7          = 'Payment details 7';
    const LOC_WITHDRAWAL_CHEQUE_NUMBER              = 'Cheque Number';
    const LOC_WITHDRAWAL_TRN_DATE                   = 'Chq / Trn Date';
    const LOC_WITHDRAWAL_MICR_NUMBER                = 'MICR Number';
    const LOC_WITHDRAWAL_IFSC_CODE                   = 'IFSC Code';
    const LOC_WITHDRAWAL_BENE_BANK_NAME             = 'Bene Bank Name';
    const LOC_WITHDRAWAL_BENE_BANK_BRANCH_NAME      = 'Bene Bank Branch Name';
    const LOC_WITHDRAWAL_BENE_EMAIL_ID              = 'Beneficiary email id';
    const LOC_WITHDRAWAL_CLIENT_CODE                = 'Client Code';
    const LOC_WITHDRAWAL_LOAN_AMOUNT                = 'Loan Amount';
    const LOC_WITHDRAWAL_LOAN_INTEREST              = 'Loan interest %';
    const LOC_WITHDRAWAL_LOAN_TENURE                = 'Loan tenure';
    const LOC_WITHDRAWAL_UTR_NUMBER                 = 'UTR Number';

    // NPCI RUPAY IIN Batch
    const IIN_NPCI_RUPAY_ROW                     = 'row';

    //Hitachi Visa IIN Batch
    const IIN_HITACHI_VISA_ROW                   = 'row';

    //MC MasterCard IIN Batch
    const IIN_MC_MASTERCARD_COMPANY_ID           = 'COMPANY_ID';
    const IIN_MC_MASTERCARD_COMPANY_NAME         = 'COMPANY_NAME';
    const IIN_MC_MASTERCARD_ICA                  = 'ICA';
    const IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM   = 'ACCOUNT_RANGE_FROM';
    const IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO     = 'ACCOUNT_RANGE_TO';
    const IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE   = 'BRAND_PRODUCT_CODE';
    const IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME   = 'BRAND_PRODUCT_NAME';
    const IIN_MC_MASTERCARD_ACCEPTANCE_BRAND     = 'ACCEPTANCE_BRAND';
    const IIN_MC_MASTERCARD_COUNTRY              = 'COUNTRY';
    const IIN_MC_MASTERCARD_REGION               = 'REGION';

    // entity update action batch
    const ID = Detail\Entity::ID;
    const BUSINESS_REGISTERED_ADDRESS = Detail\Entity::BUSINESS_REGISTERED_ADDRESS;
    const BUSINESS_REGISTERED_STATE = Detail\Entity::BUSINESS_REGISTERED_STATE;

    //report headers
    const CONSUMER     = 'consumer';
    const REPORT_TYPE  = 'report_type';
    const CONFIG_ID    = 'config_id';
    const GENERATED_BY = 'generated_by';
    const START_TIME   = 'start_time';
    const END_TIME     = 'end_time';


    const ADJUSTMENT_REFERENCE_ID   = 'reference_id';
    const ADJUSTMENT_MERCHANT_ID    = 'merchant_id';
    const ADJUSTMENT_AMOUNT         = 'amount';
    const ADJUSTMENT_BALANCE_TYPE   = 'balance_type';
    const ADJUSTMENT_DESCRIPTION    = 'description';

    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID                        = 'merchant_id';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT        = 'percentage_of_balance_limit';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT            = 'settlements_count_limit';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT                    = 'pricing_percent';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT_SCALE_FACTOR       = 'pricing_percent_scale_factor';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT                 = 'es_pricing_percent';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT_SCALE_FACTOR    = 'es_pricing_percent_scale_factor';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT                   = 'max_amount_limit';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS                        = 'full_access';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY          = 'max_limit_per_working_day';
    const SETTLEMENT_ONDEMAND_FEATURE_CONFIG_BALANCE_SEPARATION_FLAG            = 'balance_separation_flag';

    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_MERCHANT_ID        = 'merchant_id';
    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRODUCT_NAME       = 'product_name';
    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_SEGMENT            = 'segment';
    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_ELIGIBLE           = 'eligible';
    const CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRE_APPROVED_LIMIT = 'pre_approved_limit';


    const EARLY_SETTLEMENT_TRIAL_MERCHANT_ID  = 'merchant_id';
    const EARLY_SETTLEMENT_TRIAL_FULL_ACCESS  = 'full_access';
    const EARLY_SETTLEMENT_TRIAL_DISABLE_DATE = 'disable_date';
    const EARLY_SETTLEMENT_TRIAL_AMOUNT_LIMIT = 'amount_limit';
    const EARLY_SETTLEMENT_ES_PRICING         = 'es_pricing';

    const MERCHANT_CAPITAL_TAGS_MERCHANT_ID = 'merchant_id';
    const MERCHANT_CAPITAL_TAGS_ACTION      = 'action';
    const MERCHANT_CAPITAL_TAGS_TAGS        = 'tags';

    const ICICI_ECOLLECT_REMITTING_BANK_UTR_NO      = 'REMITTING BANK UTR NO.';
    const ICICI_ECOLLECT_PAYMENT_TYPE               = 'PAYMENT TYPE';
    const ICICI_ECOLLECT_CREDIT_ACCOUNT_NO          = 'CREDIT ACCOUNT NO.';
    const ICICI_ECOLLECT_TRANSACTION_AMOUNT         = 'TRANSACTION AMOUNT';
    const ICICI_ECOLLECT_REMITTER_ACCOUNT_NAME      = 'REMITTER ACCOUNT NAME';
    const ICICI_ECOLLECT_REMITTER_ACCOUNT_NO        = 'REMITTER ACCOUNT NO.';
    const ICICI_ECOLLECT_REMITTING_BANK_IFSC_CODE   = 'REMITTING BANK IFSC CODE';
    const ICICI_ECOLLECT_TRANSACTION_DATE           = 'TRANSACTION DATE';
    const ICICI_ECOLLECT_UTR                        = 'ICICI BANK UTR NO.';
    const ICICI_ECOLLECT_CUSTOMER_CODE              = 'CUSTOMER CODE';
    const ICICI_ECOLLECT_DEALER_CODE                = 'DEALER CODE';
    const ICICI_ECOLLECT_REMITTANCE_INFORMATION     = 'REMITTANCE INFORMATION';

    const  RBL_ECOLLECT_TRANSACTION_TYPE            = 'TRANSACTION_TYPE';
    const  RBL_ECOLLECT_AMOUNT                      = 'AMOUNT';
    const  RBL_ECOLLECT_UTR_NUMBER                  = 'UTR_NUMBER';
    const  RBL_ECOLLECT_RRN_NUMBER                  = 'RRN_NUMBER';
    const  RBL_ECOLLECT_SENDER_IFSC                 = 'SENDER_IFSC';
    const  RBL_ECOLLECT_SENDER_ACCOUNT_NUMBER       = 'SENDER_ACCOUNT_NUMBER';
    const  RBL_ECOLLECT_SENDER_ACCOUNT_TYPE         = 'SENDER_ACCOUNT_TYPE';
    const  RBL_ECOLLECT_SENDER_NAME                 = 'SENDER_NAME';
    const  RBL_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE    = 'BENEFICIARY_ACCOUNT_TYPE';
    const  RBL_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER  = 'BENEFICIARY_ACCOUNT_NUMBER';
    const  RBL_ECOLLECT_BENENAME                    = 'BENENAME';
    const  RBL_ECOLLECT_CREDIT_DATE                 = 'CREDIT_DATE';
    const  RBL_ECOLLECT_CREDIT_ACCOUNT_NUMBER       = 'CREDIT_ACCOUNT_NUMBER';
    const  RBL_ECOLLECT_CORPORATE_CODE              = 'CORPORATE_CODE';
    const  RBL_ECOLLECT_SENDER_INFORMATION          = 'SENDER_INFORMATION';

    const  AXIS_ECOLLECT_MESSAGE_TYPE               = 'Message Type';
    const  AXIS_ECOLLECT_UTR_NUMBER                 = 'UTR Number';
    const  AXIS_ECOLLECT_SENDER_IFSC                = 'Sender IFSC';
    const  AXIS_ECOLLECT_SENDER_ACCOUNT_TYPE        = 'Sender Acc Type';
    const  AXIS_ECOLLECT_SENDER_ACCOUNT_NUMBER      = 'Sender Account Number';
    const  AXIS_ECOLLECT_SENDER_NAME                = 'Sender Name';
    const  AXIS_ECOLLECT_SENDER_ADDRESS             = 'Sender Address 1';
    const  AXIS_ECOLLECT_BENEFICIARY_IFSC           = 'Beneficiary IFSC';
    const  AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER = 'Beneficiary Account Number';
    const  AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NAME   = 'Beneficiary Account Name';
    const  AXIS_ECOLLECT_SENDER_INFORMATION         = 'Sender Information';
    const  AXIS_ECOLLECT_AMOUNT                     = 'Amount';
    const  AXIS_ECOLLECT_TRANSACTION_DATE           = 'Transaction Date';
    const  AXIS_ECOLLECT_CREDIT_DATE                = 'Credit Date';
    const  AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE   = 'Beneficiary Account Type';
    const  AXIS_ECOLLECT_RELATED_REFERENCE          = 'Related Reference';
    const  AXIS_ECOLLECT_BENEFICIARY_ADDRESS        = 'Beneficiary Address 1';
    const  AXIS_ECOLLECT_CORPORATE_CODE             = 'CORP_CODE';
    const  AXIS_ECOLLECT_CLIENT_CODE                = 'CLIENT_CODE';
    const  AXIS_ECOLLECT_CREDIT_ACCOUNT_NUMBER      = 'Credit Acc Number - NEFT/RTGS';
    const  AXIS_ECOLLECT_BATCH_TIME                 = 'Batch Time';
    const  AXIS_ECOLLECT_CIF_ID                     = 'CIF_ID';
    const  AXIS_ECOLLECT_BRANCH_REFERENCE           = 'BRANCH_REF';
    const  AXIS_ECOLLECT_STATUS                     = 'STATUS';

    // Bank Transfer Edit Payer Bank Account Headers
    const BANK_TRANSFER_EDIT_BANK_TRANSFER_ID       = 'BANK_TRANSFER_ID';
    const BANK_TRANSFER_EDIT_BENEFICIARY_NAME       = 'BENEFICIARY_NAME';
    const BANK_TRANSFER_EDIT_ACCOUNT_NUMBER         = 'ACCOUNT_NUMBER';
    const BANK_TRANSFER_EDIT_IFSC_CODE              = 'IFSC_CODE';
    const BANK_TRANSFER_EDIT_PAYER_BANK_ACCOUNT_ID  = 'PAYER_BANK_ACCOUNT_ID';

    // Reward Headers
    const CREDIT_POINTS             = 'Credit Points';
    const CREATED_AT                = 'Created At';
    const REMARKS                   = 'Remarks';
    const CREDITS_MERCHANT_ID       = 'Merchant Id';
    const CAMPAIGN                  = 'Campaign';
    const CREATOR_NAME              = 'Creator Name';
    const PRODUCT                   = 'Product';
    const TYPE                      = 'Type';
    // PL Service
    const PL_V2_REFERENCE_ID        = 'Reference Id';
    const PL_V2_UPI_LINK            = 'Upi Link';
    //this will store all the header names of format custom_field[key]
    public static array $plV2CustomFields = [];

    //Merchant activation Header
    const BUSINESS_TYPE            = Detail\Entity::BUSINESS_TYPE;
    const BUSINESS_SUBCATEGORY     = Detail\Entity::BUSINESS_SUBCATEGORY;
    const BUSINESS_REGISTERED_CITY = Detail\Entity::BUSINESS_REGISTERED_CITY;
    const BUSINESS_REGISTERED_PIN  = Detail\Entity::BUSINESS_REGISTERED_PIN;
    const SEND_ACTIVATION_EMAIL    = 'send_activation_email';

    // OTC headers
    const CLIENT = 'CLIENT';
    const ARRANGEMENT = 'ARRANGEMENT';
    const DEPSLIPNUM = 'DEPSLIPNUM';
    const BATCHNUM = 'BATCHNUM';
    const SCHNO = 'SCHNO';
    const CLGLOC = 'CLGLOC';
    const PICKUPLOC = 'PICKUPLOC';
    const PICKUPPOINT = 'PICKUPPOINT';
    const DRAWEEBANK = 'DRAWEEBANK';
    const INSTNUM = 'INSTNUM';
    const INSTAMT = 'INSTAMT';
    const INSTDATE = 'INSTDATE';
    const DEPDATE = 'DEPDATE';
    const DRAWERCODE = 'DRAWERCODE';
    const DRAWERDES = 'DRAWERDES';
    const VALUE_DATE = 'VALUE_DATE';
    const RETURNRES = 'RETURNRES';
    const ACTIVATIONDATE = 'ACTIVATIONDATE';
    const VALDATE = 'VALDATE';
    const INTERNALINSTNMBR = 'INTERNALINSTNMBR';
    const MICRCODE = 'MICRCODE';
    const ENTRYREJRMKS = 'ENTRYREJRMKS';
    const DEPOSITBRANCH = 'DEPOSITBRANCH';
    const E1 = 'E1';
    const E2 = 'E2';
    const E3 = 'E3';
    const E4 = 'E4';
    const E5 = 'E5';
    const E6 = 'E6';
    const E7 = 'E7';
    const E8 = 'E8';
    const E9 = 'E9';
    const E10 = 'E10';
    const E11 = 'E11';
    const E12 = 'E12';
    const E13 = 'E13';
    const E14 = 'E14';
    const E15 = 'E15';
    const E16 = 'E16';
    const E17 = 'E17';
    const E18 = 'E18';
    const E19 = 'E19';
    const E20 = 'E20';
    const E21 = 'E21';

    // RBL CA Activation
    const RZP_REF_NO                            = 'RZP Ref No';
    const COMMENT                               = 'Comment';
    const NEW_STATUS                            = 'New Status';
    const NEW_SUBSTATUS                         = 'New SubStatus';
    const NEW_BANK_STATUS                       = 'New Bank Status';
    const NEW_ASSIGNEE                          = 'New Assignee';
    const RM_NAME                               = 'RM Name';
    const RM_PHONE_NUMBER                       = 'RM Phone Number';
    const ACCOUNT_OPEN_DATE                     = 'Account Open Date';
    const ACCOUNT_LOGIN_DATE                    = 'Account Login Date';
    const SALES_TEAM                            = 'Sales Team';
    const SALES_POC_EMAIL                       = 'Sales POC Email';
    const API_ONBOARDED_DATE                    = 'API Onboarded Date';
    const API_ONBOARDING_LOGIN_DATE             = 'API Onboarding Login Date';
    const MID_OFFICE_POC_NAME                   = 'Called By';
    const DOCKET_REQUESTED_DATE                 = 'Docket Requested Date';
    const ESTIMATED_DOCKET_DELIVERY_DATE        = 'Estimated Docket Delivery Date';
    const DOCKET_DELIVERED_DATE                 = 'Docket Delivered Date';
    const COURIER_SERVICE_NAME                  = 'Courier Service Name';
    const COURIER_TRACKING_ID                   = 'Courier Tracking Id';
    const REASON_WHY_DOCKET_IS_NOT_DELIVERED    = 'Reason Why Docket Is Not Delivered';

    // RBL Bulk Upload Comments
    const DATE_TIME                 = 'Date-time';
    const FIRST_DISPOSITION         = 'First Disposition';
    const SECOND_DISPOSITION        = 'Second Disposition';
    const THIRD_DISPOSISTION        = 'Third Disposition';
    const OPS_CALL_COMMENT          = 'OPS Call Comment';

    // ICICI STP MIS
    const STP_ACCOUNT_NO                         = 'Account number';
    const STP_FCRM_SR_DATE                       = 'FCRM SR date';
    const STP_SR_NUMBER                          = 'SR Number';
    const STP_SR_STATUS                          = 'SR Status';
    const STP_SR_CLOSED_DATE                     = 'SR Closed date';
    const STP_REMARKS                            = 'ICICI Remarks';
    const STP_RZP_INTERVENTION                   = 'Razorpay intervention required cases';
    const STP_CONNECTED_BANKING                  = 'Connected Banking';
    const STP_T3_DATE                            = 'T+3 Date';
    const STP_HELPDESK_SR_STATUS                 = 'Hepdesk SR Status';
    const STP_HELPDESK_SR                        = 'Hepdesk SR';

    //ICICI CA Activation
    const APPLICATION_NO                          = 'APPLICATION_NO';
    const TRACKER_ID                              = 'TRACKER_ID';
    const CLIENT_NAME                             = 'Client name';
    const F_NAME                                  = 'F_NAME';
    const L_NAME                                  = 'L_NAME';
    const LEADID                                  = 'LEADID';
    const ICICI_CA_ACCOUNT_NUMBER                 = 'Account number';
    const ICICI_LEAD_SUB_STATUS                   = 'Lead sub status';
    const ICICI_CA_ACCOUNT_STATUS                 = 'Lead status*';
    const LAST_UPDATED_ON_DATE                    = 'Last updated on date';
    const LAST_UPDATED_ON_TIME                    = 'Last updated on time';
    const COMMENT_OR_REMARKS                      = 'Comment/Remarks';
    const LEAD_SENT_TO_BANK_DATE                  = 'Lead sent to bank date';
    const DATE_ON_WHICH_1ST_APPOINTMENT_WAS_FIXED = 'Date on which 1st appointment was fixed';
    const DOCS_COLLECTED_DATE                     = 'Docs collected date';
    const CASE_INITIATION_DATE                    = 'Case initiation date';
    const ACCOUNT_OPENED_DATE                     = 'Account Opened Date';
    const MULTI_LOCATION                          = 'Multi location';
    const DROP_OFF_REASON                         = 'Drop off reason';
    const STP_DOCS_COLLECTED                      = 'STP docs collected (Y/N)';
    const ACCOUNT_NUMBER_CHANGE                   = 'Account number change (Y/N)';
    const FOLLOW_UP_DATE                          = 'Follow up date';


    //ICICI Video KYC
    const ICICI_LEADID_CREATION_DATE                    = 'Lead ID creation date [OTP date]';

    const ICICI_T3_VKYC_COMPLETION_DATE                 = 'T+3 date for V KYC completion';

    const ICICI_VKYC_INELIGIBLE_DATE                    = 'VKYC ineligible date';

    const ICICI_VKYC_INELIGIBLE_REASON                  = 'VKYC ineligible reason';

    const ICICI_VKYC_COMPLETION_DATE                    = 'VKYC completion date';

    const ICICI_VKYC_DROP_OFF_DATE                      = 'VKYC drop off date';

    const ICICI_VKYC_UNSUCCESSFUL_DATE                  = 'VKYC unsuccessful date';

    const ICICI_LEAD_ASSIGNED_TO_PHYSICAL_TEAM_DATE     = 'Lead assigned to physical process team date';

    const ICICI_VKYC_STATUS                             = 'Status';

    //Mastercard
    const SR_NO                                = "Sr. No";
    const CARD_NUMBER                          = "Card Number";
    const ARN                                  = "ARN";
    const HITACHI_MASTERCARD_AMOUNT            = "Amt (4)";
    const HITACHI_MASTERCARD_CURRENCY          = "Currency (49)";
    const HITACHI_MASTERCARD_BILLING_AMOUNT    = "Billing Amt (30)";
    const HITACHI_MASTERCARD_BILLING_CURRENCY  = "Billing currency (149)";
    const HITACHI_MASTERCARD_TXN_DATE          = "Txn Date (12)";
    const HITACHI_MASTERCARD_SETTLEMENT_DATE   = "Settelment Date";
    const HITACHI_MASTERCARD_MID               = "MID (42)";
    const HITACHI_MASTERCARD_TID               = "TID (41)";
    const HITACHI_MASTERCARD_ME_NAME           = "ME Name (43)";
    const HITACHI_MASTERCARD_AUTH_CODE         = "Auth Code (38)";
    const HITACHI_MASTERCARD_RRN               = "RRN (37)";
    const HITACHI_MASTERCARD_MCC               = "MCC (26)";
    const HITACHI_MASTERCARD_CHARGEBACK_REF_NO = "CB Reference No (95)";
    const CHARGEBACK_DATE                      = "CB Date";
    const HITACHI_MASTERCARD_DOC_INDICATOR     = "Doc Indicator(0262)";
    const HITACHI_MASTERCARD_REASON_CODE       = "Reason Code (25)";
    const HITACHI_MASTERCARD_MESSAGE_TEXT      = "Message Text (72)";
    const FULFILMENT_TAT                       = "Fulfilement TAT";
    const AGEING_DAYS                          = "Ageing Days";
    const HITACHI_MASTERCARD_TYPE              = "Type";

    // Visa and RUPAY
    const CHARGEBACK_AMOUNT    = "Chgbk Amt";
    const SOURCE_AMOUNT        = "Source Amt";
    const SOURCE_CURRENCY      = "Source Currency";
    const BILLING_AMOUNT       = "Billing Amt";
    const BILLING_CURRENCY     = "Billing Currency";
    const TXN_DATE             = "Txn Date";
    const HITCAHI_MID          = "MID";
    const HITCAHI_TID          = "TID";
    const ME_NAME              = "ME Name";
    const AUTH_CODE            = "Auth Code";
    const RRN                  = "RRN";
    const MCC_CODE             = "MCC Code";
    const CHARGEBACK_REF_NO    = "CB Reference No";
    const DOC_INDICATOR        = "Doc Indicator";
    const REASON_CODE          = "Reason Code";
    const MESSAGE_TEXT         = "Message Text";
    const DUPLICATE_RRN        = "Duplicate RRN";
    const DATE_OF_ISSUE        = "Date of Issue";
    const HITACHI_DISPUTE_TYPE = "Dispute Type";
    const SETTLEMENT_DATE      = "Settlement Date";


    // Internal Instrument Request
    const INTERNAL_INSTRUMENT_REQUEST_ID = 'internal_instrument_request_id';

    // Payout Approval
    const APPROVE_REJECT_PAYOUT     = 'Approve (A) / Reject (R) payout';
    const P_A_AMOUNT                = 'amount(Rupees) (do not edit)';
    const P_A_CURRENCY              = 'currency (do not edit)';
    const P_A_CONTACT_NAME          = 'contact_name (do not edit)';
    const P_A_MODE                  = 'mode (do not edit)';
    const P_A_PURPOSE               = 'purpose (do not edit)';
    const P_A_PAYOUT_ID             = 'payout_id (do not edit)';
    const P_A_CONTACT_ID            = 'contact_id (do not edit)';
    const P_A_FUND_ACCOUNT_ID       = 'fund_account_id (do not edit)';
    const P_A_CREATED_AT            = 'created_at (do not edit)';
    const P_A_ACCOUNT_NUMBER        = 'account_number (do not edit)';
    const P_A_STATUS                = 'status (do not edit)';
    const P_A_NOTES                 = 'payout notes (do not edit)';
    const P_A_FEES                  = 'fees (tax inclusive) (do not edit)';
    const P_A_TAX                   = 'tax (do not edit)';
    const P_A_SCHEDULED_AT          = 'scheduled_at (do not edit)';

    // Capture Setting
    const CUSTOMER_TYPE                     = 'customer_type';
    const AUTO_CAPTURE_LATE_AUTH            = 'auto_capture_late_auth';
    const AUTO_REFUND_DELAY                 = 'auto_refund_delay';
    const DEFAULT_REFUND_SPEED              = 'default_refund_speed';
    const CAPTURE_SETTING_MERCHANT_ID       = 'merchant_id';
    const CAPTURE_SETTING_MERCHANT_NAME     = 'merchant_name';
    const CAPTURE_SETTING_BUSINESS_CATEGORY = 'business_category';
    const TOTAL_CAPTURES                    = 'total_captures';
    const CAPTURE_SETTING_NAME              = 'Name';
    const CAPTURE_SETTING_CONFIG            = 'Config';


    /**
     * Header to be used in Upload MIQ flow to create merchant.
     */
    const MIQ_MERCHANT_NAME                 = 'Merchant Name';
    const MIQ_DBA_NAME                      = 'DBA Name (billing label)';
    const MIQ_WEBSITE                       = 'Website';
    const MIQ_WEBSITE_ABOUT_US              = 'Website About us';
    const MIQ_WEBSITE_TERMS_CONDITIONS      = 'Website Terms and conditions';
    const MIQ_WEBSITE_CONTACT_US            = 'Website Contact us';
    const MIQ_WEBSITE_PRIVACY_POLICY        = 'Website Privacy Policy';
    const MIQ_WEBSITE_PRODUCT_PRICING       = 'Website Product Pricing';
    const MIQ_WEBSITE_REFUNDS               = 'Website Refunds';
    const MIQ_WEBSITE_CANCELLATION          = 'Website Cancellation';
    const MIQ_WEBSITE_SHIPPING_DELIVERY     = 'Website Shipping and Delivery';
    const MIQ_CONTACT_NAME                  = 'Contact Name';
    const MIQ_CONTACT_EMAIL                 = 'Contact Email';
    const MIQ_TXN_REPORT_EMAIL              = 'Transactions Report Email-id';
    const MIQ_ADDRESS                       = 'Address';
    const MIQ_CITY                          = 'City';
    const MIQ_PIN_CODE                      = 'PIN Code';
    const MIQ_STATE                         = 'State';
    const MIQ_CONTACT_NUMBER                = 'Contact Number';
    const MIQ_BUSINESS_TYPE                 = 'Business Type';
    const MIQ_CIN                           = 'CIN';
    const MIQ_BUSINESS_PAN                  = 'Business PAN';
    const MIQ_BUSINESS_NAME                 = 'Business Name';
    const MIQ_AUTHORISED_SIGNATORY_PAN      = 'Authorised Signatory PAN';
    const MIQ_PAN_OWNER_NAME                = 'PAN Owner name';
    const MIQ_BUSINESS_CATEGORY             = 'Business Category';
    const MIQ_SUB_CATEGORY                  = 'Sub Category';
    const MIQ_GSTIN                         = 'GSTIN';
    const MIQ_BUSINESS_DESCRIPTION          = 'Business Description';
    const MIQ_ESTD_DATE                     = 'ESTD Date';
    const MIQ_FEE_MODEL                     = 'Fee Model';
    const MIQ_NB_FEE_TYPE                   = 'NetBanking Fee Type';
    const MIQ_NB_FEE_BEARER                 = 'NetBanking Fee Bearer';
    const MIQ_AXIS                          = 'Axis NetBanking';
    const MIQ_HDFC                          = 'HDFC NetBanking';
    const MIQ_ICICI                         = 'ICICI NetBanking';
    const MIQ_SBI                           = 'SBI NetBanking';
    const MIQ_YES                           = 'Yes NetBanking';
    const MIQ_NB_ANY                        = 'NetBanking (Any)';
    const MIQ_DEBIT_CARD_FEE_TYPE           = 'Debit Card Fee Type';
    const MIQ_DEBIT_CARD_FEE_BEARER         = 'Debit Card Fee Bearer';
    const MIQ_DEBIT_CARD_0_2K               = 'Debit Card 0<2K';
    const MIQ_DEBIT_CARD_2K_1CR             = 'Debit Card 2K<1Cr';
    const MIQ_RUPAY_FEE_TYPE                = 'Rupay Fee Type';
    const MIQ_RUPAY_FEE_BEARER              = 'Rupay Fee Bearer';
    const MIQ_RUPAY_0_2K                    = 'Rupay 0<2K';
    const MIQ_RUPAY_2K_1CR                  = 'Rupay 2K<1Cr';
    const MIQ_UPI_FEE_TYPE                  = 'UPI Fee Type';
    const MIQ_UPI_FEE_BEARER                = 'UPI Fee Bearer';
    const MIQ_UPI                           = 'UPI';
    const MIQ_WALLETS_FEE_TYPE              = 'Wallets Fee Type';
    const MIQ_WALLETS_FEE_BEARER            = 'Wallets Fee Bearer';
    const MIQ_WALLETS_FREECHARGE            = 'Wallets (Freecharge)';
    const MIQ_WALLETS_ANY                   = 'Wallets (Any)';
    const MIQ_CREDIT_CARD_FEE_TYPE          = 'Credit Card Fee Type';
    const MIQ_CREDIT_CARD_FEE_BEARER        = 'Credit Card Fee Bearer';
    const MIQ_CREDIT_CARD_0_2K              = 'Credit Card 0<2K';
    const MIQ_CREDIT_CARD_2K_1CR            = 'Credit Card 2K<1Cr';
    const MIQ_INTERNATIONAL                 = 'International';
    const MIQ_INTL_CARD_FEE_TYPE            = 'International Cards Fee Type';
    const MIQ_INTL_CARD_FEE_BEARER          = 'International Cards Fee Bearer';
    const MIQ_INTERNATIONAL_CARD            = 'International Cards';
    const MIQ_BUSINESS_FEE_TYPE             = 'Business Fee Type';
    const MIQ_BUSINESS_FEE_BEARER           = 'Business Fee Bearer';
    const MIQ_BUSINESS                      = 'Business';
    const MIQ_BANK_ACC_NUMBER               = 'Bank Account Number';
    const MIQ_BENEFICIARY_NAME              = 'Beneficiary Name';
    const MIQ_BRANCH_IFSC_CODE              = 'Branch IFSC Code';
    const MIQ_OUT_FEE_BEARER                = 'Fee_bearer';
    const MIQ_OUT_MERCHANT_ID               = 'Merchant_id';
    const MIQ_MERCHANT_NAME_BUSINESS_NAME  = 'MerchantNameBusinessName';
    const MIQ_STATUS                        = 'status';
    const MIQ_MERCHANT_ID                   = 'Merchant_id';
    //custom fields for storing additional data for VAS merchants
    const FIELD1                            = 'Field1';
    const FIELD2                            = 'Field2';
    const FIELD3                            = 'Field3';
    const FIELD4                            = 'Field4';
    const FIELD5                            = 'Field5';
    const FIELD6                            = 'Field6';
    const FIELD7                            = 'Field7';
    const FIELD8                            = 'Field8';
    const FIELD9                            = 'Field9';
    const FIELD10                           = 'Field10';
    const FIELD11                           = 'Field11';
    const FIELD12                           = 'Field12';
    const FIELD13                           = 'Field13';
    const FIELD14                           = 'Field14';
    const FIELD15                           = 'Field15';

    // Payment Transfer Headers
    const PAYMENT_ID_2          = 'payment_id';
    const AMOUNT_2              = 'amount';
    const CURRENCY_2            = 'currency';
    const TRANSFER_NOTES        = 'transfer_notes';
    const LINKED_ACCOUNT_NOTES  = 'linked_account_notes';
    const ON_HOLD               = 'on_hold';
    const ON_HOLD_UNTIL         = 'on_hold_until';
    const SOURCE                = 'source';
    const RECIPIENT             = 'recipient';
    const CREATED_AT_2          = 'created_at';

    // Transfer Reversal Headers
    const TRANSFER_ID_2         = 'transfer_id';
    const REVERSAL_NOTES        = 'reversal_notes';

    // Payment Transfer Retry Headers
    const TRANSFER_ID_OLD       = 'transfer_id_old';
    const TRANSFER_ID_NEW       = 'transfer_id_new';

    // Bulk Payout Links Creation
    const PAYOUT_LINK_BULK_CONTACT_NAME        = 'Name of Contact';
    const PAYOUT_LINK_BULK_CONTACT_NUMBER      = 'Contact Phone Number';
    const PAYOUT_LINK_BULK_CONTACT_EMAIL       = 'Contact Email ID';
    const PAYOUT_LINK_BULK_AMOUNT              = 'Payout Link Amount';
    const PAYOUT_LINK_BULK_PAYOUT_DESC         = 'Payout Description';
    const PAYOUT_LINK_BULK_PAYOUT_LINK_ID      = 'Payout Link ID';
    const PAYOUT_LINK_BULK_SEND_SMS            = 'Send Link to Phone Number';
    const PAYOUT_LINK_BULK_SEND_EMAIL          = 'Send Link to Mail ID';
    const PAYOUT_LINK_BULK_REFERENCE_ID        = 'Reference ID(optional)';
    const PAYOUT_LINK_BULK_NOTES_TITLE         = 'Internal notes(optional): Title';
    const PAYOUT_LINK_BULK_NOTES_DESC          = 'Internal notes(optional): Description';
    const PAYOUT_LINK_BULK_EXPIRY_DATE         = 'Expiry Date(optional)';
    const PAYOUT_LINK_BULK_EXPIRY_TIME         = 'Expiry Time(optional)';

    // Website Checker
    const WEBSITE_CHECKER_URL    = 'url';
    const WEBSITE_CHECKER_RESULT = 'result';

    // Create And Execute Risk Action
    const RISK_ACTION_MERCHANT_ID               = 'merchant_id';
    const RISK_ACTION_BULK_WORKFLOW_ACTION_ID   = 'bulk_workflow_action_id';
    const RISK_ACTION_WORKFLOW_ACTION_ID        = 'workflow_action_id';
    const RISK_ACTION_STATUS                    = 'workflow_action_status';

    // Edit Chargeback POC
    const CHARGEBACK_POC_EMAIL  = 'email';
    const ACTION                = 'action';
    const ERROR_MESSAGE         = 'error_message';

    // Edit Whitelisted Domain
    const URL                           = 'url';
    const COMMENTS                      = 'comments';

    // Create Fraud
    // Input Headers
    const FRAUD_HEADER_ARN        = 'arn';
    const FRAUD_HEADER_RRN        = 'rrn';
    const FRAUD_HEADER_TYPE       = 'type';
    const SUB_TYPE                = 'sub_type';
    const FRAUD_HEADER_CURRENCY   = 'currency';
    const FRAUD_HEADER_AMOUNT     = 'amount_in_cents';
    const FRAUD_HEADER_SEND_MAIL  = 'send_mail';
    const BASE_AMOUNT             = 'base_amount';
    const REPORTED_TO_ISSUER_AT   = 'reported_to_issuer_at';
    const CHARGEBACK_CODE         = 'chargeback_code';
    const REPORTED_BY             = 'reported_by';
    const ERROR_REASON            = 'error_reason';
    const REPORTED_TO_RAZORPAY_AT = 'reported_to_razorpay_at';

    // Output Headers
    const FRAUD_OUTPUT_HEADER_ARN           =   'ARN';
    const FRAUD_OUTPUT_HEADER_PAYMENT_ID    =   'Payment ID';
    const FRAUD_OUTPUT_HEADER_FRAUD_ID      =   'Fraud ID';
    const FRAUD_OUTPUT_HEADER_STATUS        =   'Status';
    const FRAUD_OUTPUT_HEADER_ERROR_REASON  =   'Error Reason';
    const FRAUD_OUTPUT_HEADER_MERCHANT_ID   =   'Merchant ID';
    const FRAUD_OUTPUT_HEADER_FRESHDESK_ID  =   'Freshdesk ID';
    const FEATURE_FLAG = 'feature_flag';

    // Debit note
    const DEBIT_NOTE_PAYMENT_IDS           = 'payment_ids';
    const DEBIT_NOTE_SKIP_VALIDATION       = 'skip_validation';

    const VAULT_MIGRATE_TOKEN_NAMESPACE_TOKEN              = 'token';
    const VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE = 'existing_namespace';
    const VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE       = 'bu_namespace';
    const VAULT_MIGRATE_TOKEN_NAMESPACE_MIGRATED_TOKEN_ID  = 'migrated_token_id';


    // token hq charge batch
    const TOKEN_HQ_TYPE              = 'type';
    const TOKEN_HQ_COUNT             = 'count';
    const TOKEN_HQ_MERCHANT_ID       = 'merchant_id';
    const TOKEN_HQ_AGGREGATE_DATA_ID = 'aggregated_data_id';
    const TOKEN_HQ_FEES              = 'fees';
    const TOKEN_HQ_TAX               = 'tax';
    const TOKEN_HQ_REQUEST_PRICING_ID = 'request_pricing_id';
    const TOKEN_HQ_TRANSACTION_ID    = 'transaction_id';
    const TOKEN_HQ_FEE_MODEL         = 'fee_model';
    const TOKEN_HQ_CREATED_DATE      = 'created_date';

    // Wallet create accounts batch headers
    const WALLET_ACCOUNTS_NAME = 'Name';
    const WALLET_ACCOUNTS_EMAIL = 'Email';
    const WALLET_ACCOUNTS_CONTACT = 'Contact';
    const WALLET_ACCOUNTS_IDENTIFICATION_ID = 'Identification ID';
    const WALLET_ACCOUNTS_IDENTIFICATION_TYPE = 'Identification Type';
    const WALLET_ACCOUNTS_PARTNER_CUSTOMER_ID = 'Partner Customer ID';
    const WALLET_ACCOUNTS_PARTNER_USER_ID = 'Partner User ID';
    const WALLET_ACCOUNTS_DOB = 'Date Of Birth';

    // Wallet create load batch headers
    const WALLET_LOAD_CONTACT = 'Contact';
    const WALLET_LOAD_AMOUNT = 'Amount (In Paise)';
    const WALLET_LOAD_DESCRIPTION = 'Description (Optional)';
    const WALLET_LOAD_CATEGORY = 'Category (Optional)';
    const WALLET_LOAD_REFERENCE_ID = 'Reference ID (Optional)';

    // Wallet create container load batch headers
    const WALLET_CONTAINER_LOAD_USER_ID = "User ID";
    const WALLET_CONTAINER_LOAD_PROGRAM_ID = "Program ID";
    const WALLET_CONTAINER_LOAD_AMOUNT = 'Amount (In Paise)';
    const WALLET_CONTAINER_LOAD_REFERENCE_ID = 'Reference ID (Optional)';
    const WALLET_CONTAINER_LOAD_DESCRIPTION = 'Description (Optional)';
    const WALLET_CONTAINER_LOAD_NOTES = 'Notes (Optional)';
    const WALLET_CONTAINER_LOAD_EXPIRY_DATE = 'Expiry Date (YYYY/MM/DD) (Optional)';

    // Wallet create container reversal batch headers
    const WALLET_CONTAINER_LOAD_ID = "Load ID";
    const WALLET_CONTAINER_REVERSAL_AMOUNT = 'Reversal Amount (In Paise)';
    const WALLET_CONTAINER_REVERSAL_REFERENCE_ID = 'Reference ID (Optional)';
    const WALLET_CONTAINER_REVERSAL_DESCRIPTION = 'Description (Optional)';
    const WALLET_CONTAINER_REVERSAL_NOTES = 'Notes (Optional)';

    // Create bulk gift cards batch headers
    const CREATE_BULK_GIFT_CARD_PROGRAM_ID = "Program ID";
    const CREATE_BULK_GIFT_CARD_AMOUNT = 'Amount (In Paise)';
    const CREATE_BULK_GIFT_CARD_REQUEST_ID = "Request ID (Optional)";
    const CREATE_BULK_GIFT_CARD_CONTACT = 'Contact (Optional)';
    const CREATE_BULK_GIFT_CARD_BUYER_USER_ID = 'Buyer User ID (Optional)';

   // Wallet update Gift Cards expiry
    const UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_ID = "Gift Card ID";
    const UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_NUMBER = "Gift Card Number";
    const UPDATE_GIFT_CARDS_EXPIRY_EXPIRE_AT = "Expire At (YYYY-MM-DD)";
    const UPDATE_GIFT_CARDS_EXPIRY_CONTACT = "Contact";
    const UPDATE_GIFT_CARDS_EXPIRY_TICKET_LINK = "Ticket Link";
    const UPDATE_GIFT_CARDS_EXPIRY_TIMESTAMP = "Timestamp";
    const UPDATE_GIFT_CARDS_EXPIRY_SOURCE = "Source";
    const UPDATE_GIFT_CARDS_EXPIRY_REFERENCE_ID = "Reference ID (Optional)";
    const UPDATE_GIFT_CARDS_EXPIRY_NOTES = "Notes (Optional)";

    //Wallet create gift card transfers headers
    const CREATE_GIFT_CARD_TRANSFERS_SOURCE_USER_ID = "Source user_id";
    const CREATE_GIFT_CARD_TRANSFERS_DESTINATION_USER_ID = "Destination user_id";

    // GCOMS: Provide EMAILS against orderID for gift-cards delivery
    const UPLOAD_BULK_EMAIL_ORDER_ID = "Order ID";
    const UPLOAD_BULK_EMAIL_PROGRAM_ID = "Program ID";
    const UPLOAD_BULK_EMAIL_PROGRAM_NAME = "Program Name (Optional)";
    const UPLOAD_BULK_EMAIL_DENOMINATION = "Denomination (in Paisa)";
    const UPLOAD_BULK_EMAIL_EMAIL = "Email";

    // consent collection for creation of local tokens
    //input
    const CONSENT_COLLECTION_MERCHANT_ID = 'merchantId';
    const CONSENT_COLLECTION_TOKEN_ID    = 'tokenId';
    //output
    const CONSENT_COLLECTION_SUCCESS           = "success";
    const CONSENT_COLLECTION_ERROR_CODE        = "Error Code";
    const CONSENT_COLLECTION_ERROR_DESCRIPTION = "Error description";

    // update miq headers
    const UPDATE_MIQ_MERCHANT_ID = "Merchant id";
    const UPDATE_MIQ_STATUS = "Status";
    const UPDATE_MIQ_MERCHANT_NAME_BUSINESS_NAME = "Merchant Name (business name)";
    const UPDATE_MIQ_DBA_NAME_BILLING_LABEL = "DBA Name (billing label)";
    const UPDATE_MIQ_WEBSITE = "Website";
    const UPDATE_MIQ_WEBSITE_ABOUT_US = "Website About us";
    const UPDATE_MIQ_WEBSITE_TERMS_AND_CONDITIONS = "Website Terms and conditions";
    const UPDATE_MIQ_WEBSITE_CONTACT_US = "Website Contact us";
    const UPDATE_MIQ_WEBSITE_PRIVACY_POLICY = "Website Privacy Policy";
    const UPDATE_MIQ_WEBSITE_PRODUCT_PRICING = "Website Product Pricing";
    const UPDATE_MIQ_WEBSITE_REFUNDS = "Website Refunds";
    const UPDATE_MIQ_WEBSITE_CANCELLATION = "Website Cancellation";
    const UPDATE_MIQ_WEBSITE_SHIPPING_AND_DELIVERY = "Website Shipping and delivery";
    const UPDATE_MIQ_CONTACT_NAME = "Contact Name";
    const UPDATE_MIQ_CONTACT_EMAIL = "Contact Email";
    const UPDATE_MIQ_TRANSACTIONS_REPORT_EMAIL_ID = "Transactions Report Email-id";
    const UPDATE_MIQ_ADDRESS = "Address";
    const UPDATE_MIQ_CITY = "City";
    const UPDATE_MIQ_PIN_CODE = "PIN Code";
    const UPDATE_MIQ_STATE = "State";
    const UPDATE_MIQ_CONTACT_NUMBER = "Contact Number";
    const UPDATE_MIQ_BUSINESS_TYPE = "Business Type";
    const UPDATE_MIQ_CIN = "CIN";
    const UPDATE_MIQ_BUSINESS_PAN = "Business PAN";
    const UPDATE_MIQ_BUSINESS_NAME = "Business Name";
    const UPDATE_MIQ_AUTHORISED_SIGNATURE_PAN = "Authorised Signatory PAN";
    const UPDATE_MIQ_PAN_OWNER_NAME = "PAN Owner's name";
    const UPDATE_MIQ_BUSINESS_CATEGORY = "Business Category";
    const UPDATE_MIQ_SUB_CATEGORY = "Sub Category";
    const UPDATE_MIQ_GSTIN = "GSTIN";
    const UPDATE_MIQ_BUSINESS_DESCRIPTION = "Business Description";
    const UPDATE_MIQ_ESTD_DATE = "ESTD Date";
    const UPDATE_MIQ_FEE_MODEL = "Fee Model";
    const UPDATE_MIQ_NET_BANKING_FEE_TYPE = "Netbanking Fee Type";
    const UPDATE_MIQ_NET_BANKING_FEE_BEARER = "Net Banking Fee bearer";
    const UPDATE_MIQ_AXIS_NET_BANKING = "Axis NetBanking";
    const UPDATE_MIQ_HDFC_NET_BANKING = "HDFC NetBanking";
    const UPDATE_MIQ_ICICI_NET_BANKING = "ICICI Netbanking";
    const UPDATE_MIQ_SBI_NET_BANKING = "SBI NetBanking";
    const UPDATE_MIQ_YES_NET_BANKING = "Yes Netbanking";
    const UPDATE_MIQ_NET_BANKING_ANY = "NetBanking (Any)";
    const UPDATE_MIQ_DEBIT_CARD_FEE_TYPE = "Debit Card Fee Type";
    const UPDATE_MIQ_DEBIT_CARD_FEE_BEARER = "Debit Card Fee bearer";
    const UPDATE_MIQ_DEBIT_CARD_0_TO_2K = "Debit Card  0<2K";
    const UPDATE_MIQ_DEBIT_CARD_2K_TO_1CR = "Debit Card  2K<1Cr";
    const UPDATE_MIQ_RUPAY_FEE_TYPE = "Rupay Fee Type";
    const UPDATE_MIQ_RUPAY_FEE_BEARER = "Rupay Fee bearer";
    const UPDATE_MIQ_RUPAY_0_TO_2K = "Rupay 0<2K";
    const UPDATE_MIQ_RUPAY_2K_TO_1CR = "Rupay 2K<1cr";
    const UPDATE_MIQ_UPI_FEE_TYPE = "UPI Fee Type";
    const UPDATE_MIQ_UPI_FEE_BEARER = "UPI Fee bearer";
    const UPDATE_MIQ_UPI = "UPI";
    const UPDATE_MIQ_WALLETS_FEE_TYPE = "Wallets Fee Type";
    const UPDATE_MIQ_WALLETS_FEE_BEARER = "Wallets Fee bearer";
    const UPDATE_MIQ_WALLETS_FREE_CHARGE = "Wallets (Freecharge)";
    const UPDATE_MIQ_WALLETS_ANY = "Wallets (Any)";
    const UPDATE_MIQ_CREDIT_CARD_FEE_TYPE = "Credit Card Fee Type";
    const UPDATE_MIQ_CREDIT_CARD_FEE_BEARER = "Credit Card Fee bearer";
    const UPDATE_MIQ_CREDIT_CARD_0_TO_2K = "Credit Card  0<2K";
    const UPDATE_MIQ_CREDIT_CARD_2K_TO_1CR = "Credit Card 2K<1Cr";
    const UPDATE_MIQ_INTERNATIONAL = "International";
    const UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_TYPE = "International Cards Fee Type";
    const UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_BEARER = "International Cards Fee bearer";
    const UPDATE_MIQ_INTERNATIONAL_CARDS = "International Cards";
    const UPDATE_MIQ_BUSINESS_FEE_TYPE = "Business Fee Type";
    const UPDATE_MIQ_BUSINESS_FEE_BEARER = "Business Fee bearer";
    const UPDATE_MIQ_BUSINESS = "Business";
    const UPDATE_MIQ_BANK_ACCOUNT_NUMBER = "Bank Account Number";
    const UPDATE_MIQ_BENEFICIARY_NAME = "Beneficiary Name";
    const UPDATE_MIQ_BRANCH_IFSC_CODE = "Branch IFSC Code";
    const UPDATE_MIQ_MODE = "Mode";
    const UPDATE_MIQ_GATEWAY_A = "Gateway (A)";
    const UPDATE_MIQ_GATEWAY_ACQUIRER_A = "Gateway Acquirer (A)";
    const UPDATE_MIQ_TERMINAL_CATEGORY_A = "Terminal Category (A)";
    const UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_A = "Existing Gateway Merchant ID (A)";
    const UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_A = "New Gateway Merchant ID (A)";
    const UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_A = "Gateway Merchant ID 2 (A)";
    const UPDATE_MIQ_GATEWAY_ACCESS_CODE_A = "Gateway Access Code (A)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_A = "Gateway Secure Secret (A)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_A = "Gateway Secure Secret 2 (A)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_ID_A = "Gateway Terminal ID (A)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_A = "Gateway Terminal Password (A)";
    const UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_A = "Confirm Gateway Terminal Password (A)";
    const UPDATE_MIQ_TERMINAL_MODE_A = "Terminal Mode (A)";
    const UPDATE_MIQ_GATEWAY_RECON_PASSWORD_A = "Gateway Recon Password (A)";
    const UPDATE_MIQ_CARDS_TERMINAL_A = "Cards terminal (A)";
    const UPDATE_MIQ_UPI_TERMINAL_A = "UPI (A)";
    const UPDATE_MIQ_BANK_TRANSFER_A = "Bank Transfer (A)";
    const UPDATE_MIQ_OFFLINE_A = "Offline (A)";
    const UPDATE_MIQ_TYPES_A = "Types (A)";
    const UPDATE_MIQ_CAPABILITY_A = "Capability (A)";
    const UPDATE_MIQ_CURRENCY_A = "Currency (A)";
    const UPDATE_MIQ_GATEWAY_B = "Gateway (B)";
    const UPDATE_MIQ_GATEWAY_ACQUIRER_B = "Gateway Acquirer (B)";
    const UPDATE_MIQ_TERMINAL_CATEGORY_B = "Terminal Category (B)";
    const UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_B = "Existing Gateway Merchant ID (B)";
    const UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_B = "New Gateway Merchant ID (B)";
    const UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_B = "Gateway Merchant ID 2 (B)";
    const UPDATE_MIQ_GATEWAY_ACCESS_CODE_B = "Gateway Access Code (B)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_B = "Gateway Secure Secret (B)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_B = "Gateway Secure Secret 2 (B)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_ID_B = "Gateway Terminal ID (B)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_B = "Gateway Terminal Password (B)";
    const UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_B = "Confirm Gateway Terminal Password (B)";
    const UPDATE_MIQ_TERMINAL_MODE_B = "Terminal Mode (B)";
    const UPDATE_MIQ_GATEWAY_RECON_PASSWORD_B = "Gateway Recon Password (B)";
    const UPDATE_MIQ_CARDS_TERMINAL_B = "Cards terminal (B)";
    const UPDATE_MIQ_UPI_TERMINAL_B  = "UPI (B)";
    const UPDATE_MIQ_BANK_TRANSFER_B = "Bank Transfer (B)";
    const UPDATE_MIQ_OFFLINE_B = "Offline (B)";
    const UPDATE_MIQ_TYPES_B = "Types (B)";
    const UPDATE_MIQ_CAPABILITY_B = "Capability (B)";
    const UPDATE_MIQ_CURRENCY_B = "Currency (B)";
    const UPDATE_MIQ_GATEWAY_C = "Gateway (C)";
    const UPDATE_MIQ_GATEWAY_ACQUIRER_C = "Gateway Acquirer (C)";
    const UPDATE_MIQ_TERMINAL_CATEGORY_C = "Terminal Category (C)";
    const UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_C = "Existing Gateway Merchant ID (C)";
    const UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_C = "New Gateway Merchant ID (C)";
    const UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_C = "Gateway Merchant ID 2 (C)";
    const UPDATE_MIQ_GATEWAY_ACCESS_CODE_C = "Gateway Access Code (C)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_C = "Gateway Secure Secret (C)";
    const UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_C = "Gateway Secure Secret 2 (C)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_ID_C = "Gateway Terminal ID (C)";
    const UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_C = "Gateway Terminal Password (C)";
    const UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_C = "Confirm Gateway Terminal Password (C)";
    const UPDATE_MIQ_TERMINAL_MODE_C = "Terminal Mode (C)";
    const UPDATE_MIQ_GATEWAY_RECON_PASSWORD_C = "Gateway Recon Password (C)";
    const UPDATE_MIQ_CARDS_TERMINAL_C = "Cards terminal (C)";
    const UPDATE_MIQ_UPI_TERMINAL_C  = "UPI (C)";
    const UPDATE_MIQ_BANK_TRANSFER_C = "Bank Transfer (C)";
    const UPDATE_MIQ_OFFLINE_C = "Offline (C)";
    const UPDATE_MIQ_TYPES_C = "Types (C)";
    const UPDATE_MIQ_CAPABILITY_C = "Capability (C)";
    const UPDATE_MIQ_CURRENCY_C = "Currency (C)";

    //headers for tokenisation terminals onboarding
    const TERMINAL_BATCH_CREATION_TYPE = "terminal_type";
    const TERMINAL_BATCH_CREATION_GATEWAY = "terminal_gateway";
    const TERMINAL_BATCH_CREATION_GATEWAY_TERMINAL_ID = "gateway_terminal_id";
    const TERMINAL_BATCH_CREATION_PROVIDER_NAME = "provider_name";
    const TERMINAL_BATCH_CREATION_PROVIDER_TYPE = "provider_type";
    const ORG_ID = "org_id";

    // headers for bvs bulk kyc verification
    const BVS_BULK_KYC_VERIFICATION_DOCUMENT = 'Document';
    const BVS_BULK_KYC_VERIFICATION_ENRICHMENT_TYPE = 'Enrichment Type';
    const BVS_BULK_KYC_VERIFICATION_ACCOUNT_ID = 'Account Id';

    // S2P Groups Onboarding headers
    const S2P_GROUPS_ONBOARDING_MERCHANT_ID = "MID (Mandatory) 14 character merchant ID";
    const S2P_GROUPS_ONBOARDING_GROUP_TYPE_ID = "Group Type ID (Mandatory) ID of the group type created for the MID";
    const S2P_GROUPS_ONBOARDING_NAME = "Group Name (Mandatory) Name of group to be added within the group type";
    const S2P_GROUPS_ONBOARDING_GROUP_HEAD_EMAIL = "Head of group - email ID (Optional) Email ID of head of group (they should already be added to the MID)";
    const S2P_GROUPS_ONBOARDING_REFERENCE_ID = "Reference ID (Optional) Any reference ID used internally";
    const S2P_GROUPS_ONBOARDING_DESCRIPTION = "Description (Optional) Any reference details used internally";

    // S2P Users Onboarding headers
    const S2P_USERS_ONBOARDING_MERCHANT_ID = "MID (Mandatory) 14 character merchant ID";
    const S2P_USERS_ONBOARDING_FIRST_NAME = "First Name (Mandatory) Team Member first name";
    const S2P_USERS_ONBOARDING_LAST_NAME = "Last Name (Mandatory) Team member last name";
    const S2P_USERS_ONBOARDING_EMAIL_ID = "Email ID (Mandatory) Team member work email ID";
    const S2P_USERS_ONBOARDING_USER_ROLE = "User Role (Mandatory) User role defining access & permissions on RazorpayX (role should already be present for the MID)";
    const S2P_USERS_ONBOARDING_DESIGNATION = "Designation (Optional) Team member internal designation";
    const S2P_USERS_ONBOARDING_EMPLOYEE_ID = "Employee ID (Optional) Team member internal reference ID";
    const S2P_USERS_ONBOARDING_MANAGER_EMAIL_ID = "Reporting Manager Email ID (Optional) Team member reporting manager email ID (they should already be added to the MID)";
    const S2P_USERS_ONBOARDING_GROUP_IDS = "Groups (Optional) User groups that the team member is a part of (groups should already be added to the MID)";

    // vendor onboarding headers
    const VENDOR_ONBOARDING_NAME = "Vendor Name (Mandatory) Special characters not supported";
    const VENDOR_ONBOARDING_EMAIL = "Vendor Email (Mandatory) Please enter the primary email account of the vendor. For example gaurav.kumar@example.com.";
    const VENDOR_ONBOARDING_PHONE = "Vendor Phone Number (Mandatory) Please enter the primary phone number of the vendor.  For example enter 8809077840 rather than +91 8809077840";
    const VENDOR_ONBOARDING_VERIFICATION_MODE = "Mode of Vendor Verification(Mandatory) Please enter 'Self' if vendor is KYC by you and 'RazorpayX Verification' if you want to empanel the vendor via RazorpayX";
    const VENDOR_ONBOARDING_PORTAL_ACCESS = "Vendor Portal Access(Mandatory) Please enter Yes if you want to give your vendors access to the vendor portal and No to not.";
    const VENDOR_ONBOARDING_CIN = "Vendor CIN Number (Optional) Typically 25 character long";
    const VENDOR_ONBOARDING_MSME = "Vendor MSME Number (Optional) Typically 19 digits long";
    const VENDOR_ONBOARDING_GST = "Vendor GST Number (Optional) Typically 15 character long";
    const VENDOR_ONBOARDING_PAN = "Vendor PAN Number (Optional) Typically 10 character long";
    const VENDOR_ONBOARDING_TAN = "Vendor TAN Number (Optional) Typically 10 character long";
    const VENDOR_ONBOARDING_TDS_CATEGORY = "Default Vendor TDS category (Optional) Please do not add space before or after.";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_TYPE = "Fund Account Type (Optional) Here it will either be bank_account vpa.";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_NAME = "Fund Account Name (Optional) The official name associated with your vendor's fund account";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_IFSC = "IFSC Code (Optional) 11 digit code of the beneficiary's bank account. Eg. HDFC0004277";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_NUMBER = "Vendor Account Number (Optional) Typically 9-18 digits.";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_VPA = "Vendor VPA (Optional) Vendor's virtual payment address. For example gauravkumar@exampleupi.";
    const VENDOR_ONBOARDING_FUND_ACCOUNT_PROVIDER = "Vendor Fund Account Provider (Optional) Applicable only for fund account type wallet. For example Amazon wallet . Not required for bank_account";
    const VENDOR_ONBOARDING_CONTACT_REFERENCE_ID = "Contact Reference Id (Optional) Contact ID in RazorpayX. Do not fill unless the contact already exists in Razorpay";
    const VENDOR_ONBOARDING_NOTES = "Notes";

    const VENDOR_ONBOARDING_COMPANY_REGISTERED_NAME = "Company Registered Name (Optional) Please enter the company registered name as per the government records. For example: ABC Private Limited ";

    const VENDOR_ONBOARDING_COMPANY_POC_NAME = "Company POC Name (Optional) Please enter a company POC name. For example: John Doe ";

    const VENDOR_ONBOARDING_COMPANY_BUSINESS_TYPE = "Company Business Type (Optional) Please enter the business type. For example: Private Limited";

    const VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS = "Company Registered Address  (Optional) Please enter company registered full address. For example: ABC Towers，Floor 2，Koramangala，Bangalore，Karnataka 560030";

    const VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_STATE = "Company Registered Address State (Optional) Please enter the state in which the company is registered. For example: Karnataka";

    const VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_COUNTRY = "Company Registered Address Country (Optional) Please enter the country in which the company is registered. For example: India";

    const VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_PINCODE = "Company Registered Address Pincode (Optional) Please enter a valid pincode in which the company is registered. For example: 560103";

    const VENDOR_ONBOARDING_ALTERNATE_PHONE = "Alternate Phone Number (Optional) Please enter an alternate phone number for your vendor. For example: please enter 7092837461 rather than +91 7092837461";

    const VENDOR_ONBOARDING_ALTERNATE_EMAIL = "Alternate Email ID (Optional) Please enter an alternate email ID for your vendor. For example: john.doe@gmail.com ";

    const VENDOR_ONBOARDING_VENDOR_CONTACT_POC_NAME = "Vendor Contact POC Name (Optional) Please enter a vendor POC name. For example: John Doe";

    const VENDOR_ONBOARDING_VENDOR_CONTACT_POC_PHONE = "Vendor Contact POC Phone number (Optional) Please enter an vendor POC phone number. For example: please enter 7092837461 rather than +91 7092837461";

    const VENDOR_ONBOARDING_VENDOR_CONTACT_POC_EMAIL = "Vendor Contact POC Email ID (Optional) Please enter an vendor POC email ID for your vendor. For example: john.doe@gmail.com ";

    const VENDOR_ONBOARDING_INTERNAL_POC_EMAIL_CC = "Internal POC Email ID Please enter an email ID of an internal POC if you want them in CC for all vendor-related communications. If multiple email IDs，please use comma separators.";

    const VENDOR_ONBOARDING_CUSTOM_FIELDS = "Custom Fields (Optional) Please enter any custom fields you want to add as part of this vendor. Enter in format Title:Value with multiple fields in new line in the SAME cell. For example: date of incorporation:10/09/2000";


    // jammu and kashmir onboarding headers

    const JK_REQUEST_ID = "RequestID";

    const JK_MERCHANT_NAME = "MerchantName";

    const JK_VPA = "VPA";

    const JK_QR_STRING = "QRString";

    const JK_LANGUAGE = "Language";

    const JK_BRANCH_ADDRESS_FOR_DELIVERY = "Branch Address for delivery";

    const JK_CITY = "City";

    const JK_STATE = "State";

    const JK_PINCODE = "Pincode";

    const JK_BRANCH_MOBILE_NO = "Branch MobileNo";

    const JK_MERCHANT_PHONE_SUPPORT = "Merchant Phone Support";

    const JK_ONBOARDING_DATE = "Onboarding Date";

    const JK_ONBOARDING_TIME = "OnBoarding Time";

    const JK_REQUEST_STATUS = "Request Status";
    const JK_MERCHANT_EMAIL_ID = "Merchant EmailId (Optional)";

    const JK_AIRWAY_BILLNO = "AIRWAY BILLNO";

    const JK_INSTALLATION_STATUS = "INSTALLATIONSTATUS";

    const JK_INSTALLATION_COMMENTS = "INSTALLATIONCOMMENTS";

    const JK_DATE_OF_INSTALLATION = "DATEOFINSTALLATION";

    const JK_REJECTION_REASON  = "REJECTIONREASON";

    const JK_MERCHANT_RECIEVED_STATE = "Merchant Recieved Date";

    const JK_DELIVERY_STATUS = "Delivery Status";

    const JK_REMARKS = "Remarks (Any)";

    const JK_RAZORPAY_MID = "Razorpay MID";

    const JK_MODIFIED_QR_STRING = "Modified QR string";

    // headers for IDFC fund loading
    const IDFC_ECOLLECT_UTR_NUMBER = 'txnRefNumber';
    const IDFC_ECOLLECT_SENDER_IFSC = 'remitterIfscCode';
    const IDFC_ECOLLECT_SENDER_ACCOUNT_NUMBER = 'remitterAccountNumber';
    const IDFC_ECOLLECT_SENDER_NAME = 'remitterName';
    const IDFC_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER = 'VAN';
    const IDFC_ECOLLECT_AMOUNT = 'txnAmount';
    const IDFC_ECOLLECT_TRANSACTION_DATE = 'Trn TimeStamp';
    const IDFC_ECOLLECT_MODE = 'product Code';

    const HDFC_MECODE = "MECODE";
    const HDFC_TID = "TID";
    const HDFC_LEGAL_NAME = "LEGAL NAME";
    const HDFC_DBA_NAME = "DBA NAME";
    const HDFC_ADDRESS = "ADDRESS";
    const HDFC_CITY = "CITY";
    const HDFC_PIN = "PIN";
    const HDFC_TEL = "TEL";
    const HDFC_PERSON = "PERSON";
    const HDFC_MPR = "MPR";
    const HDFC_E_DATE = "E_DATE";
    const HDFC_INF_RATE = "INF_RATE";
    const HDFC_INF_AMOUNT = "INF_AMOUNT";
    const HDFC_I_FLAG = "I_FLAG";
    const HDFC_ACCNO = "ACCNO";
    const HDFC_ACCOUNT_NO2 = "ACCOUNT NO2";
    const HDFC_DISCNT = "DISCNT";
    const HDFC_FOREIGN_FLAG = "FOREIGN_FLAG";
    const HDFC_FOREIGN_COMM = "FOREIGN_COMM";
    const HDFC_DCC_REIMB_PERCENT = "DCC Reimb %";
    const HDFC_DCC_COMM_RATE = "DCC Comm Rate";
    const HDFC_PP_RATE = "PP RATE";
    const HDFC_DINERS_FLAG = "DINERS FLAG";
    const HDFC_DINERS_COMM = "DINERS COMM";
    const HDFC_DINERSCOMM_ON = "DINERSCOMM_ON";
    const HDFC_RUPAY_FLAG = "RUPAY FLAG";
    const HDFC_ANNUAL_TURNOVER = "ANNUAL_TURNOVER";
    const HDFC_DD_COMM_SLB1_ABOVE2K = "DD COMM SLB1 ABOVE2K";
    const HDFC_DD_COMM_SLB2_ABOVE2K = "DD COMM SLB2 ABOVE2K";
    const HDFC_QR_DD_COMM_SLB1_ABOVE2K = "QR DD COMM SLB1 ABOVE2K";
    const HDFC_QR_DD_COMM_SLB2_ABOVE2K = "QR DD COMM SLB2 ABOVE2K";
    const HDFC_BBP_FLAG = "BBP FLAG";
    const HDFC_CLASSIC_ONUS_RATE = "CLASSIC ONUS RATE";
    const HDFC_PREMIUM_ONUS_RATE = "PREMIUM ONUS RATE";
    const HDFC_RENTAL = "RENTAL";
    const HDFC_SUBVENTION_FLAG = "SUBVENTION FLAG";
    const HDFC_ONUS_SUBVENTION = "ONUS SUBVENTION";
    const HDFC_OFFUS_SUBVENTION = "OFFUS SUBVENTION";
    const HDFC_RENTALS_SUBVENTION = "RENTALS SUBVENTION ";
    const HDFC_TRANSACTION_SUBVENTION = "TRANSACTION SUBVENTION";
    const HDFC_AMC_FOR_N_YRS = "AMC FOR N YRS";
    const HDFC_AMC_AMT = "AMC AMT";
    const HDFC_INT_FEES = "INT FEES";
    const HDFC_DINERS_COMM_SUBVENTION = "DINERS COMM SUBVENTION";
    const HDFC_DINERS_COMM_ONUS_SUBVENTION = "DINERS COMM ONUS SUBVENTION";
    const HDFC_DEPARTMENT_SUBVENTION = "DEPARTMENT SUBVENTION";
    const HDFC_BRANCH_CODE_SUBVENTION = "BRANCH CODE SUBVENTION";
    const HDFC_PLOUGH_BACK_RATE = "PLOUGH BACK RATE";
    const HDFC_PLOUGH_BACK_FLAG = "PLOUGH BACK FLAG";
    const HDFC_MCC = "MCC";
    const HDFC_MERCHANT_TYPE = "MERCHANT TYPE";
    const HDFC_BRANCH = "BRANCH";
    const HDFC_REGION = "REGION";
    const HDFC_UBSNO = "UBSNO";
    const HDFC_C_CODE = "C_CODE";
    const HDFC_PRODUCT = "PRODUCT";
    const HDFC_MAPFLAG = "MAPFLAG";
    const HDFC_STATUS = "STATUS";
    const HDFC_STATUS_DATE = "STATUS_DATE";
    const HDFC_TM_EMVFLAG = "TM_EMVFLAG";
    const HDFC_ACTIVEDEACTIVESTATUS = "ACTIVEDEACTIVESTATUS";
    const HDFC_DEACTIVATION_DATE = "DEACTIVATION_DATE";
    const HDFC_REACTIVATIONSTATUS = "REACTIVATIONSTATUS";
    const HDFC_REACTIVATION_DATE = "REACTIVATION_DATE";
    const HDFC_FAX_SETUP_FLAG = "FAX SETUP FLAG";
    const HDFC_CUG_FLAG = "CUG Flag";
    const HDFC_TCOMM = "TComm";
    const HDFC_TM_UTILFLG = "TM_UTILFLG";
    const HDFC_ME_XTRACOM = "ME_XTRACOM";
    const HDFC_SERVICE_TAX = "SERVICE_TAX";
    const HDFC_ME_REFUND = "Me Refund";
    const HDFC_DSA_CODE = "DSA Code";
    const HDFC_LTS_CODE = "LTS Code";
    const HDFC_LTS_DATE = "LTS Date";
    const HDFC_SERV_TAX = "Serv Tax";
    const HDFC_SBCESS = "SBcess";
    const HDFC_KKCESS = "KKCess";
    const HDFC_TXN_COMMN = "Txn Commn";
    const HDFC_WEBSITE = "Website";
    const HDFC_DEFERED_FLAG = "Defered Flag";
    const HDFC_DEFERED_DAYS = "Defered Days";
    const HDFC_DO_NOT_CALL = "Do Not Call";
    const HDFC_TERM_CURRENCY = "TERM_CURRENCY";
    const HDFC_PAY_CURRENCY = "PAY_CURRENCY";
    const HDFC_SETTL_CURRENCY = "SETTL_CURRENCY";
    const HDFC_EEFC_ACCOUNT = "EEFC_ACCOUNT";
    const HDFC_MARKUP = "MARKUP";
    const HDFC_USER_CODE = "User Code";
    const HDFC_TERMINAL_CURRENCY = "TERMINAL CURRENCY";
    const HDFC_ME_CATEGORY = "ME CATEGORY";
    const HDFC_SERV_CHARGE_FLAG = "SERV CHARGE FLAG";
    const HDFC_SERV_CHARGE_AMT = "SERV CHARGE AMT";
    const HDFC_AMC = "AMC";
    const HDFC_INSTALLATION_FEE = "INSTALLATION FEE";
    const HDFC_STATE_NAME = "STATE NAME";
    const HDFC_HBL_LEAD_CON_CODE = "HBL LEAD CON CODE";
    const HDFC_HBL_LEAD_GEN_CODE = "HBL LEAD GEN CODE ";
    const HDFC_DSA_CODE1 = "DSA CODE1";
    const HDFC_MPR_EMAILFLAG = "MPR EMAILFLAG";
    const HDFC_MPR_EMAILID = "MPR EMAILID";
    const HDFC_WALMARTTID = "WALMARTTID";
    const HDFC_COP_REIMB_FLAG = "COP REIMB FLAG";
    const HDFC_COP_REIMB_AMOUNT = "COP REIMB AMOUNT";
    const HDFC_COP_REIMB_PER = "COP REIMB PER";
    const HDFC_DCC_REIMB_FLAG = "DCC REIMB FLAG";
    const HDFC_DCC_REIMB_RATE = "DCC REIMB RATE ";
    const HDFC_ME_PAYMENT = "ME_PAYMENT";
    const HDFC_ME_PAYMODE = "ME_PAYMODE";
    const HDFC_ME_MPRTYPE = "ME_MPRTYPE";
    const HDFC_ME_MPRCAT = "ME_MPRCAT";
    const HDFC_ME_DCMFLAG = "ME_DCMFLAG";
    const HDFC_ME_DCMRATE = "ME_DCMRATE";
    const HDFC_ONUS_DD_COMM_SLB1_ABOVE2K = "ONUS DD COMM SLB1 ABOVE2K";
    const HDFC_ONUS_DD_COMM_SLB2_ABOVE2K = "ONUS DD COMM SLB2 ABOVE2K";
    const HDFC_MP_FLAG = "MP_FLAG";
    const HDFC_MP_TXN_AMOUNT = "MP_TXN_AMOUNT";
    const HDFC_MP_AMOUNT1_PERCENTAGE = "MP_AMOUNT1_PERCENTAGE";
    const HDFC_MP_AMOUNT2_PERCENTAGE = "MP_AMOUNT2_PERCENTAGE";
    const HDFC_MP_AMOUNT_ONUS_PERCENTAGE = "MP_AMOUNT_ONUS_PERCENTAGE";
    const HDFC_MP_FLAT_AMOUNT1 = "MP_FLAT_AMOUNT1";
    const HDFC_MP_FLAT_AMOUNT2 = "MP_FLAT_AMOUNT2";
    const HDFC_MP_FLAT_AMOUNT_ONUS = "MP_FLAT_AMOUNT_ONUS";
    const HDFC_CONVENIENCE_FLAG = "CONVENIENCE_FLAG";
    const HDFC_CONVENIENCE_RATE = "CONVENIENCE_RATE";
    const HDFC_CONVENIENCE_FLAG1 = "CONVENIENCE_FLAG1";
    const HDFC_COMMERCIAL_FLAG = "COMMERCIAL_FLAG";
    const HDFC_CMRCL_ONUS_RATE = "CMRCL_ONUS_RATE";
    const HDFC_APPLICATION_NUMBER = "APPLICATION NUMBER";
    const HDFC_Z_CATEOGRY = "Z CATEOGRY";
    const HDFC_TERMINAL_INDICATOR_FLAG = "TERMINAL INDICATOR FLAG";
    const HDFC_FIXED_RATE_FLAG = "FIXED RATE FLAG";
    const HDFC_EMI_PAYBACK_PERCENTAGE = "EMI PAYBACK %AGE";
    const HDFC_CUSTOMIZED_MPR_TYPE = "CUSTOMIZED MPR TYPE";
    const HDFC_MVISA_FLAG = "MVISA_FLAG";
    const HDFC_REG_TEL1 = "REG_TEL1";
    const HDFC_TEL2 = "TEL2";
    const HDFC_TEL3 = "TEL3";
    const HDFC_EMAIL_ID = "EMAIL_ID";
    const HDFC_AMC_FEES_FOR_POS = "AMC FEES FOR POS";
    const HDFC_AMC_DATE_FOR_POS = "AMC DATE FOR POS";
    const HDFC_PARENT_ME_CODE = "PARENT ME CODE";
    const HDFC_APPROVER_NAME_PAYZAP = "APPROVER_NAME_PAYZAP";
    const HDFC_DRIVING_LICENCE_NO1_PAYZAP = "DRIVING_LICENCE_NO1_PAYZAP";
    const HDFC_BRANCHCODE = "BRANCHCODE";
    const HDFC_RENTAL_FREQUENCY_FLAG = "RENTAL FREQUENCY FLAG";
    const HDFC_GSTN_UBS = "GSTN UBS";
    const HDFC_GSTN_UBS_STATE = "GSTN UBS STATE";
    const HDFC_GSTN_UBS_START_DATE = "GSTN UBS START DATE";
    const HDFC_GSTN_UBS_EXPIRY_DATE = "GSTN UBS EXPIRY DATE";
    const HDFC_GSTN_CURRAC = "GSTN CURRAC";
    const HDFC_GSTN_CURRAC_STATE = "GSTN CURRAC STATE ";
    const HDFC_GSTN_CURRAC_START_DATE = "GSTN CURRAC START DATE";
    const HDFC_GSTN_CURRAC_EXPIRY_DATE = "GSTN CURRAC EXPIRY DATE";
    const HDFC_GSTN_ACCOUNT_NO2_ACC = "GSTN ACCOUNT NO2 ACC";
    const HDFC_GSTN_ACCOUNT_NO2_STATE = "GSTN ACCOUNT NO2  STATE";
    const HDFC_GSTN_ACCOUNT_NO2_START_DATE = "GSTN ACCOUNT NO2 START DATE";
    const HDFC_GSTN_ACCOUNT_NO2_EXPIRY_DATE = "GSTN ACCOUNT NO2 EXPIRY DATE";
    const HDFC_GSTN_EEFC_ACC = "GSTN EEFC ACC";
    const HDFC_GSTN_EEFC_STATE = "GSTN EEFC STATE";
    const HDFC_GSTN_EEFC_START_DATE = "GSTN EEFC START DATE";
    const HDFC_GSTN_EEFC_EXPIRY_DATE = "GSTN EEFC EXPIRY DATE";
    const HDFC_CB_DEBIT_PER = "CB_DEBIT_PER";
    const HDFC_CB_AMOUNT = "CB_AMOUNT";
    const HDFC_CB_CASHBACK = "CB_CASHBACK";
    const HDFC_EURONET_RATE = "EURONET RATE";
    const HDFC_ARC_DATE = "ARC Date";
    const HDFC_RBI_RATE_EXEMPT_FLAG = "RBI_RATE_EXEMPT_FLAG";
    const HDFC_INTERCHANGE_FLAG = "INTERCHANGE_FLAG";
    const HDFC_INTERCHANGE_PERCENT = "INTERCHANGE_PERCENT";
    const HDFC_INTERCHANGE_FLAT_AMT = "INTERCHANGE_FLAT_AMT";
    const HDFC_AGGREGATOR_FLAG = "AGGREGATOR_FLAG";
    const HDFC_GAS_ID = "GAS_ID";
    const HDFC_BQ_AGGR_FLAG = "BQ_AGGR_FLAG";
    const HDFC_BQ_AGGR_ID = "BQ_AGGR_ID";
    const HDFC_TM_VASPARTNER = "TM_VASPARTNER";
    const HDFC_ACCOUNT_TYPE = "ACCOUNT_TYPE";
    const HDFC_GENDER = "GENDER";
    const HDFC_BBP_MDR_TYPE = "BBP MDR TYPE";
    const HDFC_PREMIUM_ONUS_AMT = "Premium ONUS Amt";
    const HDFC_PREMIUM_OFFUS_RATE = "Premium OFFUS Rate";
    const HDFC_PREMIUM_OFFUS_AMT = "Premium OFFUS Amt";
    const HDFC_CLASSIC_ONUS_AMT = "Classic ONUS Amt";
    const HDFC_CLASSIC_OFFUS_RATE = "Classic OFFUS Rate";
    const HDFC_CLASSIC_OFFUS_AMT = "Classic OFFUS Amt";
    const HDFC_SUPER_PREMIUM_ONUS_RATE = "Super Premium ONUS Rate";
    const HDFC_SUPER_PREMIUM_ONUS_AMT = "Super Premium ONUS Amt";
    const HDFC_SUPER_PREMIUM_OFFUS_RATE = "Super Premium OFFUS Rate";
    const HDFC_SUPER_PREMIUM_OFFUS_AMT = "Super Premium OFFUS Amt";
    const HDFC_COMMERCIAL_MDR_TYPE = "COMMERCIAL MDR TYPE";
    const HDFC_COMMERCIAL_ONUS_AMT = "Commercial ONUS Amt";
    const HDFC_COMMERCIAL_OFFUS_RATE = "Commercial OFFUS Rate";
    const HDFC_COMMERCIAL_OFFUS_AMT = "Commercial OFFUS Amt";
    const HDFC_SETTLEMENT_SUBVENTION = "Settlement Subvention";
    const HDFC_SETTLEMENT_SUBVENTION_AMT = "Settlement Subvention Amt";
    const HDFC_PHYSICAL_MPR_SUBVENTION = "Physical MPR subvention";
    const HDFC_PHYSICAL_MPR_SUBVENTION_AMT = "Physical MPR Subvention Amt";
    const HDFC_PAGF_SUB_FOR_NEFTCHEQUE_MER = "PAGF Sub FOR NEFTCheque Mer";
    const HDFC_PAGF_SUB_AMT_NEFTCHEQUE_MER = "PAGF Sub AMT NEFTCheque Mer";
    const HDFC_REFUND_REQUEST_SUBVENTION = "Refund Request Subvention";
    const HDFC_REFUND_REQUEST_SUBVENTION_AMT = "Refund Request Subvention Amt";
    const HDFC_GOOD_FAITH_SUBVENTION = "Good Faith Subvention";
    const HDFC_GOOD_FAITH_SUBVENTION_AMT = "Good Faith Subvention Amt";
    const HDFC_PHY_MPR_CHARGES_ADHOC_SUB = "Phy MPR Charges (Adhoc)Sub";
    const HDFC_PHY_MPR_CHARGES_ADHOC_SUB_AMT = "Phy MPR Charges (Adhoc)Sub Amt";
    const HDFC_CBRR_SUBVENTION = "CBRR Subvention";
    const HDFC_CBRR_SUBVENTION_AMT = "CBRR Subvention Amt";
    const HDFC_LUC_SUBVENTION = "LUC Subvention";
    const HDFC_LUC_SUBVENTION_PER = "LUC Subvention Per";
    const HDFC_SERVICE_CHARGE_SUBVENTION = "Service Charge Subvention";
    const HDFC_SERVICE_CHARGE_SUBVENTION_AMT = "Service Charge Subvention Amt";
    const HDFC_MERCHANT_CARE_PROGRAM_SUB = "Merchant Care Program Sub";
    const HDFC_MERCHANT_CARE_PROGRAM_SUB_AMT = "Merchant Care Program Sub Amt";
    const HDFC_TATKAL_CARE_SUBVENTION = "Tatkal Care Subvention";
    const HDFC_TATKAL_CARE_SUBVENTION_AMT = "Tatkal Care subvention Amt.";
    const HDFC_LATE_SETTLE_SUBVENTION_FLAG = "Late Settle subvention flag";
    const HDFC_LATE_SETTLE_SUBVENTION_PER = "Late Settle Subvention Per";
    const HDFC_AADHAR_FLAG = "AADHAR_FLAG";
    const HDFC_AADHAR_ONUS_COMM = "AADHAR_ONUS_COMM";
    const HDFC_AADHAR_OFFUS_COMM = "AADHAR_OFFUS_COMM";
    const HDFC_BIOMETRIC_RENT_FLAG = "BIOMETRIC_RENT_FLAG";
    const HDFC_BIOMETRIC_RENT = "BIOMETRIC_RENT";
    const HDFC_TERM_GROUP = "TERM GROUP";
    const HDFC_FPI_V = "FPI_V";
    const HDFC_SH_RENTAL_FRQNCY_FLAG = "SH_RENTAL_FRQNCY_FLAG";
    const HDFC_SH_RENTAL_AMT = "SH_RENTAL_AMT";
    const HDFC_PROMO_CODE1 = "PROMO_CODE1";
    const HDFC_POROMO_DATE1 = "POROMO_DATE1";
    const HDFC_TOKEN_NUMBER = "TOKEN_NUMBER";
    const HDFC_DD_COMM_UPTO2K_RPY = "DD_COMM_UPTO2K_RPY";
    const HDFC_DD_COMM_ABOVE2K_RPY = "DD_COMM_ABOVE2K_RPY";
    const HDFC_QR_DD_COMM_UPTO2K_RPY = "QR_DD_COMM_UPTO2K_RPY";
    const HDFC_QR_DD_COMM_ABOVE2K_RPY = "QR_DD_COMM_ABOVE2K_RPY";
    const HDFC_MDR_TEMPLATE = "MDR_TEMPLATE";
    const HDFC_DD_COMM_SLB1_UPTO2K = "DD COMM SLB1 UPTO2K";
    const HDFC_DD_COMM_SLB2_UPTO2K = "DD COMM SLB2 UPTO2K";
    const HDFC_QR_DD_COMM_SLB1_UPTO2K = "QR DD COMM SLB1 UPTO2K";
    const HDFC_QR_DD_COMM_SLB2_UPTO2K = "QR DD COMM SLB2 UPTO2K";
    const HDFC_ONUS_DD_COMM_SLB1_UPTO2K = "ONUS DD COMM SLB1 UPTO2K";
    const HDFC_ONUS_DD_COMM_SLB2_UPTO2K = "ONUS DD COMM SLB2 UPTO2K";
    const HDFC_DD_CAPPING_AMT_SLB1 = "DD CAPPING AMT SLB1";
    const HDFC_DD_CAPPING_AMT_SLB2 = "DD CAPPING AMT SLB2";
    const HDFC_BRANCH_EMP_CODE = "BRANCH_EMP_CODE";
    const HDFC_SOURCE_CODE = "SOURCE_CODE";
    const HDFC_ME_CRM_LEAD_NO = "ME_CRM_LEAD_NO";
    const HDFC_BASE_TID = "BASE_TID";
    const HDFC_POS_DATE = "POS DATE";
    const HDFC_TERMINAL_INDICATOR = "TERMINAL INDICATOR";
    const HDFC_DEACTIVATION_REASON_CODE = "Deactivation Reason Code";
    const HDFC_PM_FLAG = "PM_FLAG";
    const HDFC_PM_AMT = "PM_AMT";
    const HDFC_PAGF_FLAG = "PAGF_FLAG";
    const HDFC_PAGF_AMT = "PAGF_AMT";
    const HDFC_STLC_FLAG = "STLC_FLAG";
    const HDFC_STLC_AMT = "STLC_AMT";
    const HDFC_RR_FLAG = "RR_FLAG";
    const HDFC_RR_AMT = "RR_AMT";
    const HDFC_GF_FLAG = "GF_FLAG";
    const HDFC_GF_AMT = "GF_AMT";
    const HDFC_CBRR_FLAG = "CBRR_FLAG";
    const HDFC_CBRR_AMT = "CBRR_AMT";
    const HDFC_PM_ADHOC_FLAG = "PM_ADHOC_FLAG";
    const HDFC_PM_ADHOC_AMT = "PM_ADHOC_AMT";
    const HDFC_LUC_FLAG = "LUC_FLAG";
    const HDFC_SC_FLAG = "SC_FLAG";
    const HDFC_SC_AMT = "SC_AMT";
    const HDFC_MERCHANTCARE_FLAG = "MERCHANTCARE_FLAG";
    const HDFC_MERCHANTCARE_AMT = "MERCHANTCARE_AMT";
    const HDFC_TATKALCARE_FLAG = "TATKALCARE_FLAG";
    const HDFC_TATKALCARE_AMT = "TATKALCARE_AMT";
    const HDFC_SEZ_MERCHANT = "SEZ_MERCHANT";
    const HDFC_SEZ_CATEGORY = "SEZ_CATEGORY";
    const HDFC_SEZ_VALID_DATE = "SEZ_VALID_DATE";
    const HDFC_PPR_ROLL_CHG_FLAG = "PPR_ROLL_CHG_FLAG";
    const HDFC_PPR_ROLLCHG_SBV_FLAG = "PPR_ROLLCHG_SBV_FLAG";
    const HDFC_PPR_ROLLCHG_SBV_PER = "PPR_ROLLCHG_SBV_PER";
    const HDFC_MOBILE_NUMBER = "Mobile number";
    const HDFC_ENTITY_PAN_CARD = "Entity PAN Card";
    const HDFC_GIB_ACC_NO = "GIB_ACC_NO";
    const HDFC_REF1 = "REF1";
    const HDFC_GST_CONSOLIDATION_FLAG = "GST_CONSOLIDATION_FLAG";
    const HDFC_POS_DESIGNATED_BRANCH = "POS_DESIGNATED_BRANCH";
    const HDFC_POS_DEACTIVATION_FLAG = "POS_DEACTIVATION_FLAG";
    const HDFC_POS_DEACTIVATION_CHARGE = "POS_DEACTIVATION_CHARGE";
    const HDFC_VAS_PROGRAM_FLAG = "VAS_PROGRAM_FLAG";
    const HDFC_VOLUME_RENTAL_FLAG = "VOLUME_RENTAL_FLAG";
    const HDFC_ONLINE_REFUND_FLAG = "ONLINE_REFUND_FLAG";
    const HDFC_UNSECURE_TRANSACTION_FLAG = "UNSECURE_TRANSACTION_FLAG";
    const HDFC_UCIC_NAME = "UCIC_NAME";
    const HDFC_TRANSITORY_GL_FLAG = "TRANSITORY_GL_FLAG";
    const HDFC_EEFC_DEBIT_FLAG = "EEFC_DEBIT_FLAG";


    // mandatory headers for wallet account batch
    const MANDATORY_HEADERS_FOR_WALLET_ACCOUNTS = [
        Header::WALLET_ACCOUNTS_NAME,
        Header::WALLET_ACCOUNTS_EMAIL,
        Header::WALLET_ACCOUNTS_CONTACT,
        Header::WALLET_ACCOUNTS_IDENTIFICATION_ID,
        Header::WALLET_ACCOUNTS_IDENTIFICATION_TYPE,
        Header::WALLET_ACCOUNTS_PARTNER_CUSTOMER_ID,
        Header::WALLET_ACCOUNTS_DOB
    ];

    // mandatory headers for wallet loads batch
    const MANDATORY_HEADERS_FOR_WALLET_LOADS = [
        Header::WALLET_LOAD_CONTACT,
        Header::WALLET_LOAD_AMOUNT
    ];

    // mandatory headers for wallet users batch
    const MANDATORY_HEADERS_FOR_WALLET_USERS = [
        Header::WALLET_ACCOUNTS_CONTACT,
        Header::WALLET_ACCOUNTS_EMAIL,
        Header::WALLET_ACCOUNTS_PARTNER_USER_ID
    ];

    // mandatory headers for wallet container load batch
    const MANDATORY_HEADERS_FOR_WALLET_CONTAINER_LOADS = [
        Header::WALLET_CONTAINER_LOAD_USER_ID,
        Header::WALLET_CONTAINER_LOAD_PROGRAM_ID,
        Header::WALLET_CONTAINER_LOAD_AMOUNT
    ];

    // mandatory headers for wallet container reversal batch
    const MANDATORY_HEADERS_FOR_WALLET_CONTAINER_REVERSALS = [
        Header::WALLET_CONTAINER_LOAD_ID,
        Header::WALLET_CONTAINER_REVERSAL_AMOUNT
    ];

    // mandatory headers for create gift card batch
    const MANDATORY_HEADERS_FOR_CREATE_BULK_GIFT_CARDS = [
        Header::CREATE_BULK_GIFT_CARD_PROGRAM_ID,
        Header::CREATE_BULK_GIFT_CARD_AMOUNT
    ];

//     mandatory headers for create gift card transfers batch
    const MANDATORY_HEADERS_FOR_CREATE_GIFT_CARD_TRANSFERS = [
        Header::CREATE_GIFT_CARD_TRANSFERS_SOURCE_USER_ID,
        Header::CREATE_GIFT_CARD_TRANSFERS_DESTINATION_USER_ID
    ];

    // mandatory headers for email upload
    const MANDATORY_HEADERS_FOR_UPDATE_GIFT_CARDS_EXPIRY = [
        Header::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_ID,
        Header::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_NUMBER,
        Header::UPDATE_GIFT_CARDS_EXPIRY_EXPIRE_AT,
        Header::UPDATE_GIFT_CARDS_EXPIRY_CONTACT,
        Header::UPDATE_GIFT_CARDS_EXPIRY_TICKET_LINK,
        Header::UPDATE_GIFT_CARDS_EXPIRY_TIMESTAMP,
        Header::UPDATE_GIFT_CARDS_EXPIRY_SOURCE
    ];

    // mandatory headers for email upload
    const MANDATORY_HEADERS_FOR_UPLOAD_BULK_EMAILS = [
        Header::UPLOAD_BULK_EMAIL_ORDER_ID,
        Header::UPLOAD_BULK_EMAIL_PROGRAM_ID,
        Header::UPLOAD_BULK_EMAIL_DENOMINATION,
        Header::UPLOAD_BULK_EMAIL_EMAIL
    ];

    // Following is a list of columns that are mandatory headers in the fund account (contact) batch file
    const MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_FUND_ACCOUNTS = [
        Header::FUND_ACCOUNT_TYPE,
        Header::FUND_ACCOUNT_NAME,
        Header::FUND_ACCOUNT_IFSC,
        Header::FUND_ACCOUNT_NUMBER,
        Header::FUND_ACCOUNT_VPA,
        Header::CONTACT_ID,
        Header::CONTACT_TYPE,
        Header::CONTACT_NAME_2,
        Header::CONTACT_EMAIL_2,
        Header::CONTACT_MOBILE_2,
        Header::CONTACT_REFERENCE_ID,
        Header::NOTES,
    ];

    const MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_FUND_ACCOUNTS_V2 = [
        Header::FUND_ACCOUNT_TYPE,
        Header::FUND_ACCOUNT_NAME,
        Header::FUND_ACCOUNT_IFSC,
        Header::FUND_ACCOUNT_NUMBER,
        Header::FUND_BANK_ACCOUNT_TYPE,
        Header::FUND_ACCOUNT_VPA,
        Header::CONTACT_ID,
        Header::CONTACT_TYPE,
        Header::CONTACT_NAME_2,
        Header::CONTACT_EMAIL_2,
        Header::CONTACT_MOBILE_2,
        Header::CONTACT_REFERENCE_ID,
        Header::NOTES,
        Header::CONTACT_GSTIN,
        Header::CONTACT_PAN,
    ];

    // Following is a list of columns that are mandatory headers in the payout batch file
    const MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_PAYOUTS = [
        Header::RAZORPAYX_ACCOUNT_NUMBER,
        Header::PAYOUT_AMOUNT,
        Header::PAYOUT_AMOUNT_RUPEES,
        Header::PAYOUT_CURRENCY,
        Header::PAYOUT_MODE,
        Header::PAYOUT_PURPOSE,
        Header::FUND_ACCOUNT_ID,
        Header::FUND_ACCOUNT_TYPE,
        Header::FUND_ACCOUNT_NAME,
        Header::FUND_ACCOUNT_IFSC,
        Header::FUND_ACCOUNT_NUMBER,
        Header::FUND_ACCOUNT_VPA,
        Header::CONTACT_NAME_2,
    ];

    // Following is a list of columns that are mandatory headers in the payout link batch file
    const MANDATORY_HEADERS_FOR_PAYOUT_LINK_BULK = [
        Header::PAYOUT_LINK_BULK_CONTACT_NAME,
        Header::PAYOUT_LINK_BULK_CONTACT_NUMBER,
        Header::PAYOUT_LINK_BULK_CONTACT_EMAIL,
        Header::PAYOUT_LINK_BULK_PAYOUT_DESC,
        Header::CONTACT_TYPE,
        Header::PAYOUT_LINK_BULK_AMOUNT,
        Header::PAYOUT_LINK_BULK_SEND_SMS,
        Header::PAYOUT_LINK_BULK_SEND_EMAIL,
        Header::PAYOUT_PURPOSE,
    ];

    // Following is a list of columns that are mandatory headers in the raw address batch file
    const MANDATORY_HEADERS_FOR_RAW_ADDRESS_BULK = [
        Header::RAW_ADDRESS_BULK_NAME,
        Header::RAW_ADDRESS_BULK_CONTACT,
        Header::RAW_ADDRESS_BULK_CITY,
        Header::RAW_ADDRESS_BULK_LINE1,
        Header::RAW_ADDRESS_BULK_STATE,
        Header::RAW_ADDRESS_BULK_COUNTRY,
        Header::RAW_ADDRESS_BULK_ZIPCODE,
    ];

    // Following is a list of columns that are mandatory headers in the fulfillment order batch file
    const MANDATORY_HEADERS_FOR_FULFILLMENT_ORDER = [
        Header::FULFILLMENT_ORDER_MERCHANT_ORDER_ID,
        Header::FULFILLMENT_ORDER_STATUS,
        Header::FULFILLMENT_ORDER_AWB_NUMBER,
        Header::FULFILLMENT_ORDER_SHIPPING_PROVIDER_NAME
    ];

    // Following is a list of columns that are mandatory headers in the cod eligibility attribute whitelist batch file
    const MANDATORY_HEADERS_FOR_ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST = [
        Header::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_TYPE,
        Header::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_VALUE,
    ];

    // Following is a list of columns that are mandatory headers in the cod eligibility attribute blacklist batch file
    const MANDATORY_HEADERS_FOR_ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST = [
        Header::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_TYPE,
        Header::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_VALUE,
    ];

    /**
     * Input and output file headers
     * The keys need to be like <type>_<sub-type>_<gateway>.
     * Above is subject to those value not being empty.
     *
     * @var array
     */
    const HEADER_MAP = [
        Type::MPAN => [
            self::INPUT => [
                self::MPAN_SERIAL_NUMBER,
                self::MPAN_ADDED_ON,
                self::MPAN_VISA_PAN,
                self::MPAN_MASTERCARD_PAN,
                self::MPAN_RUPAY_PAN,
            ],
            self::OUTPUT => [
                self::MPAN_SERIAL_NUMBER,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
            self::SENSITIVE_HEADERS => [
                self::MPAN_VISA_PAN,
                self::MPAN_MASTERCARD_PAN,
                self::MPAN_RUPAY_PAN,
            ],
        ],

        Type::TERMINAL_CREATION => [
            self::INPUT => [
                self::TERMINAL_CREATION_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_ID,
                self::TERMINAL_CREATION_GATEWAY_ACCESS_CODE,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2,
                self::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_VPA,
                self::TERMINAL_CREATION_CATEGORY,
                self::TERMINAL_CREATION_CARD,
                self::TERMINAL_CREATION_NETBANKING,
                self::TERMINAL_CREATION_EMANDATE,
                self::TERMINAL_CREATION_EMI,
                self::TERMINAL_CREATION_UPI,
                self::TERMINAL_CREATION_OMNICHANNEL,
                self::TERMINAL_CREATION_BANK_TRANSFER,
                self::TERMINAL_CREATION_AEPS,
                self::TERMINAL_CREATION_EMI_DURATION,
                self::TERMINAL_CREATION_TYPE,
                self::TERMINAL_CREATION_MODE,
                self::TERMINAL_CREATION_TPV,
                self::TERMINAL_CREATION_INTERNATIONAL,
                self::TERMINAL_CREATION_CORPORATE,
                self::TERMINAL_CREATION_EXPECTED,
                self::TERMINAL_CREATION_EMI_SUBVENTION,
                self::TERMINAL_CREATION_GATEWAY_ACQUIRER,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
                self::TERMINAL_CREATION_CURRENCY,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
                self::TERMINAL_CREATION_IFSC_CODE,
                self::TERMINAL_CREATION_CARDLESS_EMI,
                self::TERMINAL_CREATION_PAYLATER,
                self::TERMINAL_CREATION_ENABLED,
                self::TERMINAL_CREATION_STATUS,
                self::TERMINAL_CREATION_CAPABILITY,
                self::TERMINAL_CREATION_PLAN_ID,
            ],
            self::OUTPUT => [
                self::TERMINAL_ID,
                self::TERMINAL_CREATION_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_ID,
                self::TERMINAL_CREATION_GATEWAY_ACCESS_CODE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_VPA,
                self::TERMINAL_CREATION_CATEGORY,
                self::TERMINAL_CREATION_CARD,
                self::TERMINAL_CREATION_NETBANKING,
                self::TERMINAL_CREATION_EMANDATE,
                self::TERMINAL_CREATION_EMI,
                self::TERMINAL_CREATION_UPI,
                self::TERMINAL_CREATION_BANK_TRANSFER,
                self::TERMINAL_CREATION_AEPS,
                self::TERMINAL_CREATION_EMI_DURATION,
                self::TERMINAL_CREATION_TYPE,
                self::TERMINAL_CREATION_MODE,
                self::TERMINAL_CREATION_TPV,
                self::TERMINAL_CREATION_INTERNATIONAL,
                self::TERMINAL_CREATION_CORPORATE,
                self::TERMINAL_CREATION_EXPECTED,
                self::TERMINAL_CREATION_EMI_SUBVENTION,
                self::TERMINAL_CREATION_GATEWAY_ACQUIRER,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
                self::TERMINAL_CREATION_CURRENCY,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
                self::TERMINAL_CREATION_IFSC_CODE,
                self::TERMINAL_CREATION_CARDLESS_EMI,
                self::TERMINAL_CREATION_PAYLATER,
                self::TERMINAL_CREATION_ENABLED,
                self::TERMINAL_CREATION_STATUS,
                self::TERMINAL_CREATION_CAPABILITY,
                self::TERMINAL_CREATION_PLAN_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
            self::SENSITIVE_HEADERS => [
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2,
                self::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
            ],
        ],

        Type::CREATE_TERMINAL => [
            self::INPUT => [
                self::TERMINAL_CREATION_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_ID,
                self::TERMINAL_CREATION_GATEWAY_ACCESS_CODE,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2,
                self::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_VPA,
                self::TERMINAL_CREATION_CATEGORY,
                self::TERMINAL_CREATION_CARD,
                self::TERMINAL_CREATION_NETBANKING,
                self::TERMINAL_CREATION_EMANDATE,
                self::TERMINAL_CREATION_EMI,
                self::TERMINAL_CREATION_UPI,
                self::TERMINAL_CREATION_OMNICHANNEL,
                self::TERMINAL_CREATION_BANK_TRANSFER,
                self::TERMINAL_CREATION_AEPS,
                self::TERMINAL_CREATION_EMI_DURATION,
                self::TERMINAL_CREATION_TYPE,
                self::TERMINAL_CREATION_MODE,
                self::TERMINAL_CREATION_DEVICE_ID,
                self::TERMINAL_CREATION_LABEL,
                self::TERMINAL_CREATION_TPV,
                self::TERMINAL_CREATION_INTERNATIONAL,
                self::TERMINAL_CREATION_CORPORATE,
                self::TERMINAL_CREATION_EXPECTED,
                self::TERMINAL_CREATION_EMI_SUBVENTION,
                self::TERMINAL_CREATION_GATEWAY_ACQUIRER,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
                self::TERMINAL_CREATION_CURRENCY,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
                self::TERMINAL_CREATION_IFSC_CODE,
                self::TERMINAL_CREATION_CARDLESS_EMI,
                self::TERMINAL_CREATION_PAYLATER,
                self::TERMINAL_CREATION_ENABLED,
                self::TERMINAL_CREATION_STATUS,
                self::TERMINAL_CREATION_CAPABILITY,
                self::TERMINAL_CREATION_PLAN_ID,
            ],
            self::OUTPUT => [
                self::TERMINAL_ID,
                self::TERMINAL_CREATION_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY_MERCHANT_ID2,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_ID,
                self::TERMINAL_CREATION_GATEWAY_ACCESS_CODE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_VPA,
                self::TERMINAL_CREATION_CATEGORY,
                self::TERMINAL_CREATION_CARD,
                self::TERMINAL_CREATION_NETBANKING,
                self::TERMINAL_CREATION_EMANDATE,
                self::TERMINAL_CREATION_EMI,
                self::TERMINAL_CREATION_UPI,
                self::TERMINAL_CREATION_BANK_TRANSFER,
                self::TERMINAL_CREATION_AEPS,
                self::TERMINAL_CREATION_EMI_DURATION,
                self::TERMINAL_CREATION_TYPE,
                self::TERMINAL_CREATION_MODE,
                self::TERMINAL_CREATION_DEVICE_ID,
                self::TERMINAL_CREATION_LABEL,
                self::TERMINAL_CREATION_TPV,
                self::TERMINAL_CREATION_INTERNATIONAL,
                self::TERMINAL_CREATION_CORPORATE,
                self::TERMINAL_CREATION_EXPECTED,
                self::TERMINAL_CREATION_EMI_SUBVENTION,
                self::TERMINAL_CREATION_GATEWAY_ACQUIRER,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
                self::TERMINAL_CREATION_CURRENCY,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
                self::TERMINAL_CREATION_IFSC_CODE,
                self::TERMINAL_CREATION_CARDLESS_EMI,
                self::TERMINAL_CREATION_PAYLATER,
                self::TERMINAL_CREATION_ENABLED,
                self::TERMINAL_CREATION_STATUS,
                self::TERMINAL_CREATION_CAPABILITY,
                self::TERMINAL_CREATION_PLAN_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
            self::SENSITIVE_HEADERS => [
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_TERMINAL_PASSWORD2,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET,
                self::TERMINAL_CREATION_GATEWAY_SECURE_SECRET2,
                self::TERMINAL_CREATION_GATEWAY_RECON_PASSWORD,
                self::TERMINAL_CREATION_GATEWAY_CLIENT_CERTIFICATE,
                self::TERMINAL_CREATION_MC_MPAN,
                self::TERMINAL_CREATION_VISA_MPAN,
                self::TERMINAL_CREATION_RUPAY_MPAN,
                self::TERMINAL_CREATION_ACCOUNT_NUMBER,
            ],
        ],

        Type::HITACHI_FULCRUM_ONBOARD => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_CATEGORY
            ],
        ],

        Type::ALT_ID_TERMINAL_ONBOARD => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::TERMINAL_CREATION_GATEWAY,
                self::TERMINAL_CREATION_CATEGORY,
                self::ORG_ID,
                self::TERMINAL_BATCH_CREATION_TYPE,
                self::TERMINAL_BATCH_CREATION_GATEWAY,
                self::TERMINAL_BATCH_CREATION_GATEWAY_TERMINAL_ID,
                self::TERMINAL_BATCH_CREATION_PROVIDER_NAME,
                self::TERMINAL_BATCH_CREATION_PROVIDER_TYPE,
            ],
        ],

        Type::UPI_TERMINAL_ONBOARDING   =>  [
            self::INPUT => [
                self::UPI_TERMINAL_ONBOARDING_MERCHANT_ID,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY,
                self::UPI_TERMINAL_ONBOARDING_VPA,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE,
                self::UPI_TERMINAL_ONBOARDING_EXPECTED,
                self::UPI_TERMINAL_ONBOARDING_VPA_HANDLE,
                self::UPI_TERMINAL_ONBOARDING_RECURRING,
                self::UPI_TERMINAL_ONBOARDING_MCC,
                self::UPI_TERMINAL_ONBOARDING_CATEGORY2,
                self::UPI_TERMINAL_ONBOARDING_MERCHANT_TYPE,
                self::UPI_TERMINAL_ONBOARDING_ALLOW_CC,
                self::UPI_TERMINAL_ONBOARDING_ALLOW_WALLET,
                self::UPI_TERMINAL_ONBOARDING_ALLOW_CREDIT_LINE,
                self::UPI_TERMINAL_ONBOARDING_DIRECT_PUSH,
            ],
            self::OUTPUT => [
                self::UPI_TERMINAL_ONBOARDING_MERCHANT_ID,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY,
                self::UPI_TERMINAL_ONBOARDING_VPA,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY_TERMINAL_ID,
                self::UPI_TERMINAL_ONBOARDING_GATEWAY_ACCESS_CODE,
                self::UPI_TERMINAL_ONBOARDING_EXPECTED,
                self::UPI_TERMINAL_ONBOARDING_VPA_HANDLE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],
        Type::UPI_ONBOARDED_TERMINAL_EDIT   =>  [
            self::INPUT => [
                self::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID,
                self::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY,
                self::UPI_ONBOARDED_TERMINAL_EDIT_ONLINE,
                self::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CC,
                self::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_WALLET,
                self::UPI_ONBOARDED_TERMINAL_EDIT_ALLOW_CREDIT_LINE,
                self::UPI_ONBOARDED_TERMINAL_EDIT_MERCHANT_SIZE,
                self::UPI_ONBOARDED_TERMINAL_EDIT_MCC,
                self::UPI_ONBOARDED_TERMINAL_EDIT_BILLING_LABEL,
                self::UPI_ONBOARDED_TERMINAL_EDIT_MOBILE_NUMBER,
            ],
            self::OUTPUT => [
                self::UPI_ONBOARDED_TERMINAL_EDIT_TERMINAL_ID,
                self::UPI_ONBOARDED_TERMINAL_EDIT_GATEWAY,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::REFUND => [

            self::INPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
                self::RECEIPT,
                self::NOTES,
                self::SPEED
            ],

            self::OUTPUT => [
                self::PAYMENT_ID,
                self::AMOUNT,
                self::RECEIPT,
                self::NOTES,
                self::REFUND_ID,
                self::REFUNDED_AMOUNT,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
                self::SPEED
            ],
        ],

        Type::VIRTUAL_ACCOUNT_EDIT => [

            self::INPUT => [
                self::VIRTUAL_ACCOUNT_ID,
                self::EXPIRE_BY,
            ],

            self::OUTPUT => [
                self::VIRTUAL_ACCOUNT_ID,
                self::EXPIRE_BY,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
                self::SPEED
            ],
        ],

        Type::EZETAP_SETTLEMENT => [

            self::INPUT => [
                self::TRANSACTION_SOURCE,
                self::MERCHANT_CODE,
                self::TERMINAL_NUMBER,
                self::CARD_NUMBER_EZETAP,
                self::MERCHANT_TRACK_ID,
                self::TRANS_DATE,
                self::SETTLE_DATE,
                self::TRANSACTION_AMOUNT,
                self::NET_AMOUNT,
                self::UDF_1,
                self::UDF_2,
                self::UDF_3,
                self::UDF_4,
                self::UDF_5,
                self::BANK_REFERENCE_NUMBER,
                self::SEQUENCE_NUMBER,
                self::DEBIT_CREDIT_TYPE,
                self::REC_FMT,
                self::CGST_AMT,
                self::SGST_AMT,
                self::IGST_AMT,
                self::UTGST_AMT,
                self::MSF,
                self::GSTN_NO,
                self::MERCHANT_NAME_EZETAP,
                self::BAT_NBR,
                self::UPVALUE,
                self::CARD_TYPE,
                self::INTNL_AMT,
                self::APPROV_CODE,
                self::ARN_NO,
                self::SERV_TAX,
                self::SB_CESS,
                self::KK_CESS,
                self::INVOICE_NUMBER_EZETAP,
                self::UPI_MERCHANT_ID,
                self::MERCHANT_VPA,
                self::CUSTOMER_REF_NO,
                self::CURRENCY_EZETAP,
                self::PAY_TYPE,
            ],

            self::OUTPUT => [
                self::TRANSACTION_SOURCE,
                self::MERCHANT_CODE,
                self::TERMINAL_NUMBER,
                self::CARD_NUMBER_EZETAP,
                self::MERCHANT_TRACK_ID,
                self::TRANS_DATE,
                self::SETTLE_DATE,
                self::TRANSACTION_AMOUNT,
                self::NET_AMOUNT,
                self::UDF_1,
                self::UDF_2,
                self::UDF_3,
                self::UDF_4,
                self::UDF_5,
                self::BANK_REFERENCE_NUMBER,
                self::SEQUENCE_NUMBER,
                self::DEBIT_CREDIT_TYPE,
                self::REC_FMT,
                self::CGST_AMT,
                self::SGST_AMT,
                self::IGST_AMT,
                self::UTGST_AMT,
                self::MSF,
                self::GSTN_NO,
                self::MERCHANT_NAME_EZETAP,
                self::BAT_NBR,
                self::UPVALUE,
                self::CARD_TYPE,
                self::INTNL_AMT,
                self::APPROV_CODE,
                self::ARN_NO,
                self::SERV_TAX,
                self::SB_CESS,
                self::KK_CESS,
                self::INVOICE_NUMBER_EZETAP,
                self::UPI_MERCHANT_ID,
                self::MERCHANT_VPA,
                self::CUSTOMER_REF_NO,
                self::CURRENCY_EZETAP,
                self::PAY_TYPE,
            ],
        ],

        Type::RAW_ADDRESS => [
            self::INPUT => [
                self::RAW_ADDRESS_BULK_NAME,
                self::RAW_ADDRESS_BULK_CONTACT,
                self::RAW_ADDRESS_BULK_LINE1,
                self::RAW_ADDRESS_BULK_LINE2,
                self::RAW_ADDRESS_BULK_LANDMARK,
                self::RAW_ADDRESS_BULK_CITY,
                self::RAW_ADDRESS_BULK_STATE,
                self::RAW_ADDRESS_BULK_ZIPCODE,
                self::RAW_ADDRESS_BULK_COUNTRY,
                self::RAW_ADDRESS_BULK_TAG,
            ],
            self::OUTPUT => [
                self::RAW_ADDRESS_BULK_NAME,
                self::RAW_ADDRESS_BULK_CONTACT,
                self::RAW_ADDRESS_BULK_LINE1,
                self::RAW_ADDRESS_BULK_LINE2,
                self::RAW_ADDRESS_BULK_LANDMARK,
                self::RAW_ADDRESS_BULK_CITY,
                self::RAW_ADDRESS_BULK_STATE,
                self::RAW_ADDRESS_BULK_ZIPCODE,
                self::RAW_ADDRESS_BULK_COUNTRY,
                self::RAW_ADDRESS_BULK_TAG,
                self::RAW_ADDRESS_BULK_STATUS,
            ]
        ],

        Type::FULFILLMENT_ORDER_UPDATE => [
            self::INPUT => [
                self::FULFILLMENT_ORDER_MERCHANT_ORDER_ID,
                self::FULFILLMENT_ORDER_STATUS,
                self::FULFILLMENT_ORDER_SHIPPING_CHARGES,
                self::FULFILLMENT_ORDER_AWB_NUMBER,
                self::FULFILLMENT_ORDER_SHIPPING_PROVIDER_NAME,
            ],
            self::OUTPUT => [
                self::FULFILLMENT_ORDER_MERCHANT_ORDER_ID,
                self::FULFILLMENT_ORDER_STATUS,
                self::FULFILLMENT_ORDER_SHIPPING_CHARGES,
                self::FULFILLMENT_ORDER_AWB_NUMBER,
                self::FULFILLMENT_ORDER_SHIPPING_PROVIDER_NAME,
            ]
        ],

        Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST => [
            self::INPUT => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_TYPE,
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_VALUE,
            ],
            self::OUTPUT => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_TYPE,
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_VALUE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
            self::SENSITIVE_HEADERS => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST_VALUE,
            ],
        ],

        Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST => [
            self::INPUT => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_TYPE,
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_VALUE,
            ],
            self::OUTPUT => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_TYPE,
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_VALUE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
            self::SENSITIVE_HEADERS => [
                self::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST_VALUE,
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
                self::NOTES,
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
                self::NOTES,
                self::STATUS,
                self::PAYMENT_LINK_ID,
                self::SHORT_URL,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYMENT_LINK_V2 => [
            self::INPUT => [
                self::PL_V2_REFERENCE_ID,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
                self::NOTES,
            ],

            self::OUTPUT => [
                self::PL_V2_REFERENCE_ID,
                self::CUSTOMER_NAME,
                self::CUSTOMER_EMAIL,
                self::CUSTOMER_CONTACT,
                self::AMOUNT,
                self::DESCRIPTION,
                self::EXPIRE_BY,
                self::PARTIAL_PAYMENT,
                self::NOTES,
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
        Type::IRCTC_DELTA_REFUND => [

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
                self::MERCHANT_TXN_ID,
                self::TRANSACTION_DATE,
                self::BANK_TRANSACTION_ID,
                self::REFUND_AMOUNT,
                self::REFUND_STATUS,
                self::BANK_REMARKS,
                self::BANK_ACTUAL_REFUND_DATE,
                self::BANK_REFUND_TXN_ID,
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
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::LINKED_ACCOUNT_CREATE => [
            self::INPUT => [
                self::ACCOUNT_NAME,
                self::ACCOUNT_EMAIL,
                self::DASHBOARD_ACCESS,
                self::CUSTOMER_REFUNDS,
                self::BUSINESS_NAME,
                self::BUSINESS_TYPE,
                self::IFSC_CODE,
                self::ACCOUNT_NUMBER,
                self::BENEFICIARY_NAME,
            ],
            self::OUTPUT => [
                self::ACCOUNT_NAME,
                self::ACCOUNT_EMAIL,
                self::ACCOUNT_ID,
                self::DASHBOARD_ACCESS,
                self::CUSTOMER_REFUNDS,
                self::BUSINESS_NAME,
                self::BUSINESS_TYPE,
                self::IFSC_CODE,
                self::ACCOUNT_NUMBER,
                self::BENEFICIARY_NAME,
                self::ACCOUNT_STATUS,
                self::ACTIVATED_AT,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],
        Type::LINKED_ACCOUNT_CREATE_WITH_ACCOUNT_CODE => [
            self::INPUT => [
                self::ACCOUNT_NAME,
                self::ACCOUNT_EMAIL,
                self::DASHBOARD_ACCESS,
                self::CUSTOMER_REFUNDS,
                self::BUSINESS_NAME,
                self::BUSINESS_TYPE,
                self::IFSC_CODE,
                self::ACCOUNT_NUMBER,
                self::BENEFICIARY_NAME,
                self::ACCOUNT_CODE
            ],
            self::OUTPUT => [
                self::ACCOUNT_NAME,
                self::ACCOUNT_EMAIL,
                self::ACCOUNT_ID,
                self::DASHBOARD_ACCESS,
                self::CUSTOMER_REFUNDS,
                self::BUSINESS_NAME,
                self::BUSINESS_TYPE,
                self::IFSC_CODE,
                self::ACCOUNT_NUMBER,
                self::BENEFICIARY_NAME,
                self::ACCOUNT_CODE,
                self::ACCOUNT_STATUS,
                self::ACTIVATED_AT,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
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
                self::HDFC_EM_DEBIT_SR,
                self::HDFC_EM_DEBIT_TRANSACTION_REF_NO,
                self::HDFC_EM_DEBIT_SUB_MERCHANT_NAME,
                self::HDFC_EM_DEBIT_MANDATE_ID,
                self::HDFC_EM_DEBIT_ACCOUNT_NO,
                self::HDFC_EM_DEBIT_AMOUNT,
                self::HDFC_EM_DEBIT_SIP_DATE,
                self::HDFC_EM_DEBIT_FREQUENCY,
                self::HDFC_EM_DEBIT_FROM_DATE,
                self::HDFC_EM_DEBIT_TO_DATE,
                self::HDFC_EM_DEBIT_STATUS,
                self::HDFC_EM_DEBIT_REJECTION_REMARKS,
                self::HDFC_EM_DEBIT_NARRATION,
            ],
            self::OUTPUT => [
                self::HDFC_EM_DEBIT_SR,
                self::HDFC_EM_DEBIT_TRANSACTION_REF_NO,
                self::HDFC_EM_DEBIT_SUB_MERCHANT_NAME,
                self::HDFC_EM_DEBIT_MANDATE_ID,
                self::HDFC_EM_DEBIT_ACCOUNT_NO,
                self::HDFC_EM_DEBIT_AMOUNT,
                self::HDFC_EM_DEBIT_SIP_DATE,
                self::HDFC_EM_DEBIT_FREQUENCY,
                self::HDFC_EM_DEBIT_FROM_DATE,
                self::HDFC_EM_DEBIT_TO_DATE,
                self::HDFC_EM_DEBIT_STATUS,
                self::HDFC_EM_DEBIT_REJECTION_REMARKS,
                self::HDFC_EM_DEBIT_NARRATION,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        'emandate_debit_axis' => [
            self::INPUT => [
                self::AXIS_EM_DEBIT_HEADING_PAYMENT_ID,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_DATE,
                self::AXIS_EM_DEBIT_HEADING_MERCHANT_ID,
                self::AXIS_EM_DEBIT_HEADING_BANK_REF_NUMBER,
                self::AXIS_EM_DEBIT_HEADING_CUSTOMER_NAME,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_ACCOUNT,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_AMOUNT,
                self::AXIS_EM_DB_HEADING_MIS_INFO3,
                self::AXIS_EM_DEBIT_HEADING_MIS_INFO4,
                self::AXIS_EM_DEBIT_HEADING_FILE_REF,
                self::AXIS_EM_DEBIT_HEADING_STATUS,
                self::AXIS_EM_DEBIT_HEADING_REMARK,
                self::AXIS_EM_DEBIT_HEADING_RECORD_IDENTIFIER,
            ],
            self::OUTPUT => [
                self::AXIS_EM_DEBIT_HEADING_PAYMENT_ID,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_DATE,
                self::AXIS_EM_DEBIT_HEADING_MERCHANT_ID,
                self::AXIS_EM_DEBIT_HEADING_BANK_REF_NUMBER,
                self::AXIS_EM_DEBIT_HEADING_CUSTOMER_NAME,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_ACCOUNT,
                self::AXIS_EM_DEBIT_HEADING_DEBIT_AMOUNT,
                self::AXIS_EM_DB_HEADING_MIS_INFO3,
                self::AXIS_EM_DEBIT_HEADING_MIS_INFO4,
                self::AXIS_EM_DEBIT_HEADING_FILE_REF,
                self::AXIS_EM_DEBIT_HEADING_STATUS,
                self::AXIS_EM_DEBIT_HEADING_REMARK,
                self::AXIS_EM_DEBIT_HEADING_RECORD_IDENTIFIER,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
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
            ],
            self::OUTPUT => [
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
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        'emandate_register_enach_npci_netbanking' => [
            self::INPUT => [
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_ID,
                self::ENACH_NPCI_NETBANKING_REGISTER_UMRN,
                self::ENACH_NPCI_NETBANKING_REGISTER_CUST_REF_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_SCH_REF_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_CUST_NAME,
                self::ENACH_NPCI_NETBANKING_REGISTER_BANK,
                self::ENACH_NPCI_NETBANKING_REGISTER_BRANCH,
                self::ENACH_NPCI_NETBANKING_REGISTER_BANK_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_AC_TYPE,
                self::ENACH_NPCI_NETBANKING_REGISTER_AC_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_AMOUNT,
                self::ENACH_NPCI_NETBANKING_REGISTER_FREQUENCY,
                self::ENACH_NPCI_NETBANKING_REGISTER_DEBIT_TYPE,
                self::ENACH_NPCI_NETBANKING_REGISTER_START_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_END_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UNTIL_CANCEL,
                self::ENACH_NPCI_NETBANKING_REGISTER_TEL_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_MOBILE_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_MAIL_ID,
                self::ENACH_NPCI_NETBANKING_REGISTER_UPLOAD_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_RESPONSE_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UTILITY_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UTILITY_NAME,
                self::ENACH_NPCI_NETBANKING_REGISTER_STATUS,
                self::ENACH_NPCI_NETBANKING_REGISTER_STATUS_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_REASON,
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_REQID,
                self::ENACH_NPCI_NETBANKING_REGISTER_MESSAGE_ID,
            ],
            self::OUTPUT => [
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_ID,
                self::ENACH_NPCI_NETBANKING_REGISTER_UMRN,
                self::ENACH_NPCI_NETBANKING_REGISTER_CUST_REF_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_SCH_REF_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_CUST_NAME,
                self::ENACH_NPCI_NETBANKING_REGISTER_BANK,
                self::ENACH_NPCI_NETBANKING_REGISTER_BRANCH,
                self::ENACH_NPCI_NETBANKING_REGISTER_BANK_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_AC_TYPE,
                self::ENACH_NPCI_NETBANKING_REGISTER_AC_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_AMOUNT,
                self::ENACH_NPCI_NETBANKING_REGISTER_FREQUENCY,
                self::ENACH_NPCI_NETBANKING_REGISTER_DEBIT_TYPE,
                self::ENACH_NPCI_NETBANKING_REGISTER_START_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_END_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UNTIL_CANCEL,
                self::ENACH_NPCI_NETBANKING_REGISTER_TEL_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_MOBILE_NO,
                self::ENACH_NPCI_NETBANKING_REGISTER_MAIL_ID,
                self::ENACH_NPCI_NETBANKING_REGISTER_UPLOAD_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_RESPONSE_DATE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UTILITY_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_UTILITY_NAME,
                self::ENACH_NPCI_NETBANKING_REGISTER_STATUS,
                self::ENACH_NPCI_NETBANKING_REGISTER_STATUS_CODE,
                self::ENACH_NPCI_NETBANKING_REGISTER_REASON,
                self::ENACH_NPCI_NETBANKING_REGISTER_MANDATE_REQID,
                self::ENACH_NPCI_NETBANKING_REGISTER_MESSAGE_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
                self::PAYMENT_ID
            ],
        ],

        'emandate_debit_enach_npci_netbanking' => [
            self::INPUT => [
                self::ENACH_NPCI_NETBANKING_DEBIT_PRESENTATION_DATE,
                self::ENACH_NPCI_NETBANKING_DEBIT_UMRN,
                self::ENACH_NPCI_NETBANKING_DEBIT_PAYMENT_ID,
                self::ENACH_NPCI_NETBANKING_DEBIT_UTILITY_CODE,
                self::ENACH_NPCI_NETBANKING_DEBIT_BANK_ACC,
                self::ENACH_NPCI_NETBANKING_DEBIT_ACCOUNT_NAME,
                self::ENACH_NPCI_NETBANKING_DEBIT_BANK,
                self::ENACH_NPCI_NETBANKING_DEBIT__IFSC,
                self::ENACH_NPCI_NETBANKING_DEBIT_AMOUNT,
                self::ENACH_NPCI_NETBANKING_DEBIT_REF_ONE,
                self::ENACH_NPCI_NETBANKING_DEBIT_REF_TWO,
                self::ENACH_NPCI_NETBANKING_DEBIT_STATUS,
                self::ENACH_NPCI_NETBANKING_DEBIT_ERROR_CODE,
                self::ENACH_NPCI_NETBANKING_DEBIT_ERROR_DESCRIPTION,
                self::ENACH_NPCI_NETBANKING_DEBIT_USER_REF,
            ],
            self::OUTPUT => [
                self::ENACH_NPCI_NETBANKING_DEBIT_PRESENTATION_DATE,
                self::ENACH_NPCI_NETBANKING_DEBIT_UMRN,
                self::ENACH_NPCI_NETBANKING_DEBIT_PAYMENT_ID,
                self::ENACH_NPCI_NETBANKING_DEBIT_UTILITY_CODE,
                self::ENACH_NPCI_NETBANKING_DEBIT_BANK_ACC,
                self::ENACH_NPCI_NETBANKING_DEBIT_ACCOUNT_NAME,
                self::ENACH_NPCI_NETBANKING_DEBIT_BANK,
                self::ENACH_NPCI_NETBANKING_DEBIT__IFSC,
                self::ENACH_NPCI_NETBANKING_DEBIT_AMOUNT,
                self::ENACH_NPCI_NETBANKING_DEBIT_REF_ONE,
                self::ENACH_NPCI_NETBANKING_DEBIT_REF_TWO,
                self::ENACH_NPCI_NETBANKING_DEBIT_STATUS,
                self::ENACH_NPCI_NETBANKING_DEBIT_ERROR_CODE,
                self::ENACH_NPCI_NETBANKING_DEBIT_ERROR_DESCRIPTION,
                self::ENACH_NPCI_NETBANKING_DEBIT_USER_REF,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        'emandate_debit_enach_nb_icici' => [  // deprecated
            self::INPUT => [
                self::ICICI_NPCI_ENACH_DEBIT_ACH_TRANSACTION_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9S,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_15S,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9SS,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_7S,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_13S,
                self::ICICI_NPCI_ENACH_DEBIT_AMOUNT,
                self::ICICI_NPCI_ENACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::ICICI_NPCI_ENACH_DEBIT_CHECKSUM,
                self::ICICI_NPCI_ENACH_DEBIT_FLAG,
                self::ICICI_NPCI_ENACH_DEBIT_REASON_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_SPONSOR_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_TRANSACTION_REFERENCE,
                self::ICICI_NPCI_ENACH_DEBIT_PRODUCT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_UMRN,
                self::ICICI_NPCI_ENACH_DEBIT_FILLER,
            ],
            self::OUTPUT => [
                self::ICICI_NPCI_ENACH_DEBIT_ACH_TRANSACTION_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9S,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_15S,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9SS,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_7S,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_13S,
                self::ICICI_NPCI_ENACH_DEBIT_AMOUNT,
                self::ICICI_NPCI_ENACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::ICICI_NPCI_ENACH_DEBIT_CHECKSUM,
                self::ICICI_NPCI_ENACH_DEBIT_FLAG,
                self::ICICI_NPCI_ENACH_DEBIT_REASON_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_SPONSOR_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_TRANSACTION_REFERENCE,
                self::ICICI_NPCI_ENACH_DEBIT_PRODUCT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_UMRN,
                self::ICICI_NPCI_ENACH_DEBIT_FILLER,
            ],
        ],

        'nach_debit_nach_icici' => [
            self::INPUT => [
                self::ICICI_NPCI_ENACH_DEBIT_ACH_TRANSACTION_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9S,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_15S,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9SS,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_7S,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_13S,
                self::ICICI_NPCI_ENACH_DEBIT_AMOUNT,
                self::ICICI_NPCI_ENACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::ICICI_NPCI_ENACH_DEBIT_CHECKSUM,
                self::ICICI_NPCI_ENACH_DEBIT_FLAG,
                self::ICICI_NPCI_ENACH_DEBIT_REASON_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_SPONSOR_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_TRANSACTION_REFERENCE,
                self::ICICI_NPCI_ENACH_DEBIT_PRODUCT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_UMRN,
                self::ICICI_NPCI_ENACH_DEBIT_FILLER,
            ],
            self::OUTPUT => [
                self::ICICI_NPCI_ENACH_DEBIT_ACH_TRANSACTION_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9S,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_15S,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_9SS,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_7S,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NAME,
                self::ICICI_NPCI_ENACH_DEBIT_CONTROL_13S,
                self::ICICI_NPCI_ENACH_DEBIT_AMOUNT,
                self::ICICI_NPCI_ENACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::ICICI_NPCI_ENACH_DEBIT_CHECKSUM,
                self::ICICI_NPCI_ENACH_DEBIT_FLAG,
                self::ICICI_NPCI_ENACH_DEBIT_REASON_CODE,
                self::ICICI_NPCI_ENACH_DEBIT_DESTINATION_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_SPONSOR_BANK_IFSC,
                self::ICICI_NPCI_ENACH_DEBIT_USER_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_TRANSACTION_REFERENCE,
                self::ICICI_NPCI_ENACH_DEBIT_PRODUCT_TYPE,
                self::ICICI_NPCI_ENACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::ICICI_NPCI_ENACH_DEBIT_UMRN,
                self::ICICI_NPCI_ENACH_DEBIT_FILLER,
            ],
        ],

        'nach_update_ifsc' => [
            self::INPUT => [
                'token_id',
                'old_ifsc',
                'new_ifsc',
            ]
        ],

        'merchant_onboarding_emi_sbi' => [
            self::INPUT => [
                self::MERCHANT_ONBOARDING_EMI_SBI_MID,
                self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_MID,
                self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_TID,
            ],
            self::OUTPUT => [
                self::MERCHANT_ONBOARDING_EMI_SBI_MID,
                self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_MID,
                self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_TID,
                self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_ACQUIRER,
                self::MERCHANT_ONBOARDING_EMI_SBI_RZP_TID,
            ],
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
                self::NARRATION,
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
                self::NARRATION,
                self::STATUS,
            ],
        ],

        Type::RETRY_REFUNDS_TO_BA => [
            self::INPUT => [
                self::REFUND_ID,
                self::REFUND_BENEFICIARY_NAME,
                self::REFUND_ACCOUNT_NUMBER,
                self::REFUND_IFSC,
                self::REFUND_TRANSFER_MODE,
            ],
            self::OUTPUT => [
                self::REFUND_ID,
                self::REFUND_BENEFICIARY_NAME,
                self::REFUND_ACCOUNT_NUMBER,
                self::REFUND_IFSC,
                self::REFUND_TRANSFER_MODE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
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
                self::NOTES,
            ],
            self::OUTPUT => [
                self::RECURRING_CHARGE_TOKEN,
                self::RECURRING_CHARGE_CUSTOMER_ID,
                self::RECURRING_CHARGE_AMOUNT,
                self::RECURRING_CHARGE_CURRENCY,
                self::RECURRING_CHARGE_RECEIPT,
                self::RECURRING_CHARGE_DESCRIPTION,
                self::NOTES,
                self::RECURRING_CHARGE_ORDER_ID,
                self::RECURRING_CHARGE_PAYMENT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::RECURRING_TOKEN_DELETE => [
            self::INPUT => [
                self::TOKEN_ID,
            ],
            self::OUTPUT => [
                self::TOKEN_ID,
                self::STATUS,
            ],
        ],

        Type::CANCEL_DORMANT_MANDATES => [
            self::INPUT => [
                self::TOKEN_ID,
            ],
            self::OUTPUT => [
                self::TOKEN_ID,
                self::STATUS,
            ],
        ],

        Type::RECURRING_CHARGE_BULK => [
            self::INPUT => [
                self::RECURRING_CHARGE_TOKEN,
                self::RECURRING_CHARGE_CUSTOMER_ID,
                self::RECURRING_CHARGE_AMOUNT,
                self::RECURRING_CHARGE_CURRENCY,
                self::RECURRING_CHARGE_RECEIPT,
                self::RECURRING_CHARGE_DESCRIPTION,
                self::NOTES,
            ],
            self::OUTPUT => [
                self::RECURRING_CHARGE_TOKEN,
                self::RECURRING_CHARGE_CUSTOMER_ID,
                self::RECURRING_CHARGE_AMOUNT,
                self::RECURRING_CHARGE_CURRENCY,
                self::RECURRING_CHARGE_RECEIPT,
                self::RECURRING_CHARGE_DESCRIPTION,
                self::NOTES,
                self::RECURRING_CHARGE_ORDER_ID,
                self::RECURRING_CHARGE_PAYMENT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::RECURRING_CHARGE_AXIS => [
            self::INPUT => [
                self::RECURRING_CHARGE_AXIS_SLNO,
                self::RECURRING_CHARGE_AXIS_URNNO,
                self::RECURRING_CHARGE_AXIS_FOLIO_NO,
                self::RECURRING_CHARGE_AXIS_SCHEMECODE,
                self::RECURRING_CHARGE_AXIS_TRANSACTION_NO,
                self::RECURRING_CHARGE_AXIS_INVESTOR_NAME,
                self::RECURRING_CHARGE_AXIS_PURCHASE_DAY,
                self::RECURRING_CHARGE_AXIS_PUR_AMOUNT,
                self::RECURRING_CHARGE_AXIS_BANK_ACCOUNTNO,
                self::RECURRING_CHARGE_AXIS_PURCHASE_DATE,
                self::RECURRING_CHARGE_AXIS_BATCH_REF_NUMBER,
                self::RECURRING_CHARGE_AXIS_BRANCH,
                self::RECURRING_CHARGE_AXIS_TR_TYPE,
                self::RECURRING_CHARGE_AXIS_UMRNNO_OR_TOKENID,
                self::RECURRING_CHARGE_AXIS_CREDIT_ACCOUNT_NO,
            ],
            self::OUTPUT => [
                self::RECURRING_CHARGE_AXIS_SLNO,
                self::RECURRING_CHARGE_AXIS_URNNO,
                self::RECURRING_CHARGE_AXIS_FOLIO_NO,
                self::RECURRING_CHARGE_AXIS_SCHEMECODE,
                self::RECURRING_CHARGE_AXIS_TRANSACTION_NO,
                self::RECURRING_CHARGE_AXIS_INVESTOR_NAME,
                self::RECURRING_CHARGE_AXIS_PURCHASE_DAY,
                self::RECURRING_CHARGE_AXIS_PUR_AMOUNT,
                self::RECURRING_CHARGE_AXIS_BANK_ACCOUNTNO,
                self::RECURRING_CHARGE_AXIS_PURCHASE_DATE,
                self::RECURRING_CHARGE_AXIS_BATCH_REF_NUMBER,
                self::RECURRING_CHARGE_AXIS_BRANCH,
                self::RECURRING_CHARGE_AXIS_TR_TYPE,
                self::RECURRING_CHARGE_AXIS_UMRNNO_OR_TOKENID,
                self::RECURRING_CHARGE_AXIS_CREDIT_ACCOUNT_NO,
            ],
        ],

        Type::RECURRING_CHARGE_BSE => [
            self::INPUT => [
                self::RECURRING_CHARGE_BSE_UNIQUE_REFERENCE_NUMBER,
                self::RECURRING_CHARGE_BSE_DEBIT_AMOUNT,
                self::RECURRING_CHARGE_BSE_DUE_DATE,
                self::RECURRING_CHARGE_BSE_ACTUAL_DEBIT_DATE,
                self::RECURRING_CHARGE_BSE_ICCL_REFERENCE,
                self::RECURRING_CHARGE_BSE_TRANSACTION_TYPE,
                self::RECURRING_CHARGE_BSE_UMRN,
                self::NOTES,
            ],
            self::OUTPUT => [
                self::RECURRING_CHARGE_BSE_UNIQUE_REFERENCE_NUMBER,
                self::RECURRING_CHARGE_BSE_DEBIT_AMOUNT,
                self::RECURRING_CHARGE_BSE_DUE_DATE,
                self::RECURRING_CHARGE_BSE_ACTUAL_DEBIT_DATE,
                self::RECURRING_CHARGE_BSE_ICCL_REFERENCE,
                self::RECURRING_CHARGE_BSE_TRANSACTION_TYPE,
                self::RECURRING_CHARGE_BSE_UMRN,
                self::NOTES,
                self::RECURRING_CHARGE_ORDER_ID,
                self::RECURRING_CHARGE_PAYMENT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        'emandate_register_hdfc' => [
            self::INPUT => [
                self::HDFC_EM_REGISTER_CLIENT_NAME,
                self::HDFC_EM_REGISTER_SUB_MERCHANT_NAME,
                self::HDFC_EM_REGISTER_CUSTOMER_NAME,
                self::HDFC_EM_REGISTER_ACCOUNT_NUMBER,
                self::HDFC_EM_REGISTER_AMOUNT,
                self::HDFC_EM_REGISTER_AMOUNT_TYPE,
                self::HDFC_EM_REGISTER_START_DATE,
                self::HDFC_EM_REGISTER_END_DATE,
                self::HDFC_EM_REGISTER_FREQUENCY,
                self::HDFC_EM_REGISTER_MANDATE_ID,
                self::HDFC_EM_REGISTER_MERCHANT_UNIQUE_REF_NO,
                self::HDFC_EM_REGISTER_MANDATE_SERIAL_NO,
                self::HDFC_EM_REGISTER_MERCHANT_REQUEST_NO,
                self::HDFC_EM_REGISTER_STATUS,
                self::HDFC_EM_REGISTER_REMARKS,
            ],
            self::OUTPUT => [
                self::HDFC_EM_REGISTER_CLIENT_NAME,
                self::HDFC_EM_REGISTER_SUB_MERCHANT_NAME,
                self::HDFC_EM_REGISTER_CUSTOMER_NAME,
                self::HDFC_EM_REGISTER_ACCOUNT_NUMBER,
                self::HDFC_EM_REGISTER_AMOUNT,
                self::HDFC_EM_REGISTER_AMOUNT_TYPE,
                self::HDFC_EM_REGISTER_START_DATE,
                self::HDFC_EM_REGISTER_END_DATE,
                self::HDFC_EM_REGISTER_FREQUENCY,
                self::HDFC_EM_REGISTER_MANDATE_ID,
                self::HDFC_EM_REGISTER_MERCHANT_UNIQUE_REF_NO,
                self::HDFC_EM_REGISTER_MANDATE_SERIAL_NO,
                self::HDFC_EM_REGISTER_MERCHANT_REQUEST_NO,
                self::HDFC_EM_REGISTER_STATUS,
                self::HDFC_EM_REGISTER_REMARKS,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        'emandate_cancel_debit_all' => [
            self::INPUT => [
                'payment_id',
            ]
        ],

        'emandate_register_sbi' => [
            self::INPUT => [
                'success' => [
                    self::SBI_EM_REGISTER_SR_NO,
                    self::SBI_EM_REGISTER_EMANDATE_TYPE,
                    self::SBI_EM_REGISTER_UMRN,
                    self::SBI_EM_REGISTER_MERCHANT_ID,
                    self::SBI_EM_REGISTER_CUSTOMER_REF_NO,
                    self::SBI_EM_REGISTER_SCHEME_NAME,
                    self::SBI_EM_REGISTER_SUB_SCHEME_NAME,
                    self::SBI_EM_REGISTER_DEBIT_CUSTOMER_NAME ,
                    self::SBI_EM_REGISTER_DEBIT_ACCOUNT_NUMBER,
                    self::SBI_EM_REGISTER_DEBIT_ACCOUNT_TYPE,
                    self::SBI_EM_REGISTER_DEBIT_IFSC,
                    self::SBI_EM_REGISTER_DEBIT_BANK_NAME,
                    self::SBI_EM_REGISTER_AMOUNT,
                    self::SBI_EM_REGISTER_AMOUNT_TYPE,
                    self::SBI_EM_REGISTER_CUSTOMER_ID,
                    self::SBI_EM_REGISTER_PERIOD,
                    self::SBI_EM_REGISTER_PAYMENT_TYPE,
                    self::SBI_EM_REGISTER_FREQUENCY,
                    self::SBI_EM_REGISTER_START_DATE,
                    self::SBI_EM_REGISTER_END_DATE,
                    self::SBI_EM_REGISTER_MOBILE,
                    self::SBI_EM_REGISTER_EMAIL,
                    self::SBI_EM_REGISTER_OTHER_REF_NO,
                    self::SBI_EM_REGISTER_PAN_NUMBER,
                    self::SBI_EM_REGISTER_AUTO_DEBIT_DATE,
                    self::SBI_EM_REGISTER_AUTHENTICATION_MODE,
                    self::SBI_EM_REGISTER_DATE_PROCESSED,
                    self::SBI_EM_REGISTER_STATUS,
                    self::SBI_EM_REGISTER_NO_OF_DAYS_PENDING,
                    self::SBI_EM_REGISTER_REJECT_REASON,
                ],
                'reject' => [
                    self::SBI_EM_REGISTER_TRANSACTION_DATE,
                    self::SBI_EM_REGISTER_CUSTOMER_NAME,
                    self::SBI_EM_REGISTER_CUSTOMER_REF_NO,
                    self::SBI_EM_REGISTER_CUSTOMER_ACCOUNT_NUMBER,
                    self::SBI_EM_REGISTER_AMOUNT,
                    self::SBI_EM_REGISTER_MAX_AMOUNT,
                    self::SBI_EM_REGISTER_STATUS,
                    self::SBI_EM_REGISTER_STATUS_DESCRIPTION,
                    self::SBI_EM_REGISTER_START_DATE_REJECT_FILE,
                    self::SBI_EM_REGISTER_END_DATE_REJECT_FILE,
                    self::SBI_EM_REGISTER_FREQUENCY,
                    self::SBI_EM_REGISTER_UMRN_REJECT_RILE,
                    self::SBI_EM_REGISTER_SBI_REFERENCE_NO,
                    self::SBI_EM_REGISTER_MODE_OF_VERIFICATION,
                    self::SBI_EM_REGISTER_AMOUNT_TYPE_REJECT_FILE,
                ]
            ],
            self::OUTPUT => [
                'success' => [
                    self::SBI_EM_REGISTER_SR_NO,
                    self::SBI_EM_REGISTER_EMANDATE_TYPE,
                    self::SBI_EM_REGISTER_UMRN,
                    self::SBI_EM_REGISTER_MERCHANT_ID,
                    self::SBI_EM_REGISTER_CUSTOMER_REF_NO,
                    self::SBI_EM_REGISTER_SCHEME_NAME,
                    self::SBI_EM_REGISTER_DEBIT_CUSTOMER_NAME ,
                    self::SBI_EM_REGISTER_DEBIT_ACCOUNT_NUMBER,
                    self::SBI_EM_REGISTER_DEBIT_ACCOUNT_TYPE,
                    self::SBI_EM_REGISTER_DEBIT_IFSC,
                    self::SBI_EM_REGISTER_DEBIT_BANK_NAME,
                    self::SBI_EM_REGISTER_AMOUNT,
                    self::SBI_EM_REGISTER_AMOUNT_TYPE,
                    self::SBI_EM_REGISTER_CUSTOMER_ID,
                    self::SBI_EM_REGISTER_PERIOD,
                    self::SBI_EM_REGISTER_PAYMENT_TYPE,
                    self::SBI_EM_REGISTER_FREQUENCY,
                    self::SBI_EM_REGISTER_START_DATE,
                    self::SBI_EM_REGISTER_END_DATE,
                    self::SBI_EM_REGISTER_MOBILE,
                    self::SBI_EM_REGISTER_EMAIL,
                    self::SBI_EM_REGISTER_OTHER_REF_NO,
                    self::SBI_EM_REGISTER_PAN_NUMBER,
                    self::SBI_EM_REGISTER_AUTO_DEBIT_DATE,
                    self::SBI_EM_REGISTER_AUTHENTICATION_MODE,
                    self::SBI_EM_REGISTER_DATE_PROCESSED,
                    self::SBI_EM_REGISTER_STATUS,
                    self::SBI_EM_REGISTER_NO_OF_DAYS_PENDING,
                    self::SBI_EM_REGISTER_REJECT_REASON,
                    self::STATUS,
                    self::ERROR_CODE,
                    self::ERROR_DESCRIPTION,
                ],
                'reject' => [
                    self::SBI_EM_REGISTER_TRANSACTION_DATE,
                    self::SBI_EM_REGISTER_CUSTOMER_NAME,
                    self::SBI_EM_REGISTER_CUSTOMER_REF_NO,
                    self::SBI_EM_REGISTER_CUSTOMER_ACCOUNT_NUMBER,
                    self::SBI_EM_REGISTER_AMOUNT,
                    self::SBI_EM_REGISTER_MAX_AMOUNT,
                    self::SBI_EM_REGISTER_STATUS,
                    self::SBI_EM_REGISTER_STATUS_DESCRIPTION,
                    self::SBI_EM_REGISTER_START_DATE_REJECT_FILE,
                    self::SBI_EM_REGISTER_END_DATE_REJECT_FILE,
                    self::SBI_EM_REGISTER_FREQUENCY,
                    self::SBI_EM_REGISTER_UMRN_REJECT_RILE,
                    self::SBI_EM_REGISTER_SBI_REFERENCE_NO,
                    self::SBI_EM_REGISTER_MODE_OF_VERIFICATION,
                    self::SBI_EM_REGISTER_AMOUNT_TYPE_REJECT_FILE,
                    self::STATUS,
                    self::ERROR_CODE,
                    self::ERROR_DESCRIPTION,
                ]
            ]
        ],

        'emandate_debit_sbi' => [
            self::INPUT => [
                self::SBI_EM_DEBIT_RESPONSE_SERIAL_NUMBER,
                self::SBI_EM_DEBIT_UMRN,
                self::SBI_EM_DEBIT_CUSTOMER_CODE,
                self::SBI_EM_DEBIT_CUSTOMER_NAME,
                self::SBI_EM_DEBIT_TRANSACTION_INPUT_CHANNEL,
                self::SBI_EM_DEBIT_FILE_NAME,
                self::SBI_EM_DEBIT_CUSTOMER_REF_NO,
                self::SBI_EM_DEBIT_MANDATE_HOLDER_NAME,
                self::SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER,
                self::SBI_EM_DEBIT_DEBIT_BANK_IFSC,
                self::SBI_EM_DEBIT_DEBIT_DATE,
                self::SBI_EM_DEBIT_AMOUNT,
                self::SBI_EM_DEBIT_JOURNAL_NUMBER,
                self::SBI_EM_DEBIT_PROCESSING_DATE,
                self::SBI_EM_DEBIT_DEBIT_STATUS,
                self::SBI_EM_DEBIT_CREDIT_STATUS,
                self::SBI_EM_DEBIT_REASON,
                self::SBI_EM_DEBIT_CREDIT_DATE,
            ],
            self::OUTPUT => [
                self::SBI_EM_DEBIT_RESPONSE_SERIAL_NUMBER,
                self::SBI_EM_DEBIT_UMRN,
                self::SBI_EM_DEBIT_UMRN,
                self::SBI_EM_DEBIT_CUSTOMER_CODE,
                self::SBI_EM_DEBIT_CUSTOMER_NAME,
                self::SBI_EM_DEBIT_TRANSACTION_INPUT_CHANNEL,
                self::SBI_EM_DEBIT_FILE_NAME,
                self::SBI_EM_DEBIT_CUSTOMER_REF_NO,
                self::SBI_EM_DEBIT_MANDATE_HOLDER_NAME,
                self::SBI_EM_DEBIT_DEBIT_ACCOUNT_NUMBER,
                self::SBI_EM_DEBIT_DEBIT_BANK_IFSC,
                self::SBI_EM_DEBIT_DEBIT_DATE,
                self::SBI_EM_DEBIT_AMOUNT,
                self::SBI_EM_DEBIT_JOURNAL_NUMBER,
                self::SBI_EM_DEBIT_PROCESSING_DATE,
                self::SBI_EM_DEBIT_DEBIT_STATUS,
                self::SBI_EM_DEBIT_CREDIT_STATUS,
                self::SBI_EM_DEBIT_REASON,
                self::SBI_EM_DEBIT_CREDIT_DATE,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        'emandate_acknowledge_enach_rbl' => [
            self::INPUT => [
                self::ENACH_ACK_MANDATE_DATE,
                self::ENACH_ACK_BATCH,
                self::ENACH_ACK_IHNO,
                self::ENACH_ACK_MANDATE_TYPE,
                self::ENACH_ACK_UMRN,
                self::ENACH_ACK_REF_1,
                self::ENACH_ACK_REF_2,
                self::ENACH_ACK_CUST_NAME,
                self::ENACH_ACK_BANK,
                self::ENACH_ACK_BRANCH,
                self::ENACH_ACK_BANK_CODE,
                self::ENACH_ACK_AC_TYPE,
                self::ENACH_ACK_ACNO,
                self::ENACH_ACK_ACK_DATE,
                self::ENACH_ACK_ACK_DESC,
                self::ENACH_ACK_AMOUNT,
                self::ENACH_ACK_FREQUENCY,
                self::ENACH_ACK_TEL_NO,
                self::ENACH_ACK_MOBILE_NO,
                self::ENACH_ACK_MAIL_ID,
                self::ENACH_ACK_UPLOAD_BATCH,
                self::ENACH_ACK_UPLOAD_DATE,
                self::ENACH_ACK_UPDATE_DATE,
                self::ENACH_ACK_SOLE_ID,
            ],
            self::OUTPUT => [
                self::ENACH_ACK_MANDATE_DATE,
                self::ENACH_ACK_BATCH,
                self::ENACH_ACK_IHNO,
                self::ENACH_ACK_MANDATE_TYPE,
                self::ENACH_ACK_UMRN,
                self::ENACH_ACK_REF_1,
                self::ENACH_ACK_REF_2,
                self::ENACH_ACK_CUST_NAME,
                self::ENACH_ACK_BANK,
                self::ENACH_ACK_BRANCH,
                self::ENACH_ACK_BANK_CODE,
                self::ENACH_ACK_AC_TYPE,
                self::ENACH_ACK_ACNO,
                self::ENACH_ACK_ACK_DATE,
                self::ENACH_ACK_ACK_DESC,
                self::ENACH_ACK_AMOUNT,
                self::ENACH_ACK_FREQUENCY,
                self::ENACH_ACK_TEL_NO,
                self::ENACH_ACK_MOBILE_NO,
                self::ENACH_ACK_MAIL_ID,
                self::ENACH_ACK_UPLOAD_BATCH,
                self::ENACH_ACK_UPLOAD_DATE,
                self::ENACH_ACK_UPDATE_DATE,
                self::ENACH_ACK_SOLE_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
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
            self::OUTPUT => [
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
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        'nach_register_nach_citi' => [
            self::INPUT => [
                self::CITI_NACH_REGISTER_SERIAL_NUMBER,
                self::CITI_NACH_REGISTER_CATEGORY_CODE,
                self::CITI_NACH_REGISTER_CATEGORY_DESCRIPTION,
                self::CITI_NACH_REGISTER_START_DATE,
                self::CITI_NACH_REGISTER_END_DATE,
                self::CITI_NACH_REGISTER_CLIENT_CODE,
                self::CITI_NACH_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO,
                self::CITI_NACH_REGISTER_CUSTOMER_ACCOUNT_NUMBER,
                self::CITI_NACH_REGISTER_CUSTOMER_NAME,
                self::CITI_NACH_REGISTER_ACCOUNT_TYPE,
                self::CITI_NACH_REGISTER_BANK_NAME,
                self::CITI_NACH_REGISTER_BANK_IFSC,
                self::CITI_NACH_REGISTER_AMOUNT,
                self::CITI_NACH_REGISTER_LOT,
                self::CITI_NACH_REGISTER_SOFT_COPY_RECEIVED_DATE,
                self::CITI_NACH_REGISTER_STATUS,
                self::CITI_NACH_REGISTER_REMARKS,
                self::CITI_NACH_REGISTER_UMRN,
            ],
            self::OUTPUT => [
                self::CITI_NACH_REGISTER_SERIAL_NUMBER,
                self::CITI_NACH_REGISTER_CATEGORY_CODE,
                self::CITI_NACH_REGISTER_CATEGORY_DESCRIPTION,
                self::CITI_NACH_REGISTER_START_DATE,
                self::CITI_NACH_REGISTER_END_DATE,
                self::CITI_NACH_REGISTER_CLIENT_CODE,
                self::CITI_NACH_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO,
                self::CITI_NACH_REGISTER_CUSTOMER_ACCOUNT_NUMBER,
                self::CITI_NACH_REGISTER_CUSTOMER_NAME,
                self::CITI_NACH_REGISTER_ACCOUNT_TYPE,
                self::CITI_NACH_REGISTER_BANK_NAME,
                self::CITI_NACH_REGISTER_BANK_IFSC,
                self::CITI_NACH_REGISTER_AMOUNT,
                self::CITI_NACH_REGISTER_LOT,
                self::CITI_NACH_REGISTER_SOFT_COPY_RECEIVED_DATE,
                self::CITI_NACH_REGISTER_STATUS,
                self::CITI_NACH_REGISTER_REMARKS,
                self::CITI_NACH_REGISTER_UMRN,
            ],
        ],

        'nach_debit_nach_citi' => [
            self::INPUT => [
                self::CITI_NACH_DEBIT_ACH_TRANSACTION_CODE,
                self::CITI_NACH_DEBIT_CONTROL_9S,
                self::CITI_NACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::CITI_NACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::CITI_NACH_DEBIT_CONTROL_15S,
                self::CITI_NACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::CITI_NACH_DEBIT_CONTROL_9SS,
                self::CITI_NACH_DEBIT_CONTROL_7S,
                self::CITI_NACH_DEBIT_USER_NAME,
                self::CITI_NACH_DEBIT_CONTROL_13S,
                self::CITI_NACH_DEBIT_AMOUNT,
                self::CITI_NACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::CITI_NACH_DEBIT_CHECKSUM,
                self::CITI_NACH_DEBIT_FLAG,
                self::CITI_NACH_DEBIT_REASON_CODE,
                self::CITI_NACH_DEBIT_DESTINATION_BANK_IFSC,
                self::CITI_NACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::CITI_NACH_DEBIT_SPONSOR_BANK_IFSC,
                self::CITI_NACH_DEBIT_USER_NUMBER,
                self::CITI_NACH_DEBIT_TRANSACTION_REFERENCE,
                self::CITI_NACH_DEBIT_PRODUCT_TYPE,
                self::CITI_NACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::CITI_NACH_DEBIT_UMRN,
                self::CITI_NACH_DEBIT_FILLER,
            ],
            self::OUTPUT => [
                self::CITI_NACH_DEBIT_ACH_TRANSACTION_CODE,
                self::CITI_NACH_DEBIT_CONTROL_9S,
                self::CITI_NACH_DEBIT_DESTINATION_ACCOUNT_TYPE,
                self::CITI_NACH_DEBIT_LEDGER_FOLIO_NUMBER,
                self::CITI_NACH_DEBIT_CONTROL_15S,
                self::CITI_NACH_DEBIT_BENEFICIARY_ACCOUNT_HOLDER_NAME,
                self::CITI_NACH_DEBIT_CONTROL_9SS,
                self::CITI_NACH_DEBIT_CONTROL_7S,
                self::CITI_NACH_DEBIT_USER_NAME,
                self::CITI_NACH_DEBIT_CONTROL_13S,
                self::CITI_NACH_DEBIT_AMOUNT,
                self::CITI_NACH_DEBIT_ACH_ITEM_SEQ_NO,
                self::CITI_NACH_DEBIT_CHECKSUM,
                self::CITI_NACH_DEBIT_FLAG,
                self::CITI_NACH_DEBIT_REASON_CODE,
                self::CITI_NACH_DEBIT_DESTINATION_BANK_IFSC,
                self::CITI_NACH_DEBIT_BENEFICIARY_BANK_ACCOUNT_NUMBER,
                self::CITI_NACH_DEBIT_SPONSOR_BANK_IFSC,
                self::CITI_NACH_DEBIT_USER_NUMBER,
                self::CITI_NACH_DEBIT_TRANSACTION_REFERENCE,
                self::CITI_NACH_DEBIT_PRODUCT_TYPE,
                self::CITI_NACH_DEBIT_BENEFICIARY_AADHAR_NUMBER,
                self::CITI_NACH_DEBIT_UMRN,
                self::CITI_NACH_DEBIT_FILLER,
            ],
        ],

        Type::SUB_MERCHANT => [

            self::INPUT  => [
                self::FEE_BEARER,
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
                self::BUSINESS_CATEGORY,
                self::BUSINESS_SUB_CATEGORY,
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
                self::PROMOTER_PAN,
                self::PROMOTER_PAN_NAME,
                self::WEBSITE_URL,
                self::BANK_ACCOUNT_NAME,
                self::BANK_BRANCH_IFSC,
                self::BANK_ACCOUNT_NUMBER,
                self::COMPANY_CIN,
                self::COMPANY_PAN,
                self::COMPANY_PAN_NAME,
            ],

            self::OUTPUT => [
                self::FEE_BEARER,
                self::MERCHANT_ID,
                self::MERCHANT_NAME,
                self::MERCHANT_EMAIL,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::MERCHANT_UPLOAD_MIQ => [
            self::INPUT => [
                self::MIQ_MERCHANT_NAME,
                self::MIQ_DBA_NAME,
                self::MIQ_WEBSITE,
                self::MIQ_WEBSITE_ABOUT_US,
                self::MIQ_WEBSITE_TERMS_CONDITIONS,
                self::MIQ_WEBSITE_CONTACT_US,
                self::MIQ_WEBSITE_PRIVACY_POLICY,
                self::MIQ_WEBSITE_PRODUCT_PRICING,
                self::MIQ_WEBSITE_REFUNDS,
                self::MIQ_WEBSITE_CANCELLATION,
                self::MIQ_WEBSITE_SHIPPING_DELIVERY,
                self::MIQ_CONTACT_NAME,
                self::MIQ_CONTACT_EMAIL,
                self::MIQ_TXN_REPORT_EMAIL,
                self::MIQ_ADDRESS,
                self::MIQ_CITY,
                self::MIQ_PIN_CODE,
                self::MIQ_STATE,
                self::MIQ_CONTACT_NUMBER,
                self::MIQ_BUSINESS_TYPE,
                self::MIQ_CIN,
                self::MIQ_BUSINESS_PAN,
                self::MIQ_BUSINESS_NAME,
                self::MIQ_AUTHORISED_SIGNATORY_PAN,
                self::MIQ_PAN_OWNER_NAME,
                self::MIQ_BUSINESS_CATEGORY,
                self::MIQ_SUB_CATEGORY,
                self::MIQ_GSTIN,
                self::MIQ_BUSINESS_DESCRIPTION,
                self::MIQ_ESTD_DATE,
                self::MIQ_FEE_MODEL,
                self::MIQ_NB_FEE_TYPE,
                self::MIQ_NB_FEE_BEARER,
                self::MIQ_AXIS,
                self::MIQ_HDFC,
                self::MIQ_ICICI,
                self::MIQ_SBI,
                self::MIQ_YES,
                self::MIQ_NB_ANY,
                self::MIQ_DEBIT_CARD_FEE_TYPE,
                self::MIQ_DEBIT_CARD_FEE_BEARER,
                self::MIQ_DEBIT_CARD_0_2K,
                self::MIQ_DEBIT_CARD_2K_1CR,
                self::MIQ_RUPAY_FEE_TYPE,
                self::MIQ_RUPAY_FEE_BEARER,
                self::MIQ_RUPAY_0_2K,
                self::MIQ_RUPAY_2K_1CR,
                self::MIQ_UPI_FEE_TYPE,
                self::MIQ_UPI_FEE_BEARER,
                self::MIQ_UPI,
                self::MIQ_WALLETS_FEE_TYPE,
                self::MIQ_WALLETS_FEE_BEARER,
                self::MIQ_WALLETS_FREECHARGE,
                self::MIQ_WALLETS_ANY,
                self::MIQ_CREDIT_CARD_FEE_TYPE,
                self::MIQ_CREDIT_CARD_FEE_BEARER,
                self::MIQ_CREDIT_CARD_0_2K,
                self::MIQ_CREDIT_CARD_2K_1CR,
                self::MIQ_INTERNATIONAL,
                self::MIQ_INTL_CARD_FEE_TYPE,
                self::MIQ_INTL_CARD_FEE_BEARER,
                self::MIQ_INTERNATIONAL_CARD,
                self::MIQ_BUSINESS_FEE_TYPE,
                self::MIQ_BUSINESS_FEE_BEARER,
                self::MIQ_BUSINESS,
                self::MIQ_BANK_ACC_NUMBER,
                self::MIQ_BENEFICIARY_NAME,
                self::MIQ_BRANCH_IFSC_CODE
            ],

            self::OUTPUT => [
                self::MIQ_MERCHANT_NAME,
                self::MIQ_DBA_NAME,
                self::MIQ_WEBSITE,
                self::MIQ_WEBSITE_ABOUT_US,
                self::MIQ_WEBSITE_TERMS_CONDITIONS,
                self::MIQ_WEBSITE_CONTACT_US,
                self::MIQ_WEBSITE_PRIVACY_POLICY,
                self::MIQ_WEBSITE_PRODUCT_PRICING,
                self::MIQ_WEBSITE_REFUNDS,
                self::MIQ_WEBSITE_CANCELLATION,
                self::MIQ_WEBSITE_SHIPPING_DELIVERY,
                self::MIQ_CONTACT_NAME,
                self::MIQ_CONTACT_EMAIL,
                self::MIQ_TXN_REPORT_EMAIL,
                self::MIQ_ADDRESS,
                self::MIQ_CITY,
                self::MIQ_PIN_CODE,
                self::MIQ_STATE,
                self::MIQ_CONTACT_NUMBER,
                self::MIQ_BUSINESS_TYPE,
                self::MIQ_CIN,
                self::MIQ_BUSINESS_PAN,
                self::MIQ_BUSINESS_NAME,
                self::MIQ_AUTHORISED_SIGNATORY_PAN,
                self::MIQ_PAN_OWNER_NAME,
                self::MIQ_BUSINESS_CATEGORY,
                self::MIQ_SUB_CATEGORY,
                self::MIQ_GSTIN,
                self::MIQ_BUSINESS_DESCRIPTION,
                self::MIQ_ESTD_DATE,
                self::MIQ_FEE_MODEL,
                self::MIQ_NB_FEE_TYPE,
                self::MIQ_NB_FEE_BEARER,
                self::MIQ_AXIS,
                self::MIQ_HDFC,
                self::MIQ_ICICI,
                self::MIQ_SBI,
                self::MIQ_YES,
                self::MIQ_NB_ANY,
                self::MIQ_DEBIT_CARD_FEE_TYPE,
                self::MIQ_DEBIT_CARD_FEE_BEARER,
                self::MIQ_DEBIT_CARD_0_2K,
                self::MIQ_DEBIT_CARD_2K_1CR,
                self::MIQ_RUPAY_FEE_TYPE,
                self::MIQ_RUPAY_FEE_BEARER,
                self::MIQ_RUPAY_0_2K,
                self::MIQ_RUPAY_2K_1CR,
                self::MIQ_UPI_FEE_TYPE,
                self::MIQ_UPI_FEE_BEARER,
                self::MIQ_UPI,
                self::MIQ_WALLETS_FEE_TYPE,
                self::MIQ_WALLETS_FEE_BEARER,
                self::MIQ_WALLETS_FREECHARGE,
                self::MIQ_WALLETS_ANY,
                self::MIQ_CREDIT_CARD_FEE_TYPE,
                self::MIQ_CREDIT_CARD_FEE_BEARER,
                self::MIQ_CREDIT_CARD_0_2K,
                self::MIQ_CREDIT_CARD_2K_1CR,
                self::MIQ_INTERNATIONAL,
                self::MIQ_INTL_CARD_FEE_TYPE,
                self::MIQ_INTL_CARD_FEE_BEARER,
                self::MIQ_INTERNATIONAL_CARD,
                self::MIQ_BUSINESS_FEE_TYPE,
                self::MIQ_BUSINESS_FEE_BEARER,
                self::MIQ_BUSINESS,
                self::MIQ_BANK_ACC_NUMBER,
                self::MIQ_BENEFICIARY_NAME,
                self::MIQ_BRANCH_IFSC_CODE,
                self::MIQ_OUT_FEE_BEARER,
                self::MIQ_OUT_MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::DIRECT_DEBIT  => [
            self::INPUT => [
                self::DIRECT_DEBIT_EMAIL,
                self::DIRECT_DEBIT_CONTACT,
                self::DIRECT_DEBIT_CARD_NUMBER,
                self::DIRECT_DEBIT_EXPIRY_MONTH,
                self::DIRECT_DEBIT_EXPIRY_YEAR,
                self::DIRECT_DEBIT_CARDHOLDER_NAME,
                self::DIRECT_DEBIT_AMOUNT,
                self::DIRECT_DEBIT_CURRENCY,
                self::DIRECT_DEBIT_RECEIPT,
                self::DIRECT_DEBIT_DESCRIPTION,
                self::NOTES,
            ],

            self::OUTPUT    => [
                self::DIRECT_DEBIT_EMAIL,
                self::DIRECT_DEBIT_CONTACT,
                self::DIRECT_DEBIT_CARD_NUMBER,
                self::DIRECT_DEBIT_EXPIRY_MONTH,
                self::DIRECT_DEBIT_EXPIRY_YEAR,
                self::DIRECT_DEBIT_CARDHOLDER_NAME,
                self::DIRECT_DEBIT_AMOUNT,
                self::DIRECT_DEBIT_CURRENCY,
                self::DIRECT_DEBIT_RECEIPT,
                self::DIRECT_DEBIT_DESCRIPTION,
                self::NOTES,
                self::DIRECT_DEBIT_ORDER_ID,
                self::DIRECT_DEBIT_PAYMENT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
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

        Type::OAUTH_MIGRATION_TOKEN => [

            self::INPUT => [
                self::MERCHANT_ID,
            ],

            self::OUTPUT => [
                self::MERCHANT_ID,
                self::ACCESS_TOKEN,
                self::PUBLIC_TOKEN,
                self::REFRESH_TOKEN,
                self::EXPIRES_IN,

                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PARTNER_SUBMERCHANTS => [

            self::INPUT => [
                self::PARTNER_MERCHANT_ID,
                self::PARTNER_TYPE,
                self::SUBMERCHANT_ID,
            ],

            self::OUTPUT => [
                self::PARTNER_MERCHANT_ID,
                self::PARTNER_TYPE,
                self::SUBMERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PARTNER_SUBMERCHANT_INVITE => [

            self::INPUT => [
                self::ACCOUNT_NAME,
                self::EMAIL,
            ],

            self::OUTPUT => [
                self::ACCOUNT_ID,
                self::ACCOUNT_NAME,
                self::EMAIL,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PARTNER_SUBMERCHANT_INVITE_CAPITAL => [
            self::INPUT => [
                self::BUSINESS_NAME,
                self::ACCOUNT_NAME,
                self::CONTACT_MOBILE,
                self::EMAIL,
                self::ANNUAL_TURNOVER_MIN,
                self::ANNUAL_TURNOVER_MAX,
                self::COMPANY_ADDRESS_LINE_1,
                self::COMPANY_ADDRESS_LINE_2,
                self::COMPANY_ADDRESS_CITY,
                self::COMPANY_ADDRESS_STATE,
                self::COMPANY_ADDRESS_COUNTRY,
                self::COMPANY_ADDRESS_PINCODE,
                self::BUSINESS_TYPE,
                self::BUSINESS_VINTAGE,
                self::GSTIN,
                self::PROMOTER_PAN
            ],

            self::OUTPUT => [
                self::ACCOUNT_ID,
                self::ACCOUNT_NAME,
                self::CONTACT_MOBILE,
                self::EMAIL,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::PARTNER_REFERRAL_FETCH => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::REFERRAL_PRODUCT
            ],

            self::OUTPUT => [
                self::MERCHANT_ID,
                self::REFERRAL_PRODUCT,
                self::REFERRAL_ID,
                self::REF_CODE,
                self::URL,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION
            ]
        ],

        Type::ENTITY_MAPPING => [
            self::INPUT => [
                self::ENTITY_FROM_ID,
                self::ENTITY_TO_ID,
            ],

            self::OUTPUT => [
                self::ENTITY_FROM_ID,
                self::ENTITY_TO_IDS,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
                ]
            ],

        Type::AUTH_LINK => [

            self::INPUT => [
                self::AUTH_LINK_CUSTOMER_NAME,
                self::AUTH_LINK_CUSTOMER_EMAIL,
                self::AUTH_LINK_CUSTOMER_PHONE,
                self::AUTH_LINK_AMOUNT_IN_PAISE,
                self::AUTH_LINK_CURRENCY,
                self::AUTH_LINK_METHOD,
                self::AUTH_LINK_TOKEN_EXPIRE_BY,
                self::AUTH_LINK_MAX_AMOUNT,
                self::AUTH_LINK_AUTH_TYPE,
                self::AUTH_LINK_BANK,
                self::AUTH_LINK_NAME_ON_ACCOUNT,
                self::AUTH_LINK_IFSC,
                self::AUTH_LINK_ACCOUNT_NUMBER,
                self::AUTH_LINK_ACCOUNT_TYPE,
                self::AUTH_LINK_RECEIPT,
                self::AUTH_LINK_DESCRIPTION,
                self::AUTH_LINK_EXPIRE_BY,
                self::NOTES,
            ],

            self::OUTPUT => [
                self::AUTH_LINK_CUSTOMER_NAME,
                self::AUTH_LINK_CUSTOMER_EMAIL,
                self::AUTH_LINK_CUSTOMER_PHONE,
                self::AUTH_LINK_AMOUNT_IN_PAISE,
                self::AUTH_LINK_CURRENCY,
                self::AUTH_LINK_METHOD,
                self::AUTH_LINK_TOKEN_EXPIRE_BY,
                self::AUTH_LINK_MAX_AMOUNT,
                self::AUTH_LINK_AUTH_TYPE,
                self::AUTH_LINK_BANK,
                self::AUTH_LINK_NAME_ON_ACCOUNT,
                self::AUTH_LINK_IFSC,
                self::AUTH_LINK_ACCOUNT_NUMBER,
                self::AUTH_LINK_ACCOUNT_TYPE,
                self::AUTH_LINK_RECEIPT,
                self::AUTH_LINK_DESCRIPTION,
                self::AUTH_LINK_EXPIRE_BY,
                self::AUTH_LINK_NACH_REFERENCE1,
                self::AUTH_LINK_NACH_REFERENCE2,
                self::AUTH_LINK_NACH_CREATE_FORM,
                self::NOTES,
                self::STATUS,
                self::AUTH_LINK_ID,
                self::AUTH_LINK_SHORT_URL,
                self::AUTH_LINK_NACH_PRI_FILLED_FORM,
                self::AUTH_LINK_STATUS,
                self::AUTH_LINK_CREATED_AT,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::INSTANT_ACTIVATION => [
            self::INPUT => [
                self::MERCHANT_ID,
            ],

            self::OUTPUT => [
                self::MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        'terminal_netbanking_icici' => [
            self::INPUT => [
                self::ICIC_NB_GATEWAY_MID,
                self::ICIC_NB_GATEWAY_MID2,
                self::ICIC_NB_MERCHANT_ID,
                self::ICIC_NB_SECTOR,
                self::ICIC_NB_SUB_IDS,
            ],
            self::OUTPUT => [
                self::ICIC_NB_GATEWAY_MID,
                self::ICIC_NB_GATEWAY_MID2,
                self::ICIC_NB_MERCHANT_ID,
                self::ICIC_NB_SECTOR,
                self::ICIC_NB_SUB_IDS,
                self::ICIC_NB_TERMINAL_ID,
                self::STATUS,
                self::FAILURE_REASON
            ]
        ],

        'terminal_netbanking_hdfc' => [
            self::INPUT => [
                self::HDFC_NB_MERCHANT_ID,
                self::HDFC_NB_GATEWAY_MERCHANT_ID,
                self::HDFC_NB_CATEGORY,
                self::HDFC_NB_TPV
            ],
            self::OUTPUT => [
                self::HDFC_NB_MERCHANT_ID,
                self::HDFC_NB_TERMINAL_ID,
                self::HDFC_NB_CATEGORY,
                self::HDFC_NB_TPV,
                self::STATUS,
                self::FAILURE_REASON
            ]
        ],
        'terminal_earlysalary' => [
            self::INPUT => [
                self::EARLYSALARY_MERCHANT_ID,
                self::EARLYSALARY_GATEWAY_MERCHANT_ID,
                self::EARLYSALARY_GATEWAY_MERCHANT_ID2,
                self::EARLYSALARY_CATEGORY,
            ],
            self::OUTPUT => [
                self::EARLYSALARY_MERCHANT_ID,
                self::EARLYSALARY_TERMINAL_ID,
                self::EARLYSALARY_GATEWAY_MERCHANT_ID,
                self::EARLYSALARY_GATEWAY_MERCHANT_ID2,
                self::EARLYSALARY_CATEGORY,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],
        'terminal_flexmoney' => [
            self::INPUT => [
                self::FLEXMONEY_MERCHANT_ID,
                self::FLEXMONEY_GATEWAY_MERCHANT_ID,
                self::FLEXMONEY_GATEWAY_MERCHANT_ID2,
                self::FLEXMONEY_CATEGORY,
            ],
            self::OUTPUT => [
                self::FLEXMONEY_MERCHANT_ID,
                self::FLEXMONEY_TERMINAL_ID,
                self::FLEXMONEY_GATEWAY_MERCHANT_ID,
                self::FLEXMONEY_GATEWAY_MERCHANT_ID2,
                self::FLEXMONEY_CATEGORY,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],
        'terminal_zestmoney' => [
            self::INPUT => [
                self::ZESTMONEY_MERCHANT_ID,
                self::ZESTMONEY_GATEWAY_MERCHANT_ID,
                self::ZESTMONEY_GATEWAY_MERCHANT_ID2,
                self::ZESTMONEY_CATEGORY,
            ],
            self::OUTPUT => [
                self::ZESTMONEY_MERCHANT_ID,
                self::ZESTMONEY_TERMINAL_ID,
                self::ZESTMONEY_GATEWAY_MERCHANT_ID,
                self::ZESTMONEY_GATEWAY_MERCHANT_ID2,
                self::ZESTMONEY_CATEGORY,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],
        'terminal_netbanking_axis' => [
            self::INPUT => [
                self::AXIS_NB_MERCHANT_ID,
                self::AXIS_NB_GATEWAY_MERCHANT_ID,
                self::AXIS_NB_CATEGORY,
                self::AXIS_NB_TPV,
                self::AXIS_NB_NON_RECURRING,
            ],
            self::OUTPUT => [
                self::AXIS_NB_MERCHANT_ID,
                self::AXIS_NB_TERMINAL_ID,
                self::AXIS_NB_GATEWAY_MERCHANT_ID,
                self::AXIS_NB_CATEGORY,
                self::AXIS_NB_TPV,
                self::AXIS_NB_NON_RECURRING,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],

        'terminal_billdesk' => [
            self::INPUT => [
                self::BILLDESK_MERCHANT_ID,
                self::BILLDESK_GATEWAY_MERCHANT_ID,
                self::BILLDESK_CATEGORY,
                self::BILLDESK_NON_RECURRING,
            ],
            self::OUTPUT => [
                self::BILLDESK_MERCHANT_ID,
                self::BILLDESK_TERMINAL_ID,
                self::BILLDESK_GATEWAY_MERCHANT_ID,
                self::BILLDESK_CATEGORY,
                self::BILLDESK_NON_RECURRING,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],

        'terminal_hitachi' => [
            self::INPUT => [
                self::HITACHI_RID,
                self::HITACHI_MERCHANT_ID,
                self::HITACHI_SUB_IDS,
                self::HITACHI_MID,
                self::HITACHI_TID,
                self::HITACHI_PART_NAME,
                self::HITACHI_ME_NAME,
                self::HITACHI_LOCATION,
                self::HITACHI_CITY,
                self::HITACHI_STATE,
                self::HITACHI_COUNTRY,
                self::HITACHI_MCC,
                self::HITACHI_TERM_STATUS,
                self::HITACHI_ME_STATUS,
                self::HITACHI_ZIPCODE,
                self::HITACHI_SWIPER_ID,
                self::HITACHI_SPONSOR_BANK,
                self::HITACHI_CURRENCY,
            ],
            self::OUTPUT => [
                self::HITACHI_RID,
                self::HITACHI_MERCHANT_ID,
                self::HITACHI_SUB_IDS,
                self::HITACHI_MID,
                self::HITACHI_TID,
                self::HITACHI_PART_NAME,
                self::HITACHI_ME_NAME,
                self::HITACHI_LOCATION,
                self::HITACHI_CITY,
                self::HITACHI_STATE,
                self::HITACHI_COUNTRY,
                self::HITACHI_MCC,
                self::HITACHI_TERM_STATUS,
                self::HITACHI_ME_STATUS,
                self::HITACHI_ZIPCODE,
                self::HITACHI_SWIPER_ID,
                self::HITACHI_SPONSOR_BANK,
                self::HITACHI_CURRENCY,
                self::HITACHI_TERMINAL_ID,
                self::STATUS,
                self::FAILURE_REASON,
            ],
        ],

        Type::CONTACT => [
            self::INPUT => [
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                self::NOTES,
            ],
            self::OUTPUT => [
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                self::NOTES,
                self::CONTACT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::FUND_ACCOUNT => [
            self::INPUT => [
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PROVIDER,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::FUND_ACCOUNT_EMAIL,
                self::CONTACT_ID,
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                // Contact's notes.
                self::NOTES,
            ],
            self::OUTPUT => [
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::FUND_ACCOUNT_EMAIL,
                self::CONTACT_ID,
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                // Contact's notes.
                self::NOTES,
                self::FUND_ACCOUNT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::FUND_ACCOUNT_V2 => [
            self::INPUT => [
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_BANK_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PROVIDER,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::FUND_ACCOUNT_EMAIL,
                self::CONTACT_ID,
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                self::CONTACT_GSTIN,
                self::CONTACT_PAN,
                // Contact's notes.
                self::NOTES,
            ],
            self::OUTPUT => [
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_BANK_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::FUND_ACCOUNT_EMAIL,
                self::CONTACT_ID,
                self::CONTACT_TYPE,
                self::CONTACT_NAME_2,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                self::CONTACT_GSTIN,
                self::CONTACT_PAN,
                // Contact's notes.
                self::NOTES,
                self::FUND_ACCOUNT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYOUT => [
            self::INPUT => [
                self::RAZORPAYX_ACCOUNT_NUMBER,
                self::PAYOUT_AMOUNT,
                // Payout amount in rupees
                self::PAYOUT_AMOUNT_RUPEES,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_MODE,
                self::PAYOUT_PURPOSE,
                self::FUND_ACCOUNT_ID,
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::CONTACT_NAME_2,
                self::FUND_ACCOUNT_EMAIL,
                self::PAYOUT_NARRATION,
                self::PAYOUT_REFERENCE_ID,
                self::CONTACT_TYPE,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                // Payout's notes.
                self::NOTES,
            ],
            self::OUTPUT => [
                self::RAZORPAYX_ACCOUNT_NUMBER,
                self::PAYOUT_AMOUNT,
                // Payout amount in rupees
                self::PAYOUT_AMOUNT_RUPEES,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_MODE,
                self::PAYOUT_PURPOSE,
                self::FUND_ACCOUNT_ID,
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::FUND_ACCOUNT_PHONE_NUMBER,
                self::CONTACT_NAME_2,
                self::FUND_ACCOUNT_EMAIL,
                self::PAYOUT_NARRATION,
                self::PAYOUT_REFERENCE_ID,
                self::CONTACT_TYPE,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::CONTACT_REFERENCE_ID,
                // Payout's notes.
                self::NOTES,
                self::PAYOUT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],
        Type::TALLY_PAYOUT => [
            self::INPUT => [
                self::RAZORPAYX_ACCOUNT_NUMBER,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_REFERENCE_ID,
                self::PAYOUT_MODE,
                self::PAYOUT_AMOUNT_RUPEES,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_DATE,
                self::PAYOUT_NARRATION,
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::CONTACT_NAME_2,
                self::CONTACT_TYPE,
                self::CONTACT_ADDRESS,
                self::CONTACT_CITY,
                self::CONTACT_ZIPCODE,
                self::CONTACT_STATE,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::NOTES_STR_VALUE,
            ],
            self::OUTPUT => [
                self::RAZORPAYX_ACCOUNT_NUMBER,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_REFERENCE_ID,
                self::PAYOUT_MODE,
                self::PAYOUT_AMOUNT,
                self::PAYOUT_CURRENCY,
                self::PAYOUT_DATE,
                self::PAYOUT_NARRATION,
                self::FUND_ACCOUNT_TYPE,
                self::FUND_ACCOUNT_NAME,
                self::FUND_ACCOUNT_IFSC,
                self::FUND_ACCOUNT_NUMBER,
                self::FUND_ACCOUNT_VPA,
                self::CONTACT_NAME_2,
                self::CONTACT_TYPE,
                self::CONTACT_ADDRESS,
                self::CONTACT_CITY,
                self::CONTACT_ZIPCODE,
                self::CONTACT_STATE,
                self::CONTACT_EMAIL_2,
                self::CONTACT_MOBILE_2,
                self::NOTES_STR_VALUE,
                self::PAYOUT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::CREDIT => [
          self::INPUT => [
              self::CREDITS_MERCHANT_ID,
              self::CREDIT_POINTS,
              self::CAMPAIGN,
              self::REMARKS,
              self::PRODUCT,
              self::TYPE,
          ],
          self::OUTPUT => [
              self::ID,
              self::CREDITS_MERCHANT_ID,
              self::CREDIT_POINTS,
              self::CAMPAIGN,
              self::REMARKS,
              self::PRODUCT,
              self::TYPE,
              self::CREATOR_NAME,
              self::CREATED_AT,
              self::ERROR_CODE,
              self::ERROR_DESCRIPTION,
          ]
        ],

        Type::LINKED_ACCOUNT_REVERSAL => [
            self::INPUT => [
                self::TRANSFER_ID,
                self::AMOUNT_IN_PAISE,
                self::NOTES,
            ],
            self::OUTPUT => [
                self::REVERSAL_ID,
                self::TRANSFER_ID,
                self::AMOUNT_IN_PAISE,
                self::INITIATOR_ID,
                self::REFUND_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::SUBMERCHANT_ASSIGN => [
            self::INPUT => [
                self::SUBMERCHANT_ID,
                self::TERMINAL_ID,
            ],
            self::OUTPUT => [
                self::SUBMERCHANT_ID,
                self::TERMINAL_ID,
                self::STATUS,
                self::FAILURE_REASON,
            ]
        ],

        Type::IIN_NPCI_RUPAY => [
            self::INPUT => [
            ],
            self::OUTPUT => [
                self::IIN_NPCI_RUPAY_ROW,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::IIN_HITACHI_VISA => [
            self::INPUT => [
                self::IIN_HITACHI_VISA_ROW,
            ],
            self::OUTPUT => [
                self::IIN_HITACHI_VISA_ROW,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::IIN_MC_MASTERCARD => [
            self::INPUT => [
                self::IIN_MC_MASTERCARD_COMPANY_ID,
                self::IIN_MC_MASTERCARD_COMPANY_NAME,
                self::IIN_MC_MASTERCARD_ICA,
                self::IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM,
                self::IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO,
                self::IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE,
                self::IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME,
                self::IIN_MC_MASTERCARD_ACCEPTANCE_BRAND,
                self::IIN_MC_MASTERCARD_COUNTRY,
                self::IIN_MC_MASTERCARD_REGION,
            ],
            self::OUTPUT => [
                self::IIN_MC_MASTERCARD_COMPANY_ID,
                self::IIN_MC_MASTERCARD_COMPANY_NAME,
                self::IIN_MC_MASTERCARD_ICA,
                self::IIN_MC_MASTERCARD_ACCOUNT_RANGE_FROM,
                self::IIN_MC_MASTERCARD_ACCOUNT_RANGE_TO,
                self::IIN_MC_MASTERCARD_BRAND_PRODUCT_CODE,
                self::IIN_MC_MASTERCARD_BRAND_PRODUCT_NAME,
                self::IIN_MC_MASTERCARD_ACCEPTANCE_BRAND,
                self::IIN_MC_MASTERCARD_COUNTRY,
                self::IIN_MC_MASTERCARD_REGION,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::PRICING_RULE => [
            self::INPUT => [
                self::PRICING_RULE_MERCHANT_ID,
                self::PRICING_RULE_PRODUCT,
                self::PRICING_RULE_FEATURE,
                self::PRICING_RULE_PAYMENT_METHOD,
                self::PRICING_RULE_PAYMENT_METHOD_TYPE,
                self::PRICING_RULE_PAYMENT_METHOD_SUBTYPE,
                self::PRICING_RULE_PAYMENT_NETWORK,
                self::PRICING_RULE_INTERNATIONAL,
                self::PRICING_RULE_PERCENT_RATE,
                self::PRICING_RULE_PERCENT_RATE_SCALE_FACTOR,
                self::PRICING_RULE_FIXED_RATE,
                self::PRICING_RULE_AMOUNT_RANGE_ACTIVE,
                self::PRICING_RULE_AMOUNT_RANGE_MIN,
                self::PRICING_RULE_AMOUNT_RANGE_MAX,
                self::PRICING_RULE_RECEIVER_TYPE,
                self::PRICING_RULE_PROCURER,
            ],
            self::OUTPUT => [
                self::PRICING_RULE_MERCHANT_ID,
                self::PRICING_RULE_PRODUCT,
                self::PRICING_RULE_FEATURE,
                self::PRICING_RULE_PAYMENT_METHOD,
                self::PRICING_RULE_PAYMENT_METHOD_TYPE,
                self::PRICING_RULE_PAYMENT_METHOD_SUBTYPE,
                self::PRICING_RULE_PAYMENT_NETWORK,
                self::PRICING_RULE_INTERNATIONAL,
                self::PRICING_RULE_PERCENT_RATE,
                self::PRICING_RULE_PERCENT_RATE_SCALE_FACTOR,
                self::PRICING_RULE_FIXED_RATE,
                self::PRICING_RULE_AMOUNT_RANGE_ACTIVE,
                self::PRICING_RULE_AMOUNT_RANGE_MIN,
                self::PRICING_RULE_AMOUNT_RANGE_MAX,
                self::PRICING_RULE_RECEIVER_TYPE,
                self::PRICING_RULE_PROCURER,
            ]
        ],
        Type::BUY_PRICING_RULE => [
            self::INPUT => [
                self::PRICING_RULE_PLAN_NAME,
                self::PRICING_RULE_PAYMENT_METHOD,
                self::PRICING_RULE_PAYMENT_METHOD_TYPE,
                self::PRICING_RULE_PAYMENT_METHOD_SUBTYPE,
                self::PRICING_RULE_RECEIVER_TYPE,
                self::PRICING_RULE_INTERNATIONAL,
                self::PRICING_RULE_EMI_DURATION,
                self::PRICING_RULE_GATEWAY,
                self::PRICING_RULE_PAYMENT_ISSUER,
                self::PRICING_RULE_PAYMENT_NETWORK,
                self::PRICING_RULE_AMOUNT_RANGE_MIN,
                self::PRICING_RULE_AMOUNT_RANGE_MAX,
                self::PRICING_RULE_PERCENT_RATE,
                self::PRICING_RULE_FIXED_RATE,
                self::PRICING_RULE_MIN_FEE,
                self::PRICING_RULE_MAX_FEE,
                self::PRICING_RULE_PROCURER
            ],
            self::OUTPUT => [
                self::PRICING_RULE_PLAN_NAME,
                self::PRICING_RULE_PAYMENT_METHOD,
                self::PRICING_RULE_PAYMENT_METHOD_TYPE,
                self::PRICING_RULE_PAYMENT_METHOD_SUBTYPE,
                self::PRICING_RULE_RECEIVER_TYPE,
                self::PRICING_RULE_INTERNATIONAL,
                self::PRICING_RULE_EMI_DURATION,
                self::PRICING_RULE_GATEWAY,
                self::PRICING_RULE_PAYMENT_ISSUER,
                self::PRICING_RULE_PAYMENT_NETWORK,
                self::PRICING_RULE_AMOUNT_RANGE_MIN,
                self::PRICING_RULE_AMOUNT_RANGE_MAX,
                self::PRICING_RULE_PERCENT_RATE,
                self::PRICING_RULE_FIXED_RATE,
                self::PRICING_RULE_MIN_FEE,
                self::PRICING_RULE_MAX_FEE,
                self::PRICING_RULE_PROCURER
            ]
        ],
        Type::BUY_PRICING_ASSIGN => [
            self::INPUT => [
                self::TERMINAL_ID,
                self::PRICING_RULE_PLAN_ID,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
            ],
            self::OUTPUT => [
                self::TERMINAL_ID,
                self::PRICING_RULE_PLAN_ID,
                self::TERMINAL_CREATION_NETWORK_CATEGORY,
            ]
        ],
        Type::LOC_WITHDRAWAL => [
            self::INPUT => [
                self::LOC_WITHDRAWAL_TRANSACTION_TYPE,
                self::LOC_WITHDRAWAL_BENEFICIARY_CODE,
                self::LOC_WITHDRAWAL_BENEFICIARY_ACCOUNT_NUMBER,
                self::LOC_WITHDRAWAL_INSTRUMENT_AMOUNT,
                self::LOC_WITHDRAWAL_BENEFICIARY_NAME,
                self::LOC_WITHDRAWAL_DRAWEE_LOCATION,
                self::LOC_WITHDRAWAL_PRINT_LOCATION,
                self::LOC_WITHDRAWAL_BENE_ADDR_1,
                self::LOC_WITHDRAWAL_BENE_ADDR_2,
                self::LOC_WITHDRAWAL_BENE_ADDR_3,
                self::LOC_WITHDRAWAL_BENE_ADDR_4,
                self::LOC_WITHDRAWAL_BENE_ADDR_5,
                self::LOC_WITHDRAWAL_IRN,
                self::LOC_WITHDRAWAL_CRN,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_1,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_2,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_3,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_4,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_5,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_6,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_7,
                self::LOC_WITHDRAWAL_CHEQUE_NUMBER,
                self::LOC_WITHDRAWAL_TRN_DATE,
                self::LOC_WITHDRAWAL_MICR_NUMBER,
                self::LOC_WITHDRAWAL_IFSC_CODE,
                self::LOC_WITHDRAWAL_BENE_BANK_NAME,
                self::LOC_WITHDRAWAL_BENE_BANK_BRANCH_NAME,
                self::LOC_WITHDRAWAL_BENE_EMAIL_ID,
                self::LOC_WITHDRAWAL_CLIENT_CODE,
                self::LOC_WITHDRAWAL_LOAN_AMOUNT,
                self::LOC_WITHDRAWAL_LOAN_INTEREST,
                self::LOC_WITHDRAWAL_LOAN_TENURE,
                self::LOC_WITHDRAWAL_UTR_NUMBER,
            ],
            self::OUTPUT => [
                self::LOC_WITHDRAWAL_TRANSACTION_TYPE,
                self::LOC_WITHDRAWAL_BENEFICIARY_CODE,
                self::LOC_WITHDRAWAL_BENEFICIARY_ACCOUNT_NUMBER,
                self::LOC_WITHDRAWAL_INSTRUMENT_AMOUNT,
                self::LOC_WITHDRAWAL_BENEFICIARY_NAME,
                self::LOC_WITHDRAWAL_DRAWEE_LOCATION,
                self::LOC_WITHDRAWAL_PRINT_LOCATION,
                self::LOC_WITHDRAWAL_BENE_ADDR_1,
                self::LOC_WITHDRAWAL_BENE_ADDR_2,
                self::LOC_WITHDRAWAL_BENE_ADDR_3,
                self::LOC_WITHDRAWAL_BENE_ADDR_4,
                self::LOC_WITHDRAWAL_BENE_ADDR_5,
                self::LOC_WITHDRAWAL_IRN,
                self::LOC_WITHDRAWAL_CRN,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_1,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_2,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_3,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_4,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_5,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_6,
                self::LOC_WITHDRAWAL_PAYMENT_DETAILS_7,
                self::LOC_WITHDRAWAL_CHEQUE_NUMBER,
                self::LOC_WITHDRAWAL_TRN_DATE,
                self::LOC_WITHDRAWAL_MICR_NUMBER,
                self::LOC_WITHDRAWAL_IFSC_CODE,
                self::LOC_WITHDRAWAL_BENE_BANK_NAME,
                self::LOC_WITHDRAWAL_BENE_BANK_BRANCH_NAME,
                self::LOC_WITHDRAWAL_BENE_EMAIL_ID,
                self::LOC_WITHDRAWAL_CLIENT_CODE,
                self::LOC_WITHDRAWAL_LOAN_AMOUNT,
                self::LOC_WITHDRAWAL_LOAN_INTEREST,
                self::LOC_WITHDRAWAL_LOAN_TENURE,
                self::LOC_WITHDRAWAL_UTR_NUMBER,
            ]
        ],

        Type::ENTITY_UPDATE_ACTION => [
            self::INPUT => [
                self::ID,
                self::BUSINESS_NAME,
                self::BUSINESS_REGISTERED_ADDRESS,
                self::BUSINESS_REGISTERED_STATE,
            ],
            self::OUTPUT => [
                self::ID,
                self::BUSINESS_NAME,
                self::BUSINESS_REGISTERED_ADDRESS,
                self::BUSINESS_REGISTERED_STATE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::ADMIN_BATCH => [
            self::INPUT => [
                self::ADMIN_ID,
            ],

            self::OUTPUT => [
                self::ADMIN_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::VAULT_MIGRATE_TOKEN_NS => [
            self::INPUT => [
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_TOKEN,
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE,
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE
            ],
            self::OUTPUT => [
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_TOKEN,
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_EXISTING_NAMESPACE,
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_BU_NAMESPACE,
                self::VAULT_MIGRATE_TOKEN_NAMESPACE_MIGRATED_TOKEN_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],


        Type::TOKEN_HQ_CHARGE => [
            self::INPUT => [
                self::TOKEN_HQ_AGGREGATE_DATA_ID,
                self::TOKEN_HQ_MERCHANT_ID,
                self::TOKEN_HQ_TYPE,
                self::TOKEN_HQ_COUNT,
                self::TOKEN_HQ_FEE_MODEL,
                self::TOKEN_HQ_CREATED_DATE
            ],
            self::OUTPUT => [
                self::TOKEN_HQ_AGGREGATE_DATA_ID,
                self::TOKEN_HQ_MERCHANT_ID,
                self::TOKEN_HQ_COUNT,
                self::TOKEN_HQ_TYPE,
                self::TOKEN_HQ_FEES,
                self::TOKEN_HQ_TAX,
                self::TOKEN_HQ_REQUEST_PRICING_ID,
                self::TOKEN_HQ_TRANSACTION_ID,
                self::TOKEN_HQ_FEE_MODEL,
                self::TOKEN_HQ_CREATED_DATE,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],


        Type::MERCHANT_CONFIG_INHERITANCE => [
            self::INPUT => [
                self::MERCHANT_CONFIG_INHERITANCE_PARENT_MERCHANT_ID,
                self::MERCHANT_CONFIG_INHERITANCE_MERHCANT_ID,
            ],
            /*
             * Input key is needed for header validation.
             * Not adding output key here
             * Reason is this batch is entirely migrated to batch micro service.
             */
        ],

        Type::ADJUSTMENT => [
            self::INPUT => [
                self::ADJUSTMENT_REFERENCE_ID,
                self::ADJUSTMENT_MERCHANT_ID,
                self::ADJUSTMENT_AMOUNT,
                self::ADJUSTMENT_BALANCE_TYPE,
                self::ADJUSTMENT_DESCRIPTION,
            ],
            self::OUTPUT => [
                self::ADJUSTMENT_REFERENCE_ID,
                self::ADJUSTMENT_MERCHANT_ID,
                self::ADJUSTMENT_AMOUNT,
                self::ADJUSTMENT_BALANCE_TYPE,
                self::ADJUSTMENT_DESCRIPTION,
            ],
        ],

        Type::SETTLEMENT_ONDEMAND_FEATURE_CONFIG => [
            self::INPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
            ],
            self::OUTPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
            ],
        ],

        Type::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_V2 => [
            self::INPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_BALANCE_SEPARATION_FLAG,
            ],
            self::OUTPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_ES_PRICING_PERCENT_SCALE_FACTOR,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_SETTLEMENTS_COUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_FULL_ACCESS,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_BALANCE_SEPARATION_FLAG,
            ],
        ],

        Type::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG => [
            self::INPUT => [
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_MERCHANT_ID,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRODUCT_NAME,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_SEGMENT,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_ELIGIBLE,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRE_APPROVED_LIMIT,
            ],
            self::OUTPUT => [
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_MERCHANT_ID,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRODUCT_NAME,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_SEGMENT,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_ELIGIBLE,
                self::CAPITAL_MERCHANT_ELIGIBILITY_CONFIG_PRE_APPROVED_LIMIT,
            ],
        ],

        Type::EARLY_SETTLEMENT_TRIAL => [
            self::INPUT => [
                self::EARLY_SETTLEMENT_TRIAL_MERCHANT_ID,
                self::EARLY_SETTLEMENT_TRIAL_FULL_ACCESS,
                self::EARLY_SETTLEMENT_TRIAL_DISABLE_DATE,
                self::EARLY_SETTLEMENT_TRIAL_AMOUNT_LIMIT,
                self::EARLY_SETTLEMENT_ES_PRICING,
            ],
            self::OUTPUT => [
                self::EARLY_SETTLEMENT_TRIAL_MERCHANT_ID,
                self::EARLY_SETTLEMENT_TRIAL_FULL_ACCESS,
                self::EARLY_SETTLEMENT_TRIAL_DISABLE_DATE,
                self::EARLY_SETTLEMENT_TRIAL_AMOUNT_LIMIT,
                self::EARLY_SETTLEMENT_ES_PRICING,
            ]
        ],

        Type::MERCHANT_CAPITAL_TAGS => [
            self::INPUT => [
                self::MERCHANT_CAPITAL_TAGS_MERCHANT_ID,
                self::MERCHANT_CAPITAL_TAGS_ACTION,
                self::MERCHANT_CAPITAL_TAGS_TAGS,
            ],
            self::OUTPUT => [
                self::MERCHANT_CAPITAL_TAGS_MERCHANT_ID,
                self::MERCHANT_CAPITAL_TAGS_ACTION,
                self::MERCHANT_CAPITAL_TAGS_TAGS,
            ]
        ],

        TYPE::REPORT => [
            self::INPUT => [
                self::CONSUMER,
                self::REPORT_TYPE,
                self::CONFIG_ID,
                self::GENERATED_BY,
                self::START_TIME,
                self::END_TIME,
                self::MODE
            ],
            self::OUTPUT => [
                self::ID,
                self::CONSUMER,
                self::CONFIG_ID,
                self::START_TIME,
                self::END_TIME,
            ],
        ],

        TYPE::LEDGER_ONBOARD_OLD_ACCOUNT => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::ACTION,
            ],
            self::OUTPUT => [
                self::MERCHANT_ID,
                self::ACTION,
                self::STATUS,
            ],
        ],

        TYPE::LEDGER_BULK_JOURNAL_CREATE => [
            self::INPUT => [
                self:: MERCHANT_ID,
                self:: CURRENCY_LEDGER,
                self:: AMOUNT_LEDGER,
                self:: BASE_AMOUNT,
                self:: COMMISSION,
                self:: TAX,
                self:: TRANSACTOR_ID,
                self:: TRANSACTOR_EVENT,
                self:: TRANSACTION_DATE_LEDGER,
                self:: JOURNAL_CREATE_NOTES,
                self:: API_TRANSACTION_ID,
                self:: ADDITIONAL_PARAMS,
                self:: IDENTIFIERS,
                self:: MONEY_PARAMS,
            ],
            self::OUTPUT =>[
                self:: JOURNAL_ID,
                self:: MERCHANT_ID,
                self:: CURRENCY_LEDGER,
                self:: TENANT,
                self:: AMOUNT_LEDGER,
                self:: BASE_AMOUNT,
                self:: TRANSACTOR_ID,
                self:: TRANSACTOR_EVENT,
                self:: CREATED_AT,
                self:: UPDATED_AT,
                self:: ERROR_CODE,
                self:: ERROR_DESCRIPTION,
            ],
        ],

        Type::VENDOR_ONBOARDING => [
            self::INPUT => [
                self::VENDOR_ONBOARDING_NAME,
                self::VENDOR_ONBOARDING_EMAIL,
                self::VENDOR_ONBOARDING_PHONE,
                self::VENDOR_ONBOARDING_VERIFICATION_MODE,
                self::VENDOR_ONBOARDING_PORTAL_ACCESS,
                self::VENDOR_ONBOARDING_COMPANY_REGISTERED_NAME,
                self::VENDOR_ONBOARDING_COMPANY_BUSINESS_TYPE,
                self::VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS,
                self::VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_COUNTRY,
                self::VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_STATE,
                self::VENDOR_ONBOARDING_COMPANY_REGISTERED_ADDRESS_PINCODE,
                self::VENDOR_ONBOARDING_COMPANY_POC_NAME,
                self::VENDOR_ONBOARDING_ALTERNATE_PHONE,
                self::VENDOR_ONBOARDING_ALTERNATE_EMAIL,
                self::VENDOR_ONBOARDING_VENDOR_CONTACT_POC_NAME,
                self::VENDOR_ONBOARDING_VENDOR_CONTACT_POC_PHONE,
                self::VENDOR_ONBOARDING_VENDOR_CONTACT_POC_EMAIL,
                self::VENDOR_ONBOARDING_INTERNAL_POC_EMAIL_CC,
                self::VENDOR_ONBOARDING_GST,
                self::VENDOR_ONBOARDING_PAN,
                self::VENDOR_ONBOARDING_TDS_CATEGORY,
                self::VENDOR_ONBOARDING_TAN,
                self::VENDOR_ONBOARDING_CIN,
                self::VENDOR_ONBOARDING_MSME,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_TYPE,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_NUMBER,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_IFSC,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_NAME,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_PROVIDER,
                self::VENDOR_ONBOARDING_FUND_ACCOUNT_VPA,
                self::VENDOR_ONBOARDING_CONTACT_REFERENCE_ID,
                self::VENDOR_ONBOARDING_NOTES,
                self::VENDOR_ONBOARDING_CUSTOM_FIELDS,
            ],
            self::OUTPUT => [
                self::VENDOR_ONBOARDING_NAME,
                self::VENDOR_ONBOARDING_EMAIL,
                self::VENDOR_ONBOARDING_PHONE,
                self::VENDOR_ONBOARDING_VERIFICATION_MODE,
                self:: ERROR_CODE,
                self:: ERROR_DESCRIPTION,
            ],
        ],

        Type::ECOLLECT_ICICI => [
            self::INPUT => [
                self::ICICI_ECOLLECT_UTR,
                self::ICICI_ECOLLECT_CUSTOMER_CODE,
                self::ICICI_ECOLLECT_CREDIT_ACCOUNT_NO,
                self::ICICI_ECOLLECT_DEALER_CODE,
                self::ICICI_ECOLLECT_PAYMENT_TYPE,
                self::ICICI_ECOLLECT_REMITTANCE_INFORMATION,
                self::ICICI_ECOLLECT_REMITTER_ACCOUNT_NAME,
                self::ICICI_ECOLLECT_REMITTER_ACCOUNT_NO,
                self::ICICI_ECOLLECT_REMITTING_BANK_IFSC_CODE,
                self::ICICI_ECOLLECT_TRANSACTION_AMOUNT,
                self::ICICI_ECOLLECT_TRANSACTION_DATE,
                self::ICICI_ECOLLECT_REMITTING_BANK_UTR_NO,
            ],
            self::OUTPUT => [
                self::ICICI_ECOLLECT_UTR,
                self::ICICI_ECOLLECT_CUSTOMER_CODE,
                self::ICICI_ECOLLECT_CREDIT_ACCOUNT_NO,
                self::ICICI_ECOLLECT_DEALER_CODE,
                self::ICICI_ECOLLECT_PAYMENT_TYPE,
                self::ICICI_ECOLLECT_REMITTANCE_INFORMATION,
                self::ICICI_ECOLLECT_REMITTER_ACCOUNT_NAME,
                self::ICICI_ECOLLECT_REMITTER_ACCOUNT_NO,
                self::ICICI_ECOLLECT_REMITTING_BANK_IFSC_CODE,
                self::ICICI_ECOLLECT_TRANSACTION_AMOUNT,
                self::ICICI_ECOLLECT_TRANSACTION_DATE,
                self::ICICI_ECOLLECT_REMITTING_BANK_UTR_NO,
            ],
        ],

        Type::ECOLLECT_RBL => [
            self::INPUT => [
                self::RBL_ECOLLECT_TRANSACTION_TYPE,
                self::RBL_ECOLLECT_AMOUNT,
                self::RBL_ECOLLECT_UTR_NUMBER,
                self::RBL_ECOLLECT_RRN_NUMBER,
                self::RBL_ECOLLECT_SENDER_IFSC,
                self::RBL_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_SENDER_ACCOUNT_TYPE,
                self::RBL_ECOLLECT_SENDER_NAME,
                self::RBL_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE,
                self::RBL_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_BENENAME,
                self::RBL_ECOLLECT_CREDIT_DATE,
                self::RBL_ECOLLECT_CREDIT_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_CORPORATE_CODE,
                self::RBL_ECOLLECT_SENDER_INFORMATION,
            ],
            self::OUTPUT => [
                self::RBL_ECOLLECT_TRANSACTION_TYPE,
                self::RBL_ECOLLECT_AMOUNT,
                self::RBL_ECOLLECT_UTR_NUMBER,
                self::RBL_ECOLLECT_RRN_NUMBER,
                self::RBL_ECOLLECT_SENDER_IFSC,
                self::RBL_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_SENDER_ACCOUNT_TYPE,
                self::RBL_ECOLLECT_SENDER_NAME,
                self::RBL_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE,
                self::RBL_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_BENENAME,
                self::RBL_ECOLLECT_CREDIT_DATE,
                self::RBL_ECOLLECT_CREDIT_ACCOUNT_NUMBER,
                self::RBL_ECOLLECT_CORPORATE_CODE,
                self::RBL_ECOLLECT_SENDER_INFORMATION,
                self::STATUS,
            ],
        ],

        Type::ECOLLECT_AXIS => [
            self::INPUT => [
                self::AXIS_ECOLLECT_MESSAGE_TYPE,
                self::AXIS_ECOLLECT_UTR_NUMBER,
                self::AXIS_ECOLLECT_SENDER_IFSC,
                self::AXIS_ECOLLECT_SENDER_ACCOUNT_TYPE,
                self::AXIS_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_SENDER_NAME,
                self::AXIS_ECOLLECT_SENDER_ADDRESS,
                self::AXIS_ECOLLECT_BENEFICIARY_IFSC,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NAME,
                self::AXIS_ECOLLECT_SENDER_INFORMATION,
                self::AXIS_ECOLLECT_AMOUNT,
                self::AXIS_ECOLLECT_TRANSACTION_DATE,
                self::AXIS_ECOLLECT_CREDIT_DATE,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE,
                self::AXIS_ECOLLECT_RELATED_REFERENCE,
                self::AXIS_ECOLLECT_BENEFICIARY_ADDRESS,
                self::AXIS_ECOLLECT_CORPORATE_CODE,
                self::AXIS_ECOLLECT_CLIENT_CODE,
                self::AXIS_ECOLLECT_CREDIT_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_BATCH_TIME,
                self::AXIS_ECOLLECT_CIF_ID,
                self::AXIS_ECOLLECT_BRANCH_REFERENCE,
                self::AXIS_ECOLLECT_STATUS,
            ],
            self::OUTPUT => [
                self::AXIS_ECOLLECT_MESSAGE_TYPE,
                self::AXIS_ECOLLECT_UTR_NUMBER,
                self::AXIS_ECOLLECT_SENDER_IFSC,
                self::AXIS_ECOLLECT_SENDER_ACCOUNT_TYPE,
                self::AXIS_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_SENDER_NAME,
                self::AXIS_ECOLLECT_SENDER_ADDRESS,
                self::AXIS_ECOLLECT_BENEFICIARY_IFSC,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_NAME,
                self::AXIS_ECOLLECT_SENDER_INFORMATION,
                self::AXIS_ECOLLECT_AMOUNT,
                self::AXIS_ECOLLECT_TRANSACTION_DATE,
                self::AXIS_ECOLLECT_CREDIT_DATE,
                self::AXIS_ECOLLECT_BENEFICIARY_ACCOUNT_TYPE,
                self::AXIS_ECOLLECT_RELATED_REFERENCE,
                self::AXIS_ECOLLECT_BENEFICIARY_ADDRESS,
                self::AXIS_ECOLLECT_CREDIT_ACCOUNT_NUMBER,
                self::AXIS_ECOLLECT_BATCH_TIME,
                self::STATUS,
            ],
        ],

        Type::ECOLLECT_IDFC => [
            self::INPUT => [
                self::IDFC_ECOLLECT_UTR_NUMBER,
                self::IDFC_ECOLLECT_SENDER_IFSC,
                self::IDFC_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::IDFC_ECOLLECT_SENDER_NAME,
                self::IDFC_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::IDFC_ECOLLECT_AMOUNT,
                self::IDFC_ECOLLECT_TRANSACTION_DATE,
                self::IDFC_ECOLLECT_MODE,
                self::STATUS,
            ],
            self::OUTPUT => [
                self::IDFC_ECOLLECT_UTR_NUMBER,
                self::IDFC_ECOLLECT_SENDER_IFSC,
                self::IDFC_ECOLLECT_SENDER_ACCOUNT_NUMBER,
                self::IDFC_ECOLLECT_SENDER_NAME,
                self::IDFC_ECOLLECT_BENEFICIARY_ACCOUNT_NUMBER,
                self::IDFC_ECOLLECT_AMOUNT,
                self::IDFC_ECOLLECT_TRANSACTION_DATE,
                self::IDFC_ECOLLECT_MODE,
                self::STATUS,
            ],
        ],

        Type::BANK_TRANSFER_EDIT => [
            self::INPUT => [
                self::BANK_TRANSFER_EDIT_BANK_TRANSFER_ID,
                self::BANK_TRANSFER_EDIT_BENEFICIARY_NAME,
                self::BANK_TRANSFER_EDIT_ACCOUNT_NUMBER,
                self::BANK_TRANSFER_EDIT_IFSC_CODE,
            ],
            self::OUTPUT => [
                self::BANK_TRANSFER_EDIT_BANK_TRANSFER_ID,
                self::BANK_TRANSFER_EDIT_PAYER_BANK_ACCOUNT_ID,
                self::BANK_TRANSFER_EDIT_BENEFICIARY_NAME,
                self::BANK_TRANSFER_EDIT_ACCOUNT_NUMBER,
                self::BANK_TRANSFER_EDIT_IFSC_CODE,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],
        Type::PGOS_RMDETAILS_BULK => [
            self::INPUT => [
              self::PGOS_RMDETAILS_BULK_MERCHANT_ID,
              self::PGOS_RMDETAILS_BULK_NAME,
              self::PGOS_RMDETAILS_BULK_EMAILS,
          ],
            self::OUTPUT => [
                self::PGOS_RMDETAILS_BULK_MERCHANT_ID,
                self::PGOS_RMDETAILS_BULK_NAME,
                self::PGOS_RMDETAILS_BULK_EMAILS,
            ]
        ],
        Type::NACH_MIGRATION => [
            self::INPUT => [
                self::NACH_MIGRATION_START_DATE,
                self::NACH_MIGRATION_END_DATE,
                self::NACH_MIGRATION_BANK,
                self::NACH_MIGRATION_ACCOUNT_NUMBER,
                self::NACH_MIGRATION_ACCOUNT_HOLDER_NAME,
                self::NACH_MIGRATION_ACCOUNT_TYPE,
                self::NACH_MIGRATION_IFSC,
                self::NACH_MIGRATION_MAX_AMOUNT,
                self::NACH_MIGRATION_UMRN,
                self::NACH_MIGRATION_DEBIT_TYPE,
                self::NACH_MIGRATION_FREQ,
                self::NACH_MIGRATION_METHOD,
                self::NACH_MIGRATION_CUSTOMER_EMAIL,
                self::NACH_MIGRATION_CUSTOMER_PHONE,
                self::NOTES,
            ],
            self::OUTPUT => [
                self::NACH_MIGRATION_START_DATE,
                self::NACH_MIGRATION_END_DATE,
                self::NACH_MIGRATION_BANK,
                self::NACH_MIGRATION_ACCOUNT_NUMBER,
                self::NACH_MIGRATION_ACCOUNT_HOLDER_NAME,
                self::NACH_MIGRATION_ACCOUNT_TYPE,
                self::NACH_MIGRATION_IFSC,
                self::NACH_MIGRATION_MAX_AMOUNT,
                self::NACH_MIGRATION_UMRN,
                self::NACH_MIGRATION_DEBIT_TYPE,
                self::NACH_MIGRATION_FREQ,
                self::NACH_MIGRATION_METHOD,
                self::NACH_MIGRATION_CUSTOMER_EMAIL,
                self::NACH_MIGRATION_CUSTOMER_PHONE,
                self::NOTES,
                self::NACH_MIGRATION_TOKEN_CREATION_STATUS,
                self::NACH_MIGRATION_TOKEN_ID,
                self::NACH_MIGRATION_FAILURE_REASON,
                self::NACH_MIGRATION_CUSTOMER_ID,
            ],
        ],

        Type::MERCHANT_STATUS_ACTION => [
            self::INPUT => [
                self::ID,
            ],

            self::OUTPUT => [
                self::ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::MERCHANT_ACTIVATION => [
            self::INPUT => [
                self::ID,
                self::BUSINESS_NAME,
                self::BUSINESS_CATEGORY,
                self::BUSINESS_SUBCATEGORY,
                self::BUSINESS_TYPE,
                self::BILLING_LABEL,
                self::BUSINESS_REGISTERED_ADDRESS,
                self::BUSINESS_REGISTERED_STATE,
                self::BUSINESS_REGISTERED_CITY,
                self::BUSINESS_REGISTERED_PIN,
                self::SEND_ACTIVATION_EMAIL
            ],

            self::OUTPUT => [
                self::ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::SUBMERCHANT_LINK => [
            self::INPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
            ],

            self::OUTPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::SUBMERCHANT_DELINK => [
            self::INPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
            ],

            self::OUTPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::SUBMERCHANT_PARTNER_CONFIG_UPSERT => [
            self::INPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
                self::IMPLICIT_PLAN_ID
            ],

            self::OUTPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
                self::IMPLICIT_PLAN_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::SUBMERCHANT_TYPE_UPDATE => [
            self::INPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID
            ],

            self::OUTPUT => [
                self::PARTNER_ID,
                self::MERCHANT_ID,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION
            ]
        ],

        'banking_account_activation_comments' => [
            self::INPUT => [
                self::RZP_REF_NO,
                self::COMMENT,
                self::NEW_STATUS,
                self::NEW_SUBSTATUS,
                self::NEW_BANK_STATUS,
                self::NEW_ASSIGNEE,
                self::RM_NAME,
                self::RM_PHONE_NUMBER,
                self::ACCOUNT_OPEN_DATE,
                self::ACCOUNT_LOGIN_DATE,
                self::SALES_TEAM,
                self::SALES_POC_EMAIL,
                self::API_ONBOARDED_DATE,
                self::API_ONBOARDING_LOGIN_DATE,
                self::MID_OFFICE_POC_NAME,
                self::DOCKET_REQUESTED_DATE,
                self::ESTIMATED_DOCKET_DELIVERY_DATE,
                self::DOCKET_DELIVERED_DATE,
                self::COURIER_SERVICE_NAME,
                self::COURIER_TRACKING_ID,
                self::REASON_WHY_DOCKET_IS_NOT_DELIVERED,
            ],
        ],

        Type::RBL_BULK_UPLOAD_COMMENTS => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::DATE_TIME,
                self::FIRST_DISPOSITION,
                self::SECOND_DISPOSITION,
                self::THIRD_DISPOSISTION,
                self::OPS_CALL_COMMENT,
            ],
        ],

        Type::ICICI_BULK_UPLOAD_COMMENTS => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::DATE_TIME,
                self::COMMENT,
                self::FIRST_DISPOSITION,
                self::SECOND_DISPOSITION,
                self::THIRD_DISPOSISTION,
            ],
        ],

        Type::ICICI_VIDEO_KYC_BULK_UPLOAD => [
            self::INPUT => [
                self::APPLICATION_NO,
                self::TRACKER_ID,
                self::CLIENT_NAME,
                self::F_NAME,
                self::L_NAME,
                self::ICICI_CA_ACCOUNT_NUMBER,
                self::LEADID,
                self::COMMENT_OR_REMARKS,
                self::ICICI_LEADID_CREATION_DATE,
                self::ICICI_T3_VKYC_COMPLETION_DATE,
                self::ICICI_VKYC_INELIGIBLE_DATE,
                self::ICICI_VKYC_INELIGIBLE_REASON,
                self::ICICI_VKYC_COMPLETION_DATE,
                self::ICICI_VKYC_DROP_OFF_DATE,
                self::ICICI_VKYC_UNSUCCESSFUL_DATE,
                self::ICICI_LEAD_ASSIGNED_TO_PHYSICAL_TEAM_DATE,
                self::ICICI_VKYC_STATUS
            ]
        ],

        Type::ICICI_LEAD_ACCOUNT_ACTIVATION_COMMENTS => [
            self::INPUT => [
                self::APPLICATION_NO,
                self::TRACKER_ID,
                self::CLIENT_NAME,
                self::F_NAME,
                self::L_NAME,
                self::LEADID,
                self::ICICI_CA_ACCOUNT_NUMBER,
                self::ICICI_LEAD_SUB_STATUS,
                self::ICICI_CA_ACCOUNT_STATUS, // PLease review this, should this be changed?
                self::LAST_UPDATED_ON_DATE,
                self::LAST_UPDATED_ON_TIME,
                self::COMMENT_OR_REMARKS,
                self::LEAD_SENT_TO_BANK_DATE,
                self::DATE_ON_WHICH_1ST_APPOINTMENT_WAS_FIXED,
                self::DOCS_COLLECTED_DATE,
                self::CASE_INITIATION_DATE,
                self::ACCOUNT_OPENED_DATE,
                self::MULTI_LOCATION,
                self::DROP_OFF_REASON,
                self::STP_DOCS_COLLECTED,
                self::ACCOUNT_NUMBER_CHANGE,
                self::FOLLOW_UP_DATE
            ]
        ],

        Type::ICICI_STP_MIS => [
            self::INPUT => [
                self::STP_ACCOUNT_NO,
                self::STP_FCRM_SR_DATE,
                self::STP_SR_NUMBER,
                self::STP_SR_STATUS,
                self::STP_SR_CLOSED_DATE,
                self::STP_REMARKS,
                self::STP_RZP_INTERVENTION,
                self::STP_CONNECTED_BANKING,
                self::STP_T3_DATE,
                self::STP_HELPDESK_SR_STATUS,
                self::STP_HELPDESK_SR,
            ]
        ],

        Type::S2P_GROUPS_ONBOARDING => [
            self::INPUT => [
                self::S2P_GROUPS_ONBOARDING_MERCHANT_ID,
                self::S2P_GROUPS_ONBOARDING_GROUP_TYPE_ID,
                self::S2P_GROUPS_ONBOARDING_NAME,
                self::S2P_GROUPS_ONBOARDING_GROUP_HEAD_EMAIL,
                self::S2P_GROUPS_ONBOARDING_REFERENCE_ID,
                self::S2P_GROUPS_ONBOARDING_DESCRIPTION,
            ]
        ],

        Type::S2P_USERS_ONBOARDING => [
            self::INPUT => [
                self::S2P_USERS_ONBOARDING_MERCHANT_ID,
                self::S2P_USERS_ONBOARDING_FIRST_NAME,
                self::S2P_USERS_ONBOARDING_LAST_NAME,
                self::S2P_USERS_ONBOARDING_EMAIL_ID,
                self::S2P_USERS_ONBOARDING_USER_ROLE,
                self::S2P_USERS_ONBOARDING_DESIGNATION,
                self::S2P_USERS_ONBOARDING_EMPLOYEE_ID,
                self::S2P_USERS_ONBOARDING_MANAGER_EMAIL_ID,
                self::S2P_USERS_ONBOARDING_GROUP_IDS,
            ]
        ],

        Type::HITACHI_CBK_MASTERCARD => [
            self::INPUT => [
                self::SR_NO,
                self::CARD_NUMBER,
                self::ARN,
                self::HITACHI_MASTERCARD_AMOUNT,
                self::HITACHI_MASTERCARD_CURRENCY,
                self::HITACHI_MASTERCARD_BILLING_AMOUNT,
                self::HITACHI_MASTERCARD_BILLING_CURRENCY,
                self::HITACHI_MASTERCARD_TXN_DATE,
                self::HITACHI_MASTERCARD_SETTLEMENT_DATE,
                self::HITACHI_MASTERCARD_MID,
                self::HITACHI_MASTERCARD_TID,
                self::HITACHI_MASTERCARD_ME_NAME,
                self::HITACHI_MASTERCARD_AUTH_CODE,
                self::HITACHI_MASTERCARD_RRN,
                self::HITACHI_MASTERCARD_MCC,
                self::HITACHI_MASTERCARD_CHARGEBACK_REF_NO,
                self::CHARGEBACK_DATE,
                self::HITACHI_MASTERCARD_DOC_INDICATOR,
                self::HITACHI_MASTERCARD_REASON_CODE,
                self::HITACHI_MASTERCARD_MESSAGE_TEXT,
                self::FULFILMENT_TAT,
                self::AGEING_DAYS,
                self::HITACHI_MASTERCARD_TYPE,
            ]
        ],

        Type::HITACHI_CBK_VISA => [
            self::INPUT => [
                self::SR_NO,
                self::CARD_NUMBER,
                self::ARN,
                self::CHARGEBACK_AMOUNT,
                self::CURRENCY,
                self::SOURCE_AMOUNT,
                self::SOURCE_CURRENCY,
                self::BILLING_AMOUNT,
                self::BILLING_CURRENCY,
                self::TXN_DATE,
                self::SETTLEMENT_DATE,
                self::HITCAHI_MID,
                self::HITCAHI_TID,
                self::ME_NAME,
                self::AUTH_CODE,
                self::RRN,
                self::MCC_CODE,
                self::CHARGEBACK_REF_NO,
                self::CHARGEBACK_DATE,
                self::DOC_INDICATOR,
                self::REASON_CODE,
                self::MESSAGE_TEXT,
                self::FULFILMENT_TAT,
                self::DUPLICATE_RRN,
                self::AGEING_DAYS,
                self::DATE_OF_ISSUE,
                self::HITACHI_DISPUTE_TYPE,
            ]
        ],

        Type::HITACHI_CBK_RUPAY => [
            self::INPUT => [
                self::SR_NO,
                self::CARD_NUMBER,
                self::ARN,
                self::CHARGEBACK_AMOUNT,
                self::CURRENCY,
                self::SOURCE_AMOUNT,
                self::SOURCE_CURRENCY,
                self::BILLING_AMOUNT,
                self::BILLING_CURRENCY,
                self::TXN_DATE,
                self::SETTLEMENT_DATE,
                self::HITCAHI_MID,
                self::HITCAHI_TID,
                self::ME_NAME,
                self::AUTH_CODE,
                self::RRN,
                self::MCC_CODE,
                self::CHARGEBACK_REF_NO,
                self::CHARGEBACK_DATE,
                self::DOC_INDICATOR,
                self::REASON_CODE,
                self::MESSAGE_TEXT,
                self::FULFILMENT_TAT,
                self::AGEING_DAYS,
                self::DATE_OF_ISSUE,
                self::HITACHI_DISPUTE_TYPE,
            ]
        ],

        Type::INTERNAL_INSTRUMENT_REQUEST => [
            self::INPUT => [
                self::INTERNAL_INSTRUMENT_REQUEST_ID,
            ],
        ],

        'payout_approval' => [
            self::INPUT => [
                self::APPROVE_REJECT_PAYOUT,
                self::P_A_AMOUNT,
                self::P_A_CURRENCY,
                self::P_A_CONTACT_NAME,
                self::P_A_MODE,
                self::P_A_PURPOSE,
                self::P_A_PAYOUT_ID,
                self::P_A_CONTACT_ID,
                self::P_A_FUND_ACCOUNT_ID,
                self::P_A_CREATED_AT,
                self::P_A_ACCOUNT_NUMBER,
                self::P_A_STATUS,
                self::P_A_NOTES,
                self::P_A_FEES,
                self::P_A_TAX,
                self::P_A_SCHEDULED_AT
            ]
        ],

        Type::CAPTURE_SETTING => [
            self::INPUT => [
                self::CUSTOMER_TYPE,
                self::AUTO_CAPTURE_LATE_AUTH,
                self::AUTO_REFUND_DELAY,
                self::DEFAULT_REFUND_SPEED,
                self::CAPTURE_SETTING_MERCHANT_ID,
                self::CAPTURE_SETTING_MERCHANT_NAME,
                self::CAPTURE_SETTING_BUSINESS_CATEGORY,
                self::TOTAL_CAPTURES,
            ],
            self::OUTPUT => [
                self::CAPTURE_SETTING_MERCHANT_ID,
                self::CAPTURE_SETTING_NAME,
                self::CAPTURE_SETTING_CONFIG,
            ]
        ],

        Type::PAYMENT_TRANSFER => [
            self::INPUT => [
                self::PAYMENT_ID_2,
                self::ACCOUNT_ID,
                self::AMOUNT_2,
                self::CURRENCY_2,
                self::TRANSFER_NOTES,
                self::LINKED_ACCOUNT_NOTES,
                self::ON_HOLD,
                self::ON_HOLD_UNTIL,
            ],
            self::OUTPUT => [
                self::ID,
                self::SOURCE,
                self::RECIPIENT,
                self::AMOUNT_2,
                self::CURRENCY_2,
                self::TRANSFER_NOTES,
                self::LINKED_ACCOUNT_NOTES,
                self::ON_HOLD,
                self::ON_HOLD_UNTIL,
                self::CREATED_AT_2,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::TRANSFER_REVERSAL => [
            self::INPUT => [
                self::TRANSFER_ID_2,
                self::AMOUNT_2,
                self::REVERSAL_NOTES,
                self::LINKED_ACCOUNT_NOTES,
            ],
            self::OUTPUT => [
                self::ID,
                self::TRANSFER_ID_2,
                self::AMOUNT_2,
                self::REVERSAL_NOTES,
                self::LINKED_ACCOUNT_NOTES,
                self::CREATED_AT_2,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYMENT_TRANSFER_RETRY => [
            self::INPUT => [
                self::TRANSFER_ID_2,
            ],
            self::OUTPUT => [
                self::TRANSFER_ID_OLD,
                self::TRANSFER_ID_NEW,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::PAYOUT_LINK_BULK => [
            self::INPUT => [
                self::PAYOUT_LINK_BULK_CONTACT_NAME,
                self::PAYOUT_LINK_BULK_CONTACT_NUMBER,
                self::PAYOUT_LINK_BULK_CONTACT_EMAIL,
                self::PAYOUT_LINK_BULK_PAYOUT_DESC,
                self::CONTACT_TYPE,
                self::PAYOUT_LINK_BULK_AMOUNT,
                self::PAYOUT_LINK_BULK_SEND_SMS,
                self::PAYOUT_LINK_BULK_SEND_EMAIL,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_LINK_BULK_REFERENCE_ID,
                self::PAYOUT_LINK_BULK_NOTES_TITLE,
                self::PAYOUT_LINK_BULK_NOTES_DESC,
            ],
            self::OUTPUT => [
                self::PAYOUT_LINK_BULK_CONTACT_NAME,
                self::PAYOUT_LINK_BULK_CONTACT_NUMBER,
                self::PAYOUT_LINK_BULK_CONTACT_EMAIL,
                self::PAYOUT_LINK_BULK_PAYOUT_DESC,
                self::CONTACT_TYPE,
                self::PAYOUT_LINK_BULK_AMOUNT,
                self::PAYOUT_LINK_BULK_SEND_SMS,
                self::PAYOUT_LINK_BULK_SEND_EMAIL,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_LINK_BULK_REFERENCE_ID,
                self::PAYOUT_LINK_BULK_NOTES_TITLE,
                self::PAYOUT_LINK_BULK_NOTES_DESC,
                self::PAYOUT_LINK_BULK_PAYOUT_LINK_ID,
                self::CONTACT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::PAYOUT_LINK_BULK_V2 => [
            self::INPUT => [
                self::PAYOUT_LINK_BULK_CONTACT_NAME,
                self::PAYOUT_LINK_BULK_CONTACT_NUMBER,
                self::PAYOUT_LINK_BULK_CONTACT_EMAIL,
                self::PAYOUT_LINK_BULK_PAYOUT_DESC,
                self::CONTACT_TYPE,
                self::PAYOUT_LINK_BULK_AMOUNT,
                self::PAYOUT_LINK_BULK_SEND_SMS,
                self::PAYOUT_LINK_BULK_SEND_EMAIL,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_LINK_BULK_REFERENCE_ID,
                self::PAYOUT_LINK_BULK_NOTES_TITLE,
                self::PAYOUT_LINK_BULK_NOTES_DESC,
                self::PAYOUT_LINK_BULK_EXPIRY_DATE,
                self::PAYOUT_LINK_BULK_EXPIRY_TIME,
            ],
            self::OUTPUT => [
                self::PAYOUT_LINK_BULK_CONTACT_NAME,
                self::PAYOUT_LINK_BULK_CONTACT_NUMBER,
                self::PAYOUT_LINK_BULK_CONTACT_EMAIL,
                self::PAYOUT_LINK_BULK_PAYOUT_DESC,
                self::CONTACT_TYPE,
                self::PAYOUT_LINK_BULK_AMOUNT,
                self::PAYOUT_LINK_BULK_SEND_SMS,
                self::PAYOUT_LINK_BULK_SEND_EMAIL,
                self::PAYOUT_PURPOSE,
                self::PAYOUT_LINK_BULK_REFERENCE_ID,
                self::PAYOUT_LINK_BULK_NOTES_TITLE,
                self::PAYOUT_LINK_BULK_NOTES_DESC,
                self::PAYOUT_LINK_BULK_EXPIRY_DATE,
                self::PAYOUT_LINK_BULK_EXPIRY_TIME,
                self::PAYOUT_LINK_BULK_PAYOUT_LINK_ID,
                self::CONTACT_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::WEBSITE_CHECKER => [
            self::INPUT => [
                self::WEBSITE_CHECKER_URL,
            ],
            self::OUTPUT => [
                self::WEBSITE_CHECKER_URL,
                self::WEBSITE_CHECKER_RESULT,
            ],
        ],

        Type::CREATE_EXEC_RISK_ACTION => [
            self::INPUT => [
                self::RISK_ACTION_MERCHANT_ID,
                self::RISK_ACTION_BULK_WORKFLOW_ACTION_ID,
            ],
            self::OUTPUT => [
                self::RISK_ACTION_MERCHANT_ID,
                self::RISK_ACTION_BULK_WORKFLOW_ACTION_ID,
                self::RISK_ACTION_WORKFLOW_ACTION_ID,
                self::RISK_ACTION_STATUS,
            ],
        ],

        Type::CHARGEBACK_POC => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::ACTION,
                self::CHARGEBACK_POC_EMAIL,
            ],
            self::OUTPUT => [
                self::MERCHANT_ID,
                self::ACTION,
                self::CHARGEBACK_POC_EMAIL,
                self::STATUS,
                self::ERROR_MESSAGE,
            ],
        ],

        Type::WHITELISTED_DOMAIN => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::ACTION,
                self::URL,
            ],
            self::OUTPUT => [
                self::MERCHANT_ID,
                self::ACTION,
                self::URL,
                self::STATUS,
                self::ERROR_MESSAGE,
                self::COMMENT,
            ],
        ],

        Type::DEBIT_NOTE => [
            self::INPUT => [
                self::MERCHANT_ID,
                self::DEBIT_NOTE_PAYMENT_IDS,
                self::DEBIT_NOTE_SKIP_VALIDATION,
            ],
        ],

        Type::CREATE_PAYMENT_FRAUD => [
            self::INPUT => [
                self::FRAUD_HEADER_ARN,
                self::FRAUD_HEADER_TYPE,
                self::SUB_TYPE,
                self::FRAUD_HEADER_AMOUNT,
                self::BASE_AMOUNT,
                self::REPORTED_TO_ISSUER_AT,
                self::CHARGEBACK_CODE,
                self::FRAUD_HEADER_RRN,
                self::FRAUD_HEADER_CURRENCY,
                self::REPORTED_BY,
                self::ERROR_REASON,
                self::FRAUD_HEADER_SEND_MAIL,
                self::REPORTED_TO_RAZORPAY_AT,
            ],
            self::OUTPUT => [
                self::FRAUD_OUTPUT_HEADER_ARN,
                self::FRAUD_OUTPUT_HEADER_PAYMENT_ID,
                self::FRAUD_OUTPUT_HEADER_FRAUD_ID,
                self::FRAUD_OUTPUT_HEADER_STATUS,
                self::FRAUD_OUTPUT_HEADER_ERROR_REASON,
            ],
        ],

        Type::ED_MERCHANT_SEARCH => [
            self::INPUT => [
                self::MERCHANT_NAME,
            ],
            self::OUTPUT => [
                self::MERCHANT_NAME,
                self::MERCHANT_IDS,
                self::STATUS,
                self::ERROR_MESSAGE,
            ],
        ],

        Type::COLLECT_LOCAL_CONSENTS_TO_CREATE_TOKENS => [
            self::INPUT => [
                self::CONSENT_COLLECTION_MERCHANT_ID,
                self::CONSENT_COLLECTION_TOKEN_ID,
            ],
            self::OUTPUT => [
                self::CONSENT_COLLECTION_SUCCESS,
                self::CONSENT_COLLECTION_ERROR_CODE,
                self::CONSENT_COLLECTION_ERROR_DESCRIPTION,
            ],
        ],

        Type::CREATE_WALLET_LOADS => [
            self::INPUT => [
                self::WALLET_LOAD_CONTACT,
                self::WALLET_LOAD_AMOUNT,
                self::WALLET_LOAD_CATEGORY,
                self::WALLET_LOAD_DESCRIPTION,
                self::WALLET_LOAD_REFERENCE_ID,
            ],
            self::OUTPUT => []
        ],

        Type::CREATE_WALLET_ACCOUNTS => [
            self::INPUT => [
                self::WALLET_ACCOUNTS_NAME,
                self::WALLET_ACCOUNTS_EMAIL,
                self::WALLET_ACCOUNTS_CONTACT,
                self::WALLET_ACCOUNTS_IDENTIFICATION_ID,
                self::WALLET_ACCOUNTS_IDENTIFICATION_TYPE,
                self::WALLET_ACCOUNTS_PARTNER_CUSTOMER_ID,
                self::WALLET_ACCOUNTS_DOB
            ],
            self::OUTPUT => []
        ],

        Type::CREATE_WALLET_CONTAINER_LOADS => [
            self::INPUT => [
                self::WALLET_CONTAINER_LOAD_USER_ID,
                self::WALLET_CONTAINER_LOAD_PROGRAM_ID,
                self::WALLET_CONTAINER_LOAD_AMOUNT,
                self::WALLET_CONTAINER_LOAD_REFERENCE_ID,
                self::WALLET_CONTAINER_LOAD_DESCRIPTION,
                self::WALLET_CONTAINER_LOAD_NOTES,
                self::WALLET_CONTAINER_LOAD_EXPIRY_DATE,
            ],
            self::OUTPUT => []
        ],

        TYPE::CREATE_WALLET_USER_CONTAINERS => [
            self::INPUT => [
                self::WALLET_ACCOUNTS_CONTACT,
                self::WALLET_ACCOUNTS_EMAIL,
                self::WALLET_ACCOUNTS_PARTNER_USER_ID
            ],
            self::OUTPUT => []
        ],

        TYPE::CREATE_WALLET_CONTAINER_REVERSALS => [
            self::INPUT => [
                self::WALLET_CONTAINER_LOAD_ID,
                self::WALLET_CONTAINER_REVERSAL_AMOUNT,
                self::WALLET_CONTAINER_REVERSAL_REFERENCE_ID,
                self::WALLET_CONTAINER_REVERSAL_DESCRIPTION,
                self::WALLET_CONTAINER_REVERSAL_NOTES
            ],
            self::OUTPUT => []
        ],

        TYPE::CREATE_BULK_GIFT_CARDS => [
            self::INPUT => [
                self::CREATE_BULK_GIFT_CARD_PROGRAM_ID,
                self::CREATE_BULK_GIFT_CARD_AMOUNT,
                self::CREATE_BULK_GIFT_CARD_REQUEST_ID,
                self::CREATE_BULK_GIFT_CARD_CONTACT,
                self::CREATE_BULK_GIFT_CARD_BUYER_USER_ID
            ],
            self::OUTPUT => []
        ],

        TYPE::UPDATE_GIFT_CARDS_EXPIRY => [
            self::INPUT => [
                self::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_ID,
                self::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_NUMBER,
                self::UPDATE_GIFT_CARDS_EXPIRY_EXPIRE_AT,
                self::UPDATE_GIFT_CARDS_EXPIRY_REFERENCE_ID,
                self::UPDATE_GIFT_CARDS_EXPIRY_NOTES,
                self::UPDATE_GIFT_CARDS_EXPIRY_CONTACT,
                self::UPDATE_GIFT_CARDS_EXPIRY_TICKET_LINK,
                self::UPDATE_GIFT_CARDS_EXPIRY_TIMESTAMP,
                self::UPDATE_GIFT_CARDS_EXPIRY_SOURCE
            ],
            self::OUTPUT => [],
        ],

        TYPE::CREATE_GIFT_CARD_TRANSFERS => [
            self::INPUT => [
                self::CREATE_GIFT_CARD_TRANSFERS_SOURCE_USER_ID,
                self::CREATE_GIFT_CARD_TRANSFERS_DESTINATION_USER_ID
            ],
            self::OUTPUT => []
        ],

        TYPE::GCMS_UPLOAD_BULK_EMAILS => [
            self::INPUT => [
                self::UPLOAD_BULK_EMAIL_ORDER_ID,
                self::UPLOAD_BULK_EMAIL_PROGRAM_ID,
                self::UPLOAD_BULK_EMAIL_PROGRAM_NAME,
                self::UPLOAD_BULK_EMAIL_DENOMINATION,
                self::UPLOAD_BULK_EMAIL_EMAIL,
            ],
            self::OUTPUT => []
        ],


        Type::PARTNER_SUBMERCHANT_REFERRAL_INVITE => [
            self::INPUT => [
                self::NAME,
                self::EMAIL,
                self::CONTACT_MOBILE
            ],
            self::OUTPUT => [
                self::NAME,
                self::EMAIL,
                self::CONTACT_MOBILE,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ]
        ],

        Type::UPDATE_MIQ => [
            self::INPUT => [
                self::UPDATE_MIQ_MERCHANT_ID,
                self::UPDATE_MIQ_STATUS,
                self::UPDATE_MIQ_MERCHANT_NAME_BUSINESS_NAME,
                self::UPDATE_MIQ_DBA_NAME_BILLING_LABEL,
                self::UPDATE_MIQ_WEBSITE,
                self::UPDATE_MIQ_WEBSITE_ABOUT_US,
                self::UPDATE_MIQ_WEBSITE_TERMS_AND_CONDITIONS,
                self::UPDATE_MIQ_WEBSITE_CONTACT_US,
                self::UPDATE_MIQ_WEBSITE_PRIVACY_POLICY,
                self::UPDATE_MIQ_WEBSITE_PRODUCT_PRICING,
                self::UPDATE_MIQ_WEBSITE_REFUNDS,
                self::UPDATE_MIQ_WEBSITE_CANCELLATION,
                self::UPDATE_MIQ_WEBSITE_SHIPPING_AND_DELIVERY,
                self::UPDATE_MIQ_CONTACT_NAME,
                self::UPDATE_MIQ_CONTACT_EMAIL,
                self::UPDATE_MIQ_TRANSACTIONS_REPORT_EMAIL_ID,
                self::UPDATE_MIQ_ADDRESS,
                self::UPDATE_MIQ_CITY,
                self::UPDATE_MIQ_PIN_CODE,
                self::UPDATE_MIQ_STATE,
                self::UPDATE_MIQ_CONTACT_NUMBER,
                self::UPDATE_MIQ_BUSINESS_TYPE,
                self::UPDATE_MIQ_CIN,
                self::UPDATE_MIQ_BUSINESS_PAN,
                self::UPDATE_MIQ_BUSINESS_NAME,
                self::UPDATE_MIQ_AUTHORISED_SIGNATURE_PAN,
                self::UPDATE_MIQ_PAN_OWNER_NAME,
                self::UPDATE_MIQ_BUSINESS_CATEGORY,
                self::UPDATE_MIQ_SUB_CATEGORY,
                self::UPDATE_MIQ_GSTIN,
                self::UPDATE_MIQ_BUSINESS_DESCRIPTION,
                self::UPDATE_MIQ_ESTD_DATE,
                self::UPDATE_MIQ_FEE_MODEL,
                self::UPDATE_MIQ_NET_BANKING_FEE_TYPE,
                self::UPDATE_MIQ_NET_BANKING_FEE_BEARER,
                self::UPDATE_MIQ_AXIS_NET_BANKING,
                self::UPDATE_MIQ_HDFC_NET_BANKING,
                self::UPDATE_MIQ_ICICI_NET_BANKING,
                self::UPDATE_MIQ_SBI_NET_BANKING,
                self::UPDATE_MIQ_YES_NET_BANKING,
                self::UPDATE_MIQ_NET_BANKING_ANY,
                self::UPDATE_MIQ_DEBIT_CARD_FEE_TYPE,
                self::UPDATE_MIQ_DEBIT_CARD_FEE_BEARER,
                self::UPDATE_MIQ_DEBIT_CARD_0_TO_2K,
                self::UPDATE_MIQ_DEBIT_CARD_2K_TO_1CR,
                self::UPDATE_MIQ_RUPAY_FEE_TYPE,
                self::UPDATE_MIQ_RUPAY_FEE_BEARER,
                self::UPDATE_MIQ_RUPAY_0_TO_2K,
                self::UPDATE_MIQ_RUPAY_2K_TO_1CR,
                self::UPDATE_MIQ_UPI_FEE_TYPE,
                self::UPDATE_MIQ_UPI_FEE_BEARER,
                self::UPDATE_MIQ_UPI,
                self::UPDATE_MIQ_WALLETS_FEE_TYPE,
                self::UPDATE_MIQ_WALLETS_FEE_BEARER,
                self::UPDATE_MIQ_WALLETS_FREE_CHARGE,
                self::UPDATE_MIQ_WALLETS_ANY,
                self::UPDATE_MIQ_CREDIT_CARD_FEE_TYPE,
                self::UPDATE_MIQ_CREDIT_CARD_FEE_BEARER,
                self::UPDATE_MIQ_CREDIT_CARD_0_TO_2K,
                self::UPDATE_MIQ_CREDIT_CARD_2K_TO_1CR,
                self::UPDATE_MIQ_INTERNATIONAL,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_TYPE,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_BEARER,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS,
                self::UPDATE_MIQ_BUSINESS_FEE_TYPE,
                self::UPDATE_MIQ_BUSINESS_FEE_BEARER,
                self::UPDATE_MIQ_BUSINESS,
                self::UPDATE_MIQ_BANK_ACCOUNT_NUMBER,
                self::UPDATE_MIQ_BENEFICIARY_NAME,
                self::UPDATE_MIQ_BRANCH_IFSC_CODE,
                self::UPDATE_MIQ_MODE,
                self::UPDATE_MIQ_GATEWAY_A,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_A,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_A,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_A,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_A,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_A,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_A,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_A,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_A,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_A,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_A,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_A,
                self::UPDATE_MIQ_TERMINAL_MODE_A,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_A,
                self::UPDATE_MIQ_CARDS_TERMINAL_A,
                self::UPDATE_MIQ_UPI_TERMINAL_A,
                self::UPDATE_MIQ_BANK_TRANSFER_A,
                self::UPDATE_MIQ_OFFLINE_A,
                self::UPDATE_MIQ_TYPES_A,
                self::UPDATE_MIQ_CAPABILITY_A,
                self::UPDATE_MIQ_CURRENCY_A,
                self::UPDATE_MIQ_GATEWAY_B,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_B,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_B,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_B,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_B,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_B,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_B,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_B,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_B,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_B,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_B,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_B,
                self::UPDATE_MIQ_TERMINAL_MODE_B,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_B,
                self::UPDATE_MIQ_CARDS_TERMINAL_B,
                self::UPDATE_MIQ_UPI_TERMINAL_B,
                self::UPDATE_MIQ_BANK_TRANSFER_B,
                self::UPDATE_MIQ_OFFLINE_B,
                self::UPDATE_MIQ_TYPES_B,
                self::UPDATE_MIQ_CAPABILITY_B,
                self::UPDATE_MIQ_CURRENCY_B,
                self::UPDATE_MIQ_GATEWAY_C,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_C,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_C,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_C,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_C,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_C,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_C,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_C,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_C,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_C,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_C,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_C,
                self::UPDATE_MIQ_TERMINAL_MODE_C,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_C,
                self::UPDATE_MIQ_CARDS_TERMINAL_C,
                self::UPDATE_MIQ_UPI_TERMINAL_C,
                self::UPDATE_MIQ_BANK_TRANSFER_C,
                self::UPDATE_MIQ_OFFLINE_C,
                self::UPDATE_MIQ_TYPES_C,
                self::UPDATE_MIQ_CAPABILITY_C,
                self::UPDATE_MIQ_CURRENCY_C
            ],
            self::OUTPUT => [
                self::UPDATE_MIQ_MERCHANT_ID,
                self::UPDATE_MIQ_STATUS,
                self::UPDATE_MIQ_MERCHANT_NAME_BUSINESS_NAME,
                self::UPDATE_MIQ_DBA_NAME_BILLING_LABEL,
                self::UPDATE_MIQ_WEBSITE,
                self::UPDATE_MIQ_WEBSITE_ABOUT_US,
                self::UPDATE_MIQ_WEBSITE_TERMS_AND_CONDITIONS,
                self::UPDATE_MIQ_WEBSITE_CONTACT_US,
                self::UPDATE_MIQ_WEBSITE_PRIVACY_POLICY,
                self::UPDATE_MIQ_WEBSITE_PRODUCT_PRICING,
                self::UPDATE_MIQ_WEBSITE_REFUNDS,
                self::UPDATE_MIQ_WEBSITE_CANCELLATION,
                self::UPDATE_MIQ_WEBSITE_SHIPPING_AND_DELIVERY,
                self::UPDATE_MIQ_CONTACT_NAME,
                self::UPDATE_MIQ_CONTACT_EMAIL,
                self::UPDATE_MIQ_TRANSACTIONS_REPORT_EMAIL_ID,
                self::UPDATE_MIQ_ADDRESS,
                self::UPDATE_MIQ_CITY,
                self::UPDATE_MIQ_PIN_CODE,
                self::UPDATE_MIQ_STATE,
                self::UPDATE_MIQ_CONTACT_NUMBER,
                self::UPDATE_MIQ_BUSINESS_TYPE,
                self::UPDATE_MIQ_CIN,
                self::UPDATE_MIQ_BUSINESS_PAN,
                self::UPDATE_MIQ_BUSINESS_NAME,
                self::UPDATE_MIQ_AUTHORISED_SIGNATURE_PAN,
                self::UPDATE_MIQ_PAN_OWNER_NAME,
                self::UPDATE_MIQ_BUSINESS_CATEGORY,
                self::UPDATE_MIQ_SUB_CATEGORY,
                self::UPDATE_MIQ_GSTIN,
                self::UPDATE_MIQ_BUSINESS_DESCRIPTION,
                self::UPDATE_MIQ_ESTD_DATE,
                self::UPDATE_MIQ_FEE_MODEL,
                self::UPDATE_MIQ_NET_BANKING_FEE_TYPE,
                self::UPDATE_MIQ_NET_BANKING_FEE_BEARER,
                self::UPDATE_MIQ_AXIS_NET_BANKING,
                self::UPDATE_MIQ_HDFC_NET_BANKING,
                self::UPDATE_MIQ_ICICI_NET_BANKING,
                self::UPDATE_MIQ_SBI_NET_BANKING,
                self::UPDATE_MIQ_YES_NET_BANKING,
                self::UPDATE_MIQ_NET_BANKING_ANY,
                self::UPDATE_MIQ_DEBIT_CARD_FEE_TYPE,
                self::UPDATE_MIQ_DEBIT_CARD_FEE_BEARER,
                self::UPDATE_MIQ_DEBIT_CARD_0_TO_2K,
                self::UPDATE_MIQ_DEBIT_CARD_2K_TO_1CR,
                self::UPDATE_MIQ_RUPAY_FEE_TYPE,
                self::UPDATE_MIQ_RUPAY_FEE_BEARER,
                self::UPDATE_MIQ_RUPAY_0_TO_2K,
                self::UPDATE_MIQ_RUPAY_2K_TO_1CR,
                self::UPDATE_MIQ_UPI_FEE_TYPE,
                self::UPDATE_MIQ_UPI_FEE_BEARER,
                self::UPDATE_MIQ_UPI,
                self::UPDATE_MIQ_WALLETS_FEE_TYPE,
                self::UPDATE_MIQ_WALLETS_FEE_BEARER,
                self::UPDATE_MIQ_WALLETS_FREE_CHARGE,
                self::UPDATE_MIQ_WALLETS_ANY,
                self::UPDATE_MIQ_CREDIT_CARD_FEE_TYPE,
                self::UPDATE_MIQ_CREDIT_CARD_FEE_BEARER,
                self::UPDATE_MIQ_CREDIT_CARD_0_TO_2K,
                self::UPDATE_MIQ_CREDIT_CARD_2K_TO_1CR,
                self::UPDATE_MIQ_INTERNATIONAL,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_TYPE,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS_FEE_BEARER,
                self::UPDATE_MIQ_INTERNATIONAL_CARDS,
                self::UPDATE_MIQ_BUSINESS_FEE_TYPE,
                self::UPDATE_MIQ_BUSINESS_FEE_BEARER,
                self::UPDATE_MIQ_BUSINESS,
                self::UPDATE_MIQ_BANK_ACCOUNT_NUMBER,
                self::UPDATE_MIQ_BENEFICIARY_NAME,
                self::UPDATE_MIQ_BRANCH_IFSC_CODE,
                self::UPDATE_MIQ_MODE,
                self::UPDATE_MIQ_GATEWAY_A,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_A,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_A,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_A,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_A,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_A,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_A,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_A,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_A,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_A,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_A,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_A,
                self::UPDATE_MIQ_TERMINAL_MODE_A,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_A,
                self::UPDATE_MIQ_CARDS_TERMINAL_A,
                self::UPDATE_MIQ_UPI_TERMINAL_A,
                self::UPDATE_MIQ_BANK_TRANSFER_A,
                self::UPDATE_MIQ_OFFLINE_A,
                self::UPDATE_MIQ_TYPES_A,
                self::UPDATE_MIQ_CAPABILITY_A,
                self::UPDATE_MIQ_CURRENCY_A,
                self::UPDATE_MIQ_GATEWAY_B,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_B,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_B,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_B,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_B,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_B,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_B,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_B,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_B,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_B,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_B,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_B,
                self::UPDATE_MIQ_TERMINAL_MODE_B,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_B,
                self::UPDATE_MIQ_CARDS_TERMINAL_B,
                self::UPDATE_MIQ_UPI_TERMINAL_B,
                self::UPDATE_MIQ_BANK_TRANSFER_B,
                self::UPDATE_MIQ_OFFLINE_B,
                self::UPDATE_MIQ_TYPES_B,
                self::UPDATE_MIQ_CAPABILITY_B,
                self::UPDATE_MIQ_CURRENCY_B,
                self::UPDATE_MIQ_GATEWAY_C,
                self::UPDATE_MIQ_GATEWAY_ACQUIRER_C,
                self::UPDATE_MIQ_TERMINAL_CATEGORY_C,
                self::UPDATE_MIQ_EXISTING_GATEWAY_MERCHANT_ID_C,
                self::UPDATE_MIQ_NEW_GATEWAY_MERCHANT_ID_C,
                self::UPDATE_MIQ_GATEWAY_MERCHANT_ID_2_C,
                self::UPDATE_MIQ_GATEWAY_ACCESS_CODE_C,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_C,
                self::UPDATE_MIQ_GATEWAY_SECURE_SECRET_2_C,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_ID_C,
                self::UPDATE_MIQ_GATEWAY_TERMINAL_PASSWORD_C,
                self::UPDATE_MIQ_CONFIRM_GATEWAY_TERMINAL_PASSWORD_C,
                self::UPDATE_MIQ_TERMINAL_MODE_C,
                self::UPDATE_MIQ_GATEWAY_RECON_PASSWORD_C,
                self::UPDATE_MIQ_CARDS_TERMINAL_C,
                self::UPDATE_MIQ_UPI_TERMINAL_C,
                self::UPDATE_MIQ_BANK_TRANSFER_C,
                self::UPDATE_MIQ_OFFLINE_C,
                self::UPDATE_MIQ_TYPES_C,
                self::UPDATE_MIQ_CAPABILITY_C,
                self::UPDATE_MIQ_CURRENCY_C,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION
            ]
            ],
        Type::BVS_BULK_KYC_VERIFICATION => [
            self::INPUT => [
                self::BVS_BULK_KYC_VERIFICATION_DOCUMENT,
                self::BVS_BULK_KYC_VERIFICATION_ACCOUNT_ID,
                self::BVS_BULK_KYC_VERIFICATION_ENRICHMENT_TYPE,
            ],
            self::OUTPUT => []
        ],
        Type::STORE_ORG_DEFINED_MERCHANT_FIELDS => [
            self::INPUT => [
                self::ORG_ID,
                self::MERCHANT_ID,
                self::FIELD1,
                self::FIELD2,
                self::FIELD3,
                self::FIELD4,
                self::FIELD5,
                self::FIELD6,
                self::FIELD7,
                self::FIELD8,
                self::FIELD9,
                self::FIELD10,
                self::FIELD11,
                self::FIELD12,
                self::FIELD13,
                self::FIELD14,
                self::FIELD15
            ],
            self::OUTPUT => [
                self::ORG_ID,
                self::MERCHANT_ID,
                self::FIELD1,
                self::FIELD2,
                self::FIELD3,
                self::FIELD4,
                self::FIELD5,
                self::FIELD6,
                self::FIELD7,
                self::FIELD8,
                self::FIELD9,
                self::FIELD10,
                self::FIELD11,
                self::FIELD12,
                self::FIELD13,
                self::FIELD14,
                self::FIELD15,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION
            ]
        ],

        Type::JAMMU_AND_KASHMIR_ONBOARDING => [
            self::INPUT => [
                self::JK_REQUEST_ID,
                self::JK_MERCHANT_NAME,
                self::JK_VPA,
                self::JK_QR_STRING,
                self::JK_LANGUAGE,
                self::JK_BRANCH_ADDRESS_FOR_DELIVERY,
                self::JK_CITY,
                self::JK_STATE,
                self::JK_PINCODE,
                self::JK_BRANCH_MOBILE_NO,
                self::JK_MERCHANT_PHONE_SUPPORT,
                self::JK_ONBOARDING_DATE,
                self::JK_ONBOARDING_TIME,
                self::JK_REQUEST_STATUS,
                self::JK_MERCHANT_EMAIL_ID,
                self::JK_AIRWAY_BILLNO,
                self::JK_INSTALLATION_STATUS,
                self::JK_INSTALLATION_COMMENTS,
                self::JK_DATE_OF_INSTALLATION,
                self::JK_REJECTION_REASON,
                self::JK_MERCHANT_RECIEVED_STATE,
                self::JK_DELIVERY_STATUS,
                self::JK_REMARKS
            ],
            self::OUTPUT => [
                self::JK_REQUEST_ID,
                self::JK_MERCHANT_NAME,
                self::JK_VPA,
                self::JK_QR_STRING,
                self::JK_LANGUAGE,
                self::JK_BRANCH_ADDRESS_FOR_DELIVERY,
                self::JK_CITY,
                self::JK_STATE,
                self::JK_PINCODE,
                self::JK_BRANCH_MOBILE_NO,
                self::JK_MERCHANT_PHONE_SUPPORT,
                self::JK_ONBOARDING_DATE,
                self::JK_ONBOARDING_TIME,
                self::JK_REQUEST_STATUS,
                self::JK_MERCHANT_EMAIL_ID,
                self::JK_AIRWAY_BILLNO,
                self::JK_INSTALLATION_STATUS,
                self::JK_INSTALLATION_COMMENTS,
                self::JK_DATE_OF_INSTALLATION,
                self::JK_REJECTION_REASON,
                self::JK_MERCHANT_RECIEVED_STATE,
                self::JK_DELIVERY_STATUS,
                self::JK_REMARKS,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
                self::JK_RAZORPAY_MID,
                self::JK_MODIFIED_QR_STRING
            ]
        ],

        Type::HDFC_ONBOARDING => [
            self::INPUT => [
                self::HDFC_MECODE,
                self::HDFC_TID,
                self::HDFC_LEGAL_NAME,
                self::HDFC_DBA_NAME,
                self::HDFC_ADDRESS,
                self::HDFC_CITY,
                self::HDFC_PIN,
                self::HDFC_TEL,
                self::HDFC_PERSON,
                self::HDFC_MPR,
                self::HDFC_E_DATE,
                self::HDFC_INF_RATE,
                self::HDFC_INF_AMOUNT,
                self::HDFC_I_FLAG,
                self::HDFC_ACCNO,
                self::HDFC_ACCOUNT_NO2,
                self::HDFC_DISCNT,
                self::HDFC_FOREIGN_FLAG,
                self::HDFC_FOREIGN_COMM,
                self::HDFC_DCC_REIMB_PERCENT,
                self::HDFC_DCC_COMM_RATE,
                self::HDFC_PP_RATE,
                self::HDFC_DINERS_FLAG,
                self::HDFC_DINERS_COMM,
                self::HDFC_DINERSCOMM_ON,
                self::HDFC_RUPAY_FLAG,
                self::HDFC_ANNUAL_TURNOVER,
                self::HDFC_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_QR_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_QR_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_BBP_FLAG,
                self::HDFC_CLASSIC_ONUS_RATE,
                self::HDFC_PREMIUM_ONUS_RATE,
                self::HDFC_RENTAL,
                self::HDFC_SUBVENTION_FLAG,
                self::HDFC_ONUS_SUBVENTION,
                self::HDFC_OFFUS_SUBVENTION,
                self::HDFC_RENTALS_SUBVENTION,
                self::HDFC_TRANSACTION_SUBVENTION,
                self::HDFC_AMC_FOR_N_YRS,
                self::HDFC_AMC_AMT,
                self::HDFC_INT_FEES,
                self::HDFC_DINERS_COMM_SUBVENTION,
                self::HDFC_DINERS_COMM_ONUS_SUBVENTION,
                self::HDFC_DEPARTMENT_SUBVENTION,
                self::HDFC_BRANCH_CODE_SUBVENTION,
                self::HDFC_PLOUGH_BACK_RATE,
                self::HDFC_PLOUGH_BACK_FLAG,
                self::HDFC_MCC,
                self::HDFC_MERCHANT_TYPE,
                self::HDFC_BRANCH,
                self::HDFC_REGION,
                self::HDFC_UBSNO,
                self::HDFC_C_CODE,
                self::HDFC_PRODUCT,
                self::HDFC_MAPFLAG,
                self::HDFC_STATUS,
                self::HDFC_STATUS_DATE,
                self::HDFC_TM_EMVFLAG,
                self::HDFC_ACTIVEDEACTIVESTATUS,
                self::HDFC_DEACTIVATION_DATE,
                self::HDFC_REACTIVATIONSTATUS,
                self::HDFC_REACTIVATION_DATE,
                self::HDFC_FAX_SETUP_FLAG,
                self::HDFC_CUG_FLAG,
                self::HDFC_TCOMM,
                self::HDFC_TM_UTILFLG,
                self::HDFC_ME_XTRACOM,
                self::HDFC_SERVICE_TAX,
                self::HDFC_ME_REFUND,
                self::HDFC_DSA_CODE,
                self::HDFC_LTS_CODE,
                self::HDFC_LTS_DATE,
                self::HDFC_SERV_TAX,
                self::HDFC_SBCESS,
                self::HDFC_KKCESS,
                self::HDFC_TXN_COMMN,
                self::HDFC_WEBSITE,
                self::HDFC_DEFERED_FLAG,
                self::HDFC_DEFERED_DAYS,
                self::HDFC_DO_NOT_CALL,
                self::HDFC_TERM_CURRENCY,
                self::HDFC_PAY_CURRENCY,
                self::HDFC_SETTL_CURRENCY,
                self::HDFC_EEFC_ACCOUNT,
                self::HDFC_MARKUP,
                self::HDFC_USER_CODE,
                self::HDFC_TERMINAL_CURRENCY,
                self::HDFC_ME_CATEGORY,
                self::HDFC_SERV_CHARGE_FLAG,
                self::HDFC_SERV_CHARGE_AMT,
                self::HDFC_AMC,
                self::HDFC_INSTALLATION_FEE,
                self::HDFC_STATE_NAME,
                self::HDFC_HBL_LEAD_CON_CODE,
                self::HDFC_HBL_LEAD_GEN_CODE,
                self::HDFC_DSA_CODE1,
                self::HDFC_MPR_EMAILFLAG,
                self::HDFC_MPR_EMAILID,
                self::HDFC_WALMARTTID,
                self::HDFC_COP_REIMB_FLAG,
                self::HDFC_COP_REIMB_AMOUNT,
                self::HDFC_COP_REIMB_PER,
                self::HDFC_DCC_REIMB_FLAG,
                self::HDFC_DCC_REIMB_RATE,
                self::HDFC_ME_PAYMENT,
                self::HDFC_ME_PAYMODE,
                self::HDFC_ME_MPRTYPE,
                self::HDFC_ME_MPRCAT,
                self::HDFC_ME_DCMFLAG,
                self::HDFC_ME_DCMRATE,
                self::HDFC_ONUS_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_ONUS_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_MP_FLAG,
                self::HDFC_MP_TXN_AMOUNT,
                self::HDFC_MP_AMOUNT1_PERCENTAGE,
                self::HDFC_MP_AMOUNT2_PERCENTAGE,
                self::HDFC_MP_AMOUNT_ONUS_PERCENTAGE,
                self::HDFC_MP_FLAT_AMOUNT1,
                self::HDFC_MP_FLAT_AMOUNT2,
                self::HDFC_MP_FLAT_AMOUNT_ONUS,
                self::HDFC_CONVENIENCE_FLAG,
                self::HDFC_CONVENIENCE_RATE,
                self::HDFC_CONVENIENCE_FLAG1,
                self::HDFC_COMMERCIAL_FLAG,
                self::HDFC_CMRCL_ONUS_RATE,
                self::HDFC_APPLICATION_NUMBER,
                self::HDFC_Z_CATEOGRY,
                self::HDFC_TERMINAL_INDICATOR_FLAG,
                self::HDFC_FIXED_RATE_FLAG,
                self::HDFC_EMI_PAYBACK_PERCENTAGE,
                self::HDFC_CUSTOMIZED_MPR_TYPE,
                self::HDFC_MVISA_FLAG,
                self::HDFC_REG_TEL1,
                self::HDFC_TEL2,
                self::HDFC_TEL3,
                self::HDFC_EMAIL_ID,
                self::HDFC_AMC_FEES_FOR_POS,
                self::HDFC_AMC_DATE_FOR_POS,
                self::HDFC_PARENT_ME_CODE,
                self::HDFC_APPROVER_NAME_PAYZAP,
                self::HDFC_DRIVING_LICENCE_NO1_PAYZAP,
                self::HDFC_BRANCHCODE,
                self::HDFC_RENTAL_FREQUENCY_FLAG,
                self::HDFC_GSTN_UBS,
                self::HDFC_GSTN_UBS_STATE,
                self::HDFC_GSTN_UBS_START_DATE,
                self::HDFC_GSTN_UBS_EXPIRY_DATE,
                self::HDFC_GSTN_CURRAC,
                self::HDFC_GSTN_CURRAC_STATE,
                self::HDFC_GSTN_CURRAC_START_DATE,
                self::HDFC_GSTN_CURRAC_EXPIRY_DATE,
                self::HDFC_GSTN_ACCOUNT_NO2_ACC,
                self::HDFC_GSTN_ACCOUNT_NO2_STATE,
                self::HDFC_GSTN_ACCOUNT_NO2_START_DATE,
                self::HDFC_GSTN_ACCOUNT_NO2_EXPIRY_DATE,
                self::HDFC_GSTN_EEFC_ACC,
                self::HDFC_GSTN_EEFC_STATE,
                self::HDFC_GSTN_EEFC_START_DATE,
                self::HDFC_GSTN_EEFC_EXPIRY_DATE,
                self::HDFC_CB_DEBIT_PER,
                self::HDFC_CB_AMOUNT,
                self::HDFC_CB_CASHBACK,
                self::HDFC_EURONET_RATE,
                self::HDFC_ARC_DATE,
                self::HDFC_RBI_RATE_EXEMPT_FLAG,
                self::HDFC_INTERCHANGE_FLAG,
                self::HDFC_INTERCHANGE_PERCENT,
                self::HDFC_INTERCHANGE_FLAT_AMT,
                self::HDFC_AGGREGATOR_FLAG,
                self::HDFC_GAS_ID,
                self::HDFC_BQ_AGGR_FLAG,
                self::HDFC_BQ_AGGR_ID,
                self::HDFC_TM_VASPARTNER,
                self::HDFC_ACCOUNT_TYPE,
                self::HDFC_GENDER,
                self::HDFC_BBP_MDR_TYPE,
                self::HDFC_PREMIUM_ONUS_AMT,
                self::HDFC_PREMIUM_OFFUS_RATE,
                self::HDFC_PREMIUM_OFFUS_AMT,
                self::HDFC_CLASSIC_ONUS_AMT,
                self::HDFC_CLASSIC_OFFUS_RATE,
                self::HDFC_CLASSIC_OFFUS_AMT,
                self::HDFC_SUPER_PREMIUM_ONUS_RATE,
                self::HDFC_SUPER_PREMIUM_ONUS_AMT,
                self::HDFC_SUPER_PREMIUM_OFFUS_RATE,
                self::HDFC_SUPER_PREMIUM_OFFUS_AMT,
                self::HDFC_COMMERCIAL_MDR_TYPE,
                self::HDFC_COMMERCIAL_ONUS_AMT,
                self::HDFC_COMMERCIAL_OFFUS_RATE,
                self::HDFC_COMMERCIAL_OFFUS_AMT,
                self::HDFC_SETTLEMENT_SUBVENTION,
                self::HDFC_SETTLEMENT_SUBVENTION_AMT,
                self::HDFC_PHYSICAL_MPR_SUBVENTION,
                self::HDFC_PHYSICAL_MPR_SUBVENTION_AMT,
                self::HDFC_PAGF_SUB_FOR_NEFTCHEQUE_MER,
                self::HDFC_PAGF_SUB_AMT_NEFTCHEQUE_MER,
                self::HDFC_REFUND_REQUEST_SUBVENTION,
                self::HDFC_REFUND_REQUEST_SUBVENTION_AMT,
                self::HDFC_GOOD_FAITH_SUBVENTION,
                self::HDFC_GOOD_FAITH_SUBVENTION_AMT,
                self::HDFC_PHY_MPR_CHARGES_ADHOC_SUB,
                self::HDFC_PHY_MPR_CHARGES_ADHOC_SUB_AMT,
                self::HDFC_CBRR_SUBVENTION,
                self::HDFC_CBRR_SUBVENTION_AMT,
                self::HDFC_LUC_SUBVENTION,
                self::HDFC_LUC_SUBVENTION_PER,
                self::HDFC_SERVICE_CHARGE_SUBVENTION,
                self::HDFC_SERVICE_CHARGE_SUBVENTION_AMT,
                self::HDFC_MERCHANT_CARE_PROGRAM_SUB,
                self::HDFC_MERCHANT_CARE_PROGRAM_SUB_AMT,
                self::HDFC_TATKAL_CARE_SUBVENTION,
                self::HDFC_TATKAL_CARE_SUBVENTION_AMT,
                self::HDFC_LATE_SETTLE_SUBVENTION_FLAG,
                self::HDFC_LATE_SETTLE_SUBVENTION_PER,
                self::HDFC_AADHAR_FLAG,
                self::HDFC_AADHAR_ONUS_COMM,
                self::HDFC_AADHAR_OFFUS_COMM,
                self::HDFC_BIOMETRIC_RENT_FLAG,
                self::HDFC_BIOMETRIC_RENT,
                self::HDFC_TERM_GROUP,
                self::HDFC_FPI_V,
                self::HDFC_SH_RENTAL_FRQNCY_FLAG,
                self::HDFC_SH_RENTAL_AMT,
                self::HDFC_PROMO_CODE1,
                self::HDFC_POROMO_DATE1,
                self::HDFC_TOKEN_NUMBER,
                self::HDFC_DD_COMM_UPTO2K_RPY,
                self::HDFC_DD_COMM_ABOVE2K_RPY,
                self::HDFC_QR_DD_COMM_UPTO2K_RPY,
                self::HDFC_QR_DD_COMM_ABOVE2K_RPY,
                self::HDFC_MDR_TEMPLATE,
                self::HDFC_DD_COMM_SLB1_UPTO2K,
                self::HDFC_DD_COMM_SLB2_UPTO2K,
                self::HDFC_QR_DD_COMM_SLB1_UPTO2K,
                self::HDFC_QR_DD_COMM_SLB2_UPTO2K,
                self::HDFC_ONUS_DD_COMM_SLB1_UPTO2K,
                self::HDFC_ONUS_DD_COMM_SLB2_UPTO2K,
                self::HDFC_DD_CAPPING_AMT_SLB1,
                self::HDFC_DD_CAPPING_AMT_SLB2,
                self::HDFC_BRANCH_EMP_CODE,
                self::HDFC_SOURCE_CODE,
                self::HDFC_ME_CRM_LEAD_NO,
                self::HDFC_BASE_TID,
                self::HDFC_POS_DATE,
                self::HDFC_TERMINAL_INDICATOR,
                self::HDFC_DEACTIVATION_REASON_CODE,
                self::HDFC_PM_FLAG,
                self::HDFC_PM_AMT,
                self::HDFC_PAGF_FLAG,
                self::HDFC_PAGF_AMT,
                self::HDFC_STLC_FLAG,
                self::HDFC_STLC_AMT,
                self::HDFC_RR_FLAG,
                self::HDFC_RR_AMT,
                self::HDFC_GF_FLAG,
                self::HDFC_GF_AMT,
                self::HDFC_CBRR_FLAG,
                self::HDFC_CBRR_AMT,
                self::HDFC_PM_ADHOC_FLAG,
                self::HDFC_PM_ADHOC_AMT,
                self::HDFC_LUC_FLAG,
                self::HDFC_SC_FLAG,
                self::HDFC_SC_AMT,
                self::HDFC_MERCHANTCARE_FLAG,
                self::HDFC_MERCHANTCARE_AMT,
                self::HDFC_TATKALCARE_FLAG,
                self::HDFC_TATKALCARE_AMT,
                self::HDFC_SEZ_MERCHANT,
                self::HDFC_SEZ_CATEGORY,
                self::HDFC_SEZ_VALID_DATE,
                self::HDFC_PPR_ROLL_CHG_FLAG,
                self::HDFC_PPR_ROLLCHG_SBV_FLAG,
                self::HDFC_PPR_ROLLCHG_SBV_PER,
                self::HDFC_MOBILE_NUMBER,
                self::HDFC_ENTITY_PAN_CARD,
                self::HDFC_GIB_ACC_NO,
                self::HDFC_REF1,
                self::HDFC_GST_CONSOLIDATION_FLAG,
                self::HDFC_POS_DESIGNATED_BRANCH,
                self::HDFC_POS_DEACTIVATION_FLAG,
                self::HDFC_POS_DEACTIVATION_CHARGE,
                self::HDFC_VAS_PROGRAM_FLAG,
                self::HDFC_VOLUME_RENTAL_FLAG,
                self::HDFC_ONLINE_REFUND_FLAG,
                self::HDFC_UNSECURE_TRANSACTION_FLAG,
                self::HDFC_UCIC_NAME,
                self::HDFC_TRANSITORY_GL_FLAG,
                self::HDFC_EEFC_DEBIT_FLAG,
            ],
            self::OUTPUT => [
                self::HDFC_MECODE,
                self::HDFC_TID,
                self::HDFC_LEGAL_NAME,
                self::HDFC_DBA_NAME,
                self::HDFC_ADDRESS,
                self::HDFC_CITY,
                self::HDFC_PIN,
                self::HDFC_TEL,
                self::HDFC_PERSON,
                self::HDFC_MPR,
                self::HDFC_E_DATE,
                self::HDFC_INF_RATE,
                self::HDFC_INF_AMOUNT,
                self::HDFC_I_FLAG,
                self::HDFC_ACCNO,
                self::HDFC_ACCOUNT_NO2,
                self::HDFC_DISCNT,
                self::HDFC_FOREIGN_FLAG,
                self::HDFC_FOREIGN_COMM,
                self::HDFC_DCC_REIMB_PERCENT,
                self::HDFC_DCC_COMM_RATE,
                self::HDFC_PP_RATE,
                self::HDFC_DINERS_FLAG,
                self::HDFC_DINERS_COMM,
                self::HDFC_DINERSCOMM_ON,
                self::HDFC_RUPAY_FLAG,
                self::HDFC_ANNUAL_TURNOVER,
                self::HDFC_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_QR_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_QR_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_BBP_FLAG,
                self::HDFC_CLASSIC_ONUS_RATE,
                self::HDFC_PREMIUM_ONUS_RATE,
                self::HDFC_RENTAL,
                self::HDFC_SUBVENTION_FLAG,
                self::HDFC_ONUS_SUBVENTION,
                self::HDFC_OFFUS_SUBVENTION,
                self::HDFC_RENTALS_SUBVENTION,
                self::HDFC_TRANSACTION_SUBVENTION,
                self::HDFC_AMC_FOR_N_YRS,
                self::HDFC_AMC_AMT,
                self::HDFC_INT_FEES,
                self::HDFC_DINERS_COMM_SUBVENTION,
                self::HDFC_DINERS_COMM_ONUS_SUBVENTION,
                self::HDFC_DEPARTMENT_SUBVENTION,
                self::HDFC_BRANCH_CODE_SUBVENTION,
                self::HDFC_PLOUGH_BACK_RATE,
                self::HDFC_PLOUGH_BACK_FLAG,
                self::HDFC_MCC,
                self::HDFC_MERCHANT_TYPE,
                self::HDFC_BRANCH,
                self::HDFC_REGION,
                self::HDFC_UBSNO,
                self::HDFC_C_CODE,
                self::HDFC_PRODUCT,
                self::HDFC_MAPFLAG,
                self::HDFC_STATUS,
                self::HDFC_STATUS_DATE,
                self::HDFC_TM_EMVFLAG,
                self::HDFC_ACTIVEDEACTIVESTATUS,
                self::HDFC_DEACTIVATION_DATE,
                self::HDFC_REACTIVATIONSTATUS,
                self::HDFC_REACTIVATION_DATE,
                self::HDFC_FAX_SETUP_FLAG,
                self::HDFC_CUG_FLAG,
                self::HDFC_TCOMM,
                self::HDFC_TM_UTILFLG,
                self::HDFC_ME_XTRACOM,
                self::HDFC_SERVICE_TAX,
                self::HDFC_ME_REFUND,
                self::HDFC_DSA_CODE,
                self::HDFC_LTS_CODE,
                self::HDFC_LTS_DATE,
                self::HDFC_SERV_TAX,
                self::HDFC_SBCESS,
                self::HDFC_KKCESS,
                self::HDFC_TXN_COMMN,
                self::HDFC_WEBSITE,
                self::HDFC_DEFERED_FLAG,
                self::HDFC_DEFERED_DAYS,
                self::HDFC_DO_NOT_CALL,
                self::HDFC_TERM_CURRENCY,
                self::HDFC_PAY_CURRENCY,
                self::HDFC_SETTL_CURRENCY,
                self::HDFC_EEFC_ACCOUNT,
                self::HDFC_MARKUP,
                self::HDFC_USER_CODE,
                self::HDFC_TERMINAL_CURRENCY,
                self::HDFC_ME_CATEGORY,
                self::HDFC_SERV_CHARGE_FLAG,
                self::HDFC_SERV_CHARGE_AMT,
                self::HDFC_AMC,
                self::HDFC_INSTALLATION_FEE,
                self::HDFC_STATE_NAME,
                self::HDFC_HBL_LEAD_CON_CODE,
                self::HDFC_HBL_LEAD_GEN_CODE,
                self::HDFC_DSA_CODE1,
                self::HDFC_MPR_EMAILFLAG,
                self::HDFC_MPR_EMAILID,
                self::HDFC_WALMARTTID,
                self::HDFC_COP_REIMB_FLAG,
                self::HDFC_COP_REIMB_AMOUNT,
                self::HDFC_COP_REIMB_PER,
                self::HDFC_DCC_REIMB_FLAG,
                self::HDFC_DCC_REIMB_RATE,
                self::HDFC_ME_PAYMENT,
                self::HDFC_ME_PAYMODE,
                self::HDFC_ME_MPRTYPE,
                self::HDFC_ME_MPRCAT,
                self::HDFC_ME_DCMFLAG,
                self::HDFC_ME_DCMRATE,
                self::HDFC_ONUS_DD_COMM_SLB1_ABOVE2K,
                self::HDFC_ONUS_DD_COMM_SLB2_ABOVE2K,
                self::HDFC_MP_FLAG,
                self::HDFC_MP_TXN_AMOUNT,
                self::HDFC_MP_AMOUNT1_PERCENTAGE,
                self::HDFC_MP_AMOUNT2_PERCENTAGE,
                self::HDFC_MP_AMOUNT_ONUS_PERCENTAGE,
                self::HDFC_MP_FLAT_AMOUNT1,
                self::HDFC_MP_FLAT_AMOUNT2,
                self::HDFC_MP_FLAT_AMOUNT_ONUS,
                self::HDFC_CONVENIENCE_FLAG,
                self::HDFC_CONVENIENCE_RATE,
                self::HDFC_CONVENIENCE_FLAG1,
                self::HDFC_COMMERCIAL_FLAG,
                self::HDFC_CMRCL_ONUS_RATE,
                self::HDFC_APPLICATION_NUMBER,
                self::HDFC_Z_CATEOGRY,
                self::HDFC_TERMINAL_INDICATOR_FLAG,
                self::HDFC_FIXED_RATE_FLAG,
                self::HDFC_EMI_PAYBACK_PERCENTAGE,
                self::HDFC_CUSTOMIZED_MPR_TYPE,
                self::HDFC_MVISA_FLAG,
                self::HDFC_REG_TEL1,
                self::HDFC_TEL2,
                self::HDFC_TEL3,
                self::HDFC_EMAIL_ID,
                self::HDFC_AMC_FEES_FOR_POS,
                self::HDFC_AMC_DATE_FOR_POS,
                self::HDFC_PARENT_ME_CODE,
                self::HDFC_APPROVER_NAME_PAYZAP,
                self::HDFC_DRIVING_LICENCE_NO1_PAYZAP,
                self::HDFC_BRANCHCODE,
                self::HDFC_RENTAL_FREQUENCY_FLAG,
                self::HDFC_GSTN_UBS,
                self::HDFC_GSTN_UBS_STATE,
                self::HDFC_GSTN_UBS_START_DATE,
                self::HDFC_GSTN_UBS_EXPIRY_DATE,
                self::HDFC_GSTN_CURRAC,
                self::HDFC_GSTN_CURRAC_STATE,
                self::HDFC_GSTN_CURRAC_START_DATE,
                self::HDFC_GSTN_CURRAC_EXPIRY_DATE,
                self::HDFC_GSTN_ACCOUNT_NO2_ACC,
                self::HDFC_GSTN_ACCOUNT_NO2_STATE,
                self::HDFC_GSTN_ACCOUNT_NO2_START_DATE,
                self::HDFC_GSTN_ACCOUNT_NO2_EXPIRY_DATE,
                self::HDFC_GSTN_EEFC_ACC,
                self::HDFC_GSTN_EEFC_STATE,
                self::HDFC_GSTN_EEFC_START_DATE,
                self::HDFC_GSTN_EEFC_EXPIRY_DATE,
                self::HDFC_CB_DEBIT_PER,
                self::HDFC_CB_AMOUNT,
                self::HDFC_CB_CASHBACK,
                self::HDFC_EURONET_RATE,
                self::HDFC_ARC_DATE,
                self::HDFC_RBI_RATE_EXEMPT_FLAG,
                self::HDFC_INTERCHANGE_FLAG,
                self::HDFC_INTERCHANGE_PERCENT,
                self::HDFC_INTERCHANGE_FLAT_AMT,
                self::HDFC_AGGREGATOR_FLAG,
                self::HDFC_GAS_ID,
                self::HDFC_BQ_AGGR_FLAG,
                self::HDFC_BQ_AGGR_ID,
                self::HDFC_TM_VASPARTNER,
                self::HDFC_ACCOUNT_TYPE,
                self::HDFC_GENDER,
                self::HDFC_BBP_MDR_TYPE,
                self::HDFC_PREMIUM_ONUS_AMT,
                self::HDFC_PREMIUM_OFFUS_RATE,
                self::HDFC_PREMIUM_OFFUS_AMT,
                self::HDFC_CLASSIC_ONUS_AMT,
                self::HDFC_CLASSIC_OFFUS_RATE,
                self::HDFC_CLASSIC_OFFUS_AMT,
                self::HDFC_SUPER_PREMIUM_ONUS_RATE,
                self::HDFC_SUPER_PREMIUM_ONUS_AMT,
                self::HDFC_SUPER_PREMIUM_OFFUS_RATE,
                self::HDFC_SUPER_PREMIUM_OFFUS_AMT,
                self::HDFC_COMMERCIAL_MDR_TYPE,
                self::HDFC_COMMERCIAL_ONUS_AMT,
                self::HDFC_COMMERCIAL_OFFUS_RATE,
                self::HDFC_COMMERCIAL_OFFUS_AMT,
                self::HDFC_SETTLEMENT_SUBVENTION,
                self::HDFC_SETTLEMENT_SUBVENTION_AMT,
                self::HDFC_PHYSICAL_MPR_SUBVENTION,
                self::HDFC_PHYSICAL_MPR_SUBVENTION_AMT,
                self::HDFC_PAGF_SUB_FOR_NEFTCHEQUE_MER,
                self::HDFC_PAGF_SUB_AMT_NEFTCHEQUE_MER,
                self::HDFC_REFUND_REQUEST_SUBVENTION,
                self::HDFC_REFUND_REQUEST_SUBVENTION_AMT,
                self::HDFC_GOOD_FAITH_SUBVENTION,
                self::HDFC_GOOD_FAITH_SUBVENTION_AMT,
                self::HDFC_PHY_MPR_CHARGES_ADHOC_SUB,
                self::HDFC_PHY_MPR_CHARGES_ADHOC_SUB_AMT,
                self::HDFC_CBRR_SUBVENTION,
                self::HDFC_CBRR_SUBVENTION_AMT,
                self::HDFC_LUC_SUBVENTION,
                self::HDFC_LUC_SUBVENTION_PER,
                self::HDFC_SERVICE_CHARGE_SUBVENTION,
                self::HDFC_SERVICE_CHARGE_SUBVENTION_AMT,
                self::HDFC_MERCHANT_CARE_PROGRAM_SUB,
                self::HDFC_MERCHANT_CARE_PROGRAM_SUB_AMT,
                self::HDFC_TATKAL_CARE_SUBVENTION,
                self::HDFC_TATKAL_CARE_SUBVENTION_AMT,
                self::HDFC_LATE_SETTLE_SUBVENTION_FLAG,
                self::HDFC_LATE_SETTLE_SUBVENTION_PER,
                self::HDFC_AADHAR_FLAG,
                self::HDFC_AADHAR_ONUS_COMM,
                self::HDFC_AADHAR_OFFUS_COMM,
                self::HDFC_BIOMETRIC_RENT_FLAG,
                self::HDFC_BIOMETRIC_RENT,
                self::HDFC_TERM_GROUP,
                self::HDFC_FPI_V,
                self::HDFC_SH_RENTAL_FRQNCY_FLAG,
                self::HDFC_SH_RENTAL_AMT,
                self::HDFC_PROMO_CODE1,
                self::HDFC_POROMO_DATE1,
                self::HDFC_TOKEN_NUMBER,
                self::HDFC_DD_COMM_UPTO2K_RPY,
                self::HDFC_DD_COMM_ABOVE2K_RPY,
                self::HDFC_QR_DD_COMM_UPTO2K_RPY,
                self::HDFC_QR_DD_COMM_ABOVE2K_RPY,
                self::HDFC_MDR_TEMPLATE,
                self::HDFC_DD_COMM_SLB1_UPTO2K,
                self::HDFC_DD_COMM_SLB2_UPTO2K,
                self::HDFC_QR_DD_COMM_SLB1_UPTO2K,
                self::HDFC_QR_DD_COMM_SLB2_UPTO2K,
                self::HDFC_ONUS_DD_COMM_SLB1_UPTO2K,
                self::HDFC_ONUS_DD_COMM_SLB2_UPTO2K,
                self::HDFC_DD_CAPPING_AMT_SLB1,
                self::HDFC_DD_CAPPING_AMT_SLB2,
                self::HDFC_BRANCH_EMP_CODE,
                self::HDFC_SOURCE_CODE,
                self::HDFC_ME_CRM_LEAD_NO,
                self::HDFC_BASE_TID,
                self::HDFC_POS_DATE,
                self::HDFC_TERMINAL_INDICATOR,
                self::HDFC_DEACTIVATION_REASON_CODE,
                self::HDFC_PM_FLAG,
                self::HDFC_PM_AMT,
                self::HDFC_PAGF_FLAG,
                self::HDFC_PAGF_AMT,
                self::HDFC_STLC_FLAG,
                self::HDFC_STLC_AMT,
                self::HDFC_RR_FLAG,
                self::HDFC_RR_AMT,
                self::HDFC_GF_FLAG,
                self::HDFC_GF_AMT,
                self::HDFC_CBRR_FLAG,
                self::HDFC_CBRR_AMT,
                self::HDFC_PM_ADHOC_FLAG,
                self::HDFC_PM_ADHOC_AMT,
                self::HDFC_LUC_FLAG,
                self::HDFC_SC_FLAG,
                self::HDFC_SC_AMT,
                self::HDFC_MERCHANTCARE_FLAG,
                self::HDFC_MERCHANTCARE_AMT,
                self::HDFC_TATKALCARE_FLAG,
                self::HDFC_TATKALCARE_AMT,
                self::HDFC_SEZ_MERCHANT,
                self::HDFC_SEZ_CATEGORY,
                self::HDFC_SEZ_VALID_DATE,
                self::HDFC_PPR_ROLL_CHG_FLAG,
                self::HDFC_PPR_ROLLCHG_SBV_FLAG,
                self::HDFC_PPR_ROLLCHG_SBV_PER,
                self::HDFC_MOBILE_NUMBER,
                self::HDFC_ENTITY_PAN_CARD,
                self::HDFC_GIB_ACC_NO,
                self::HDFC_REF1,
                self::HDFC_GST_CONSOLIDATION_FLAG,
                self::HDFC_POS_DESIGNATED_BRANCH,
                self::HDFC_POS_DEACTIVATION_FLAG,
                self::HDFC_POS_DEACTIVATION_CHARGE,
                self::HDFC_VAS_PROGRAM_FLAG,
                self::HDFC_VOLUME_RENTAL_FLAG,
                self::HDFC_ONLINE_REFUND_FLAG,
                self::HDFC_UNSECURE_TRANSACTION_FLAG,
                self::HDFC_UCIC_NAME,
                self::HDFC_TRANSITORY_GL_FLAG,
                self::HDFC_EEFC_DEBIT_FLAG,
                self::STATUS,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ],

        Type::UPDATE_ODS_MERCHANT_LIMITS => [
            self::INPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
            ],
            self::OUTPUT => [
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MERCHANT_ID,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_LIMIT_PER_WORKING_DAY,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_MAX_AMOUNT_LIMIT,
                self::SETTLEMENT_ONDEMAND_FEATURE_CONFIG_PERCENTAGE_OF_BALANCE_LIMIT,
            ],
        ],

        Type::OTC_PAYMENT_CREDIT => [
            self::INPUT => [
                self::CLIENT,
                self::PRODUCT,
                self::ARRANGEMENT,
                self::DEPSLIPNUM,
                self::BATCHNUM,
                self::SCHNO,
                self::CLGLOC,
                self::PICKUPLOC,
                self::PICKUPPOINT,
                self::DRAWEEBANK,
                self::INSTNUM,
                self::INSTAMT,
                self::INSTDATE,
                self::DEPDATE,
                self::DRAWERCODE,
                self::DRAWERDES,
                self::VALUE_DATE,
                self::STATUS,
                self::RETURNRES,
                self::ACTIVATIONDATE,
                self::VALDATE,
                self::INTERNALINSTNMBR,
                self::MICRCODE,
                self::ENTRYREJRMKS,
                self::DEPOSITBRANCH,
                self::E1,
                self::E2,
                self::E3,
                self::E4,
                self::E5,
                self::E6,
                self::E7,
                self::E8,
                self::E9,
                self::E10,
                self::E11,
                self::E12,
                self::E13,
                self::E14,
                self::E15,
                self::E16,
                self::E17,
                self::E18,
                self::E19,
                self::E20,
                self::E21,
            ]
        ],

        Type::DEVICE_TERMINAL_MAPPING => [
            self::INPUT => [
                self::DEVICE_TERMINAL_MAPPING_TERMINAL_ID,
                self::DEVICE_TERMINAL_MAPPING_DEVICE_ID,
            ],
            self::OUTPUT => [
                self::TERMINAL_ID,
                self::DEVICE_TERMINAL_MAPPING_DEVICE_ID,
                self::ERROR_CODE,
                self::ERROR_DESCRIPTION,
            ],
        ]

    ];

    /**
     * Additional headers added against each entry detailing the type of error
     * and its description, if any. Used in Validated file output.
     */
    const VALIDATED_HEADERS = [
        self::ERROR_CODE,
        self::ERROR_DESCRIPTION,
    ];

    const MIQ_ADDITIONAL_FIELDS = [
        self::FIELD1,
        self::FIELD2,
        self::FIELD3,
        self::FIELD4,
        self::FIELD5,
        self::FIELD6,
        self::FIELD7,
        self::FIELD8,
        self::FIELD9,
        self::FIELD10,
        self::FIELD11,
        self::FIELD12,
        self::FIELD13,
        self::FIELD14,
        self::FIELD15
    ];

    /**
     * Validates headers of batch input file.
     *
     * @param string $type
     * @param array  $actualHeaders
     *
     * @throws BadRequestException
     */
    public static function validate(string $type, array $actualHeaders, string $orgId = null)
    {

        $expectedHeaders = Header::getInputHeadersForType($type);

        //
        // Notes is optional header in file. Currently optional headers are not supported and so this quick workaround
        // to get validation passing. Soon we will have support for optional headers.
        //
        if ((in_array(self::NOTES, $expectedHeaders, true) === true) and
            (in_array(self::NOTES, $actualHeaders, true) === false))
        {
            $actualHeaders[] = self::NOTES;
        }
        if ((in_array(self::PRODUCTS, $actualHeaders, true) === true) and
            (in_array(self::PRODUCTS, $expectedHeaders, true) === false))
        {
            $expectedHeaders[] = self::PRODUCTS;
        }

        //
        // Headers are not present in tally payouts hence, no validation needed.
        //
        if ($type === Type::TALLY_PAYOUT) {
            return;
        }


        if ($orgId !== null)
        {
            $permissionEnabled = (new Org\Service)->isRequiredPermissionEnabledforOrg($orgId, Permission\Name::ORG_DEFINED_CUSTOM_MERCHANT_FIELDS);
        }

        if (($type === Type::MERCHANT_UPLOAD_MIQ || $type === Type::UPDATE_MIQ) && !empty($permissionEnabled) && $permissionEnabled === true)
        {
            $expectedHeaders = Header::getMiqHeadersWithAdditionalFields($type);
        }

        //
        // Speed is also optional. See ^above comments about Notes;
        // Speed is optional for batch type refunds.
        //
        if (($type === Type::REFUND) and
            (in_array(self::SPEED, $expectedHeaders, true) === true) and
            (in_array(self::SPEED, $actualHeaders, true) === false))
        {
            $actualHeaders[] = self::SPEED;
        }

        //
        // RECEIPT is also optional. See ^above comments about Notes;
        // RECEIPT is optional for batch type refunds.
        //
        if (($type === Type::REFUND) and
            (in_array(self::RECEIPT, $expectedHeaders, true) === true) and
            (in_array(self::RECEIPT, $actualHeaders, true) === false))
        {
            $actualHeaders[] = self::RECEIPT;
        }

        // GatewayAcquirer is also optional. See ^above comments about Notes;
        // GatewayAcquirer is optional for batch type merchant_onboarding_emi_sbi.
        //
        if (($type == Type::MERCHANT_ONBOARDING_EMI_SBI) and
            (in_array(self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_ACQUIRER, $actualHeaders, true) === true) and
            (in_array(self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_ACQUIRER, $expectedHeaders, true) === false))
        {
            $expectedHeaders[] = self::MERCHANT_ONBOARDING_EMI_SBI_GATEWAY_ACQUIRER;
        }

        //
        // For PL batch, we want to optionally accept the FIRST_PAYMENT_MIN_AMOUNT
        // headers. This is temporary until we have support for optional headers.
        //
        if (($type === Type::PAYMENT_LINK or $type === Type::PAYMENT_LINK_V2) and
            ((in_array(self::FIRST_PAYMENT_MIN_AMOUNT, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = self::FIRST_PAYMENT_MIN_AMOUNT;
        }

        if ($type === Type::AUTH_LINK)
        {
            if (in_array(self::AUTH_LINK_NACH_REFERENCE1, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = self::AUTH_LINK_NACH_REFERENCE1;
            }
            if (in_array(self::AUTH_LINK_NACH_REFERENCE2, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = self::AUTH_LINK_NACH_REFERENCE2;
            }
            if (in_array(self::AUTH_LINK_NACH_CREATE_FORM, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = self::AUTH_LINK_NACH_CREATE_FORM;
            }
        }

        if (($type === Type::PAYMENT_LINK or $type === Type::PAYMENT_LINK_V2) and
            ((in_array(self::CURRENCY, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = self::CURRENCY;
        }

        if (($type === Type::PAYMENT_LINK_V2) and
            ((in_array(self::PL_V2_UPI_LINK, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = self::PL_V2_UPI_LINK;
        }

        /**
         * Checks for the custom field headers of format custom_field[key]
         * If they are present and correct then it will add it to expected headers array
         * */
        if (($type === Type::PAYMENT_LINK_V2))
        {
            $isCustomFieldsPresent = self::getAndvalidateCustomFieldHeaders($actualHeaders);
            if($isCustomFieldsPresent === true) {
                   foreach (self::$plV2CustomFields as $customField){
                        $expectedHeaders[] = $customField;
                   }
               }
        }

        //
        // For Pricing Rule batch, we want to optionally accept the PRICING_RULE_UPDATE and PRICING_RULE_FEE_BEARER
        // headers.
        //
        if ($type === Type::PRICING_RULE) {
            if (in_array(self::PRICING_RULE_UPDATE, $actualHeaders, true) === true) {
                $expectedHeaders[] = self::PRICING_RULE_UPDATE;
            }
            if (in_array(self::PRICING_RULE_FEE_BEARER, $actualHeaders, true) === true)
            {
                $expectedHeaders[] = self::PRICING_RULE_FEE_BEARER;
            }
        }

        if ($type === Type::IIN_NPCI_RUPAY)
        {
            // header is dynamic for these dat files, adding hack to ignore
            $actualHeaders = [];
        }

        if ($type === Type::ADMIN_BATCH)
        {
            $expectedHeaders [] = self::validateAdminBatchHeader($actualHeaders);
        }

        if ($type === Type::MPAN)
        {
            // header can contain empty columns when input file is CSV
            // this is due to trailing comma in the given input file
            $actualHeaders = array_filter($actualHeaders);
        }

        //
        // In case of subMerchant batch adding support of optional header merchant_id
        // With this data support team will be able to fix issue by their own and we can move this batch to new service .
        //
        if (($type === Type::SUB_MERCHANT) and
            ((in_array(self::MERCHANT_ID, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = self::MERCHANT_ID;
        }

        if (($type === Type::SUB_MERCHANT) and
            (in_array(self::FEE_BEARER, $expectedHeaders, true) === true) and
            (in_array(self::FEE_BEARER, $actualHeaders, true) === false))
        {
            $expectedHeaders[] = 'reference1';
            $expectedHeaders = array_diff($expectedHeaders, [self::FEE_BEARER]);
        }

        // TODO: Update the batch header once FE changes for the same are deployed on prod.
        if (($type === Type::PARTNER_SUBMERCHANT_INVITE) and
            ((in_array(self::CONTACT_MOBILE, $actualHeaders, true) === true)))
        {
            $expectedHeaders[] = self::CONTACT_MOBILE;
        }

        if ( $type === Type::RAW_ADDRESS)
        {
            self::validateRawAddressBulkHeaders($expectedHeaders, $actualHeaders);
            return;
        }

        if ( $type === Type::FULFILLMENT_ORDER_UPDATE)
        {
            self::validateFulfillmentOrderUpdateBulkHeaders($expectedHeaders, $actualHeaders);
            return;
        }

        if ($type === Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST)
        {
            self::validateCODEligibilityAttributeWhitelistBulkHeaders($expectedHeaders, $actualHeaders);
        }

        if ($type === Type::ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST)
        {
            self::validateCODEligibilityAttributeBlacklistBulkHeaders($expectedHeaders, $actualHeaders);
        }

        if ($type === Type::CREATE_WALLET_ACCOUNTS)
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_WALLET_ACCOUNTS);
        }

        if ($type === Type::CREATE_WALLET_LOADS)
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_WALLET_LOADS);
        }

        if ($type === Type::CREATE_WALLET_CONTAINER_LOADS)
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_WALLET_CONTAINER_LOADS);
        }

        if ($type === Type::CREATE_WALLET_USER_CONTAINERS)
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_WALLET_USERS);
        }

        if ($type === Type::CREATE_WALLET_CONTAINER_REVERSALS)
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_WALLET_CONTAINER_REVERSALS);
        }

        if ($type === Type::CREATE_BULK_GIFT_CARDS )
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_CREATE_BULK_GIFT_CARDS);
        }

        if ($type === Type::CREATE_GIFT_CARD_TRANSFERS )
        {
            self::validateWalletBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_CREATE_GIFT_CARD_TRANSFERS);
        }

        if ($type === Type::UPDATE_GIFT_CARDS_EXPIRY )
        {
            self::validateWalletUpdateGCExpiryBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_UPDATE_GIFT_CARDS_EXPIRY);
        }

        if ($type === Type::GCMS_UPLOAD_BULK_EMAILS)
        {
            self::validateGCOMSBatchHeaders($expectedHeaders, $actualHeaders, self::MANDATORY_HEADERS_FOR_UPLOAD_BULK_EMAILS);
        }

        // For payouts, we do not want to match exact headers, because we are allowing some headers to be skipped.
        // Since some headers can be skipped, we are also allowing for rearrangement of headers
        // and hence there are no strict checks inside payout batch file header validations.
        if ($type === Type::PAYOUT)
        {
            self::validatePayoutHeaders($expectedHeaders, $actualHeaders);

            return;
        }
        // For Payout Links, some of the headers are optional.
        elseif ($type === Type::PAYOUT_LINK_BULK or $type === Type::PAYOUT_LINK_BULK_V2)
        {
            self::validatePayoutLinkBulkHeaders($expectedHeaders, $actualHeaders);

            return;
        }
        elseif ($type === Type::FUND_ACCOUNT)
        {
            self::validateFundAccountBulkHeaders($expectedHeaders, $actualHeaders);

            return;
        }
        elseif ($type === Type::FUND_ACCOUNT_V2)
        {
            self::validateFundAccountV2BulkHeaders($expectedHeaders, $actualHeaders);

            return;
        }
        else
        {
            $valid = self::areTwoHeadersSame($expectedHeaders, $actualHeaders);
        }

        // Todo: Fix this hack!
        if (($valid === false) and ($type === Type::PAYMENT_LINK or $type === Type::PAYMENT_LINK_V2))
        {
            $expectedHeaders = array_replace($expectedHeaders, [4 => self::AMOUNT_IN_PAISE]);

            $valid = self::areTwoHeadersSame($expectedHeaders, $actualHeaders);
        }

        if (($valid === false) and ($type === Type::PAYMENT_PAGE))
        {
            return;
        }


        if ($valid === false)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                null,
                [
                    'expected_headers' => $expectedHeaders,
                    'input_headers'    => $actualHeaders,
                ]);
        }
    }

    public static function validatePaymentPageHeaders(array $actualHeaders, array $config)
    {
        $pl_id = PL\Entity::silentlyStripSign($config['payment_page_id']);

        $udf_schema = (new Settings())->getSettings($pl_id, 'payment_link', 'udf_schema');

        $udf_schema = json_decode($udf_schema['value'], true);

        foreach ($udf_schema as $udf)
        {
            $title = $udf['title'];

            if ($udf['required'] === true)
            {
                if (!in_array($title, $actualHeaders,'true'))
                {

                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                        null,
                        [
                            'expected_header' => $title,
                            'input_headers'    => $actualHeaders,
                        ]);
                }
            }

            // Remove the checked header from $actualHeaders to track extra elements later
            unset($actualHeaders[array_search($title, $actualHeaders)]);
        }

        $paymentLink = (new PaymentLink())->find($pl_id);

        $payment_page_items = (new PPI\Repository())->fetchByPaymentLinkIdAndMerchant($paymentLink->getId(), $paymentLink->getMerchantId());

        foreach ($payment_page_items as $paymentPageItem) {

            $item = $paymentPageItem->item;

            if($paymentPageItem['mandatory'] === true)
            {
                if (!in_array($item['name'], $actualHeaders,'true'))
                {

                    throw new BadRequestException(
                        ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                        null,
                        [
                            'expected_header' => $item['name'],
                            'input_headers'    => $actualHeaders,
                        ]);
                }
            }

            // Remove the checked header from $actualHeaders to track extra elements later
            unset($actualHeaders[array_search($item['name'], $actualHeaders)]);
        }

        // Check for extra headers not present in udf_schema or payment_page_items
        if (empty($actualHeaders) === false) {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
                null,
                [
                    'unexpected_headers' => $actualHeaders,
                ]
            );
        }
    }

    public static function validateWalletBatchHeaders(array $expectedHeaders, array $actualHeaders, array $mandatoryHeaders)
    {
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateWalletUpdateGCExpiryBatchHeaders(array $expectedHeaders, array $actualHeaders, array $mandatoryHeaders)
    {
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0 && !(count($mandatoryHeaders) === 1 && in_array($mandatoryHeaders[0], [self::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_ID, self::UPDATE_GIFT_CARDS_EXPIRY_GIFT_CARD_NUMBER])))
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateGCOMSBatchHeaders(array $expectedHeaders, array $actualHeaders, array $mandatoryHeaders)
    {
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    protected static function validateAdminBatchHeader($actualHeaders)
    {
        $validInputArray = ['allow_all_merchants'];

        $expectedHeaders = '';

        foreach ($validInputArray as $attribute)
        {
            if ((in_array($attribute, $actualHeaders, true) === true))
            {
                $expectedHeaders = $attribute;

            }
        }

        return $expectedHeaders;
    }

    /**
     * Validates notes keys:
     * - No more than 15 keys,
     * - Each key's length should be less than or equals to 256
     *
     * @param  array $notesKeys
     * @throws BadRequestValidationFailureException
     */
    public static function validateNotesKeys(array $notesKeys)
    {
        if (count($notesKeys) > 15)
        {
            throw new BadRequestValidationFailureException(
                'Number of headers for notes should not exceed 15',
                null,
                [self::NOTES => $notesKeys]);
        }

        $notesKeysTooLarge = array_filter($notesKeys, function (string $k)
        {
            return strlen($k) > 256;
        });

        if (count($notesKeysTooLarge) > 0)
        {
            throw new BadRequestValidationFailureException(
                'No notes headers should have keys with length exceeding 256 characters',
                null,
                [self::NOTES => $notesKeys]);
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

    public static function getMiqHeadersWithAdditionalFields(string $type): array
    {
        return array_merge(self::HEADER_MAP[$type][self::INPUT], self::MIQ_ADDITIONAL_FIELDS);
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

    public static function areTwoHeadersSame(array $headings1, array $headings2): bool
    {
        return ((count($headings1) === count($headings2)) and
                (array_diff($headings1, $headings2) === array_diff($headings2, $headings1)));
    }

    public static function validateFundAccountBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_FUND_ACCOUNTS;

        // We cannot do a strict check here as it will break existing validations.
        // The sample files now have two versions, one before the bulk improvements project and the other after it.
        // We need to support validations for both.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now we shall make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                throw new BadRequestValidationFailureException('The uploaded file has invalid header: ' . $actualHeader);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }
    }

    public static function validateFundAccountV2BulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_FUND_ACCOUNTS_V2;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now we shall make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                throw new BadRequestValidationFailureException('The uploaded file has invalid header: ' . $actualHeader);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }
    }

    public static function validatePayoutHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_AND_CONDITIONALLY_MANDATORY_HEADERS_FOR_PAYOUTS;

        // We cannot do a strict check here as it will break existing validations.
        // The sample files now have two versions, one before the bulk improvements project and the other after it.
        // We need to support validations for both.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        // At this point, we should have exactly one header (payout amount or payout amount (in rupees))
        // If count is anything else, we should throw an error.
        if (count($mandatoryHeaders) > 1)
        {
            throw new BadRequestException(
                ErrorCode::BAD_REQUEST_PAYOUT_BATCH_FILE_MISSING_MANDATORY_HEADERS,
                null,
                [
                    'expected_headers' => $expectedHeaders,
                    'input_headers'    => $actualHeaders,
                ]);
        }

        if (count($mandatoryHeaders) === 0)
        {
            $message = 'You seem to have entered a wrong amount header. The amount has to be entered either in Rupees format or in Paise format';

            throw new BadRequestValidationFailureException($message);
        }

        // Now we shall make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                throw new BadRequestValidationFailureException('The uploaded file has invalid header: ' . $actualHeader);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }
    }

    public static function validatePayoutLinkBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_HEADERS_FOR_PAYOUT_LINK_BULK;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateRawAddressBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_HEADERS_FOR_RAW_ADDRESS_BULK;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateFulfillmentOrderUpdateBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_HEADERS_FOR_FULFILLMENT_ORDER;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateCODEligibilityAttributeWhitelistBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_HEADERS_FOR_ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_WHITELIST;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    public static function validateCODEligibilityAttributeBlacklistBulkHeaders(array $expectedHeaders, array $actualHeaders)
    {
        $mandatoryHeaders = self::MANDATORY_HEADERS_FOR_ONE_CC_COD_ELIGIBILITY_ATTRIBUTE_BLACKLIST;

        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $mandatoryHeaders, true) === true)
            {
                // This will remove the header we just validated from the list of mandatory headers.
                $mandatoryHeaders = array_diff($mandatoryHeaders, [$actualHeader]);
            }
        }

        if (count($mandatoryHeaders) > 0)
        {
            $msg = 'Uploaded file is missing mandatory header(s) [%s]';

            $msg = sprintf($msg, implode(', ',$mandatoryHeaders));

            throw new BadRequestValidationFailureException($msg);
        }

        // Now make sure that all headers provided are part of our headers list.
        foreach ($actualHeaders as $actualHeader)
        {
            if (in_array($actualHeader, $expectedHeaders, true) === false)
            {
                $msg = 'Uploaded file has has invalid header [%s]';

                $msg = sprintf($msg, $actualHeader);

                throw new BadRequestValidationFailureException($msg);
            }

            // This is required so that we throw an exception if the same header is repeated twice.
            $expectedHeaders = array_diff($expectedHeaders, [$actualHeader]);
        }

    }

    /**
     *checks if the custom_field headers is present and extract the value of key from custom_field[key] into customFieldHeaders array.
     * send customFieldHeaders to validateCustomFields Function for validation of this key.
     * If the keys are validate. we will add the headers(custom_field[key]) to plV2DynamicField array.
     */
    public static function getAndvalidateCustomFieldHeaders($actualHeaders) : bool
    {
        $customFieldHeaders = [];

        $searchPrefix = "custom_field[";
        $searchSuffix = "]";

        foreach ($actualHeaders as $key) {
            if ((str_starts_with($key, $searchPrefix)) and
                str_ends_with($key, $searchSuffix))
            {
                $filteredKey = substr($key, strlen($searchPrefix), -strlen($searchSuffix));
                $customFieldHeaders[] = $filteredKey;
            }
        }

        if ($customFieldHeaders === []) {
            return false;
        }

        $areValidCustomField = (new Validator())->validateCustomFields($customFieldHeaders);

        if($areValidCustomField === true){
            foreach ($customFieldHeaders as $customField) {
                self::$plV2CustomFields[] = "custom_field[". $customField . "]";
            }
        }

        return $areValidCustomField;
    }
}
