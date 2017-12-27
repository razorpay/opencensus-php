<?php

namespace RZP\Models\Batch;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
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
    // Marketplace Linked Account Headers
    //
    const BUSINESS_NAME       = 'business_name';
    const BANK_ACCOUNT_TYPE   = 'bank_account_type';
    const BANK_ACCOUNT_NAME   = 'bank_account_name';
    const BANK_BRANCH_IFSC    = 'bank_branch_ifsc';
    const BANK_ACCOUNT_NUMBER = 'bank_account_number';
    const REFERENCE_ID        = 'reference_id';
    const ACCOUNT_ID          = 'account_id';

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
    // HDFC Emandate Register Response File Headers
    //
    const HDFC_EM_REGISTER_CLIENT_NAME                      = HdfcEMRegisterHeadings::CLIENT_NAME;
    const HDFC_EM_REGISTER_MERCHANT_UNIQUE_REFERENCE_NO     = HdfcEMRegisterHeadings::MERCHANT_UNIQUE_REFERENCE_NO;
    const HDFC_EM_REGISTER_CUSTOMER_NAME                    = HdfcEMRegisterHeadings::CUSTOMER_NAME ;
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
        return self::HEADER_MAP[$type][self::INPUT];
    }

    public static function getOutputHeadersForType(string $type): array
    {
        return self::HEADER_MAP[$type][self::OUTPUT];
    }
}
