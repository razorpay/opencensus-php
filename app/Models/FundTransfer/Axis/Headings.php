<?php

namespace RZP\Models\FundTransfer\Axis;

class Headings
{
    const RECORD_IDENTIFIER     = 'Record Identifier';
    const REFERENCE_NUMBER      = 'Reference Number';
    const DEBIT_ACCOUNT_NUMBER  = 'Debit Account Number';
    const AMOUNT                = 'Transaction Amount';
    const NO_OF_CREDIT_RECORDS  = 'No of Credit Records';
    const EXECUTION_DATE        = 'Execution Date';

    const FILE_LEVEL_REFERENCE  = 'User Credit/Debit Reference (File Level)';
    const BENEFICIARY_CODE      = 'Beneficiary Code';
    const TRANSACTION_AMOUNT    = 'Transaction Amount';
    const SETTLEMENT_DATE       = 'Settlement Date';
    const RBI_SEQUENCE_NUMBER   = 'RBI Sequence Number';
    const STATUS                = 'Status';
    const RETURN_REASON         = 'Return Reason';
    const ADDITIONAL_INFO1      = 'Additional Info1';
    const ADDITIONAL_INFO2      = 'Additional Info2';
    const ADDITIONAL_INFO3      = 'Additional Info3';
    const ADDITIONAL_INFO4      = 'Additional Info4';

    public static function getRequestFileHeadings(): array
    {
        return [
            self::RECORD_IDENTIFIER,
            self::DEBIT_ACCOUNT_NUMBER,
            self::BENEFICIARY_CODE,
            self::EXECUTION_DATE,
            self::AMOUNT,
            self::ADDITIONAL_INFO1,
            self::ADDITIONAL_INFO2,
            self::REFERENCE_NUMBER,
            self::ADDITIONAL_INFO3,
        ];
    }

    public static function getResponseFileHeadings(): array
    {
        return [
            self::FILE_LEVEL_REFERENCE,
            self::BENEFICIARY_CODE,
            self::TRANSACTION_AMOUNT,
            self::SETTLEMENT_DATE,
            self::RBI_SEQUENCE_NUMBER,
            self::STATUS,
            self::RETURN_REASON
        ];
    }
}
