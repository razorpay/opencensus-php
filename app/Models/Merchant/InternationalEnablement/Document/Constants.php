<?php

namespace RZP\Models\Merchant\InternationalEnablement\Document;

class Constants
{
    const FIRC                                      = 'firc';
    const IE_CODE                                   = 'ie_code';
    const INVOICES                                  = 'invoices';
    const BANK_STATEMENT_INWARD_REMITTANCE          = 'bank_statement_inward_remittance';
    const CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD = 'current_payment_partner_settlement_record';

    const OTHERS = 'others';

    const MAX_DOCUMENTS_PER_TYPE = 3;

    const MANDATORY_DOCUMENT_TYPES = [
        self::BANK_STATEMENT_INWARD_REMITTANCE,
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD,
    ];

    const DOCUMENT_TYPES = [
        self::FIRC,
        self::IE_CODE,
        self::INVOICES,
        self::BANK_STATEMENT_INWARD_REMITTANCE,
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD,
        self::OTHERS,
    ];

    const DOCUMENT_TYPE_VALIDATOR_CSV =
        self::FIRC . ',' .
        self::IE_CODE . ',' .
        self::INVOICES . ',' .
        self::BANK_STATEMENT_INWARD_REMITTANCE . ',' .
        self::CURRENT_PAYMENT_PARTNER_SETTLEMENT_RECORD . ',' .
        self::OTHERS;

    public static function isMandatoryDocumentType(string $documentType): bool
    {
        return (in_array($documentType, self::MANDATORY_DOCUMENT_TYPES) === true);
    }

    public static function getValidDocumentTypesCSV(): string
    {
        return implode(',', self::DOCUMENT_TYPES);
    }
}
