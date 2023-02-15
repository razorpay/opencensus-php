<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const ECOD    = 'ecod';
    const INVOICE = 'invoice';
    const LINK    = 'link';
    const DCC_INV = 'dcc_inv';
    const DCC_CRN = 'dcc_crn';
    const OPGSP_INVOICE = 'opgsp_invoice';
    const OPGSP_AWB = 'opgsp_awb';

    protected static $paymentLinkTypes = [
        self::LINK,
        self::ECOD,
    ];

    protected static $dccEInvoiceTypes = [
        self::DCC_INV,
        self::DCC_CRN,
    ];

    protected static $OPGSPInvoiceTypes = [
        self::OPGSP_INVOICE,
        self::OPGSP_AWB,
    ];

    public static function isTypeValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    public static function isPaymentLinkType(string $type): bool
    {
        return ((self::isTypeValid($type)) and
                (in_array($type, self::$paymentLinkTypes, true) === true));
    }

    public static function getDCCEInvoiceTypes(): array
    {
        return self::$dccEInvoiceTypes;
    }

    public static function getOPGSPInvoiceTypes(): array
    {
        return self::$OPGSPInvoiceTypes;
    }

    public static function checkType(string $type)
    {
        if (self::isTypeValid($type) === false)
        {
            throw new BadRequestValidationFailureException(
                'Not a valid type: ' . $type);
        }
    }

    /**
     * Get invoice type's label, which will be used in public error descriptions.
     *
     * @param string $type
     *
     * @return string
     */
    public static function getLabel(string $type): string
    {
        self::checkType($type);

        switch ($type)
        {
            case self::LINK:
            case self::ECOD:
                return 'Payment Link';

            case self::INVOICE:
                return 'Invoice';

            case self::DCC_INV:
                return 'DCC tax invoice';
            case self::DCC_CRN:
                return 'DCC credit note';

            case self::OPGSP_INVOICE:
                return 'OPGSP Invoice';
            case self::OPGSP_AWB:
                return 'OPGSP AWB';
        }
    }
}
