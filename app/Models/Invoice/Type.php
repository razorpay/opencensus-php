<?php

namespace RZP\Models\Invoice;

use RZP\Exception\BadRequestValidationFailureException;

class Type
{
    const ECOD    = 'ecod';
    const INVOICE = 'invoice';
    const LINK    = 'link';

    public static function isTypeValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
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
        }
    }
}
