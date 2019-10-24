<?php

namespace RZP\Models\Merchant\Account;

use RZP\Exception\BadRequestValidationFailureException;

class DocumentType
{
    const COMPANY_PAN = 'company_pan';
    const OWNER_PAN   = 'owner_pan';
    const GSTIN       = 'gstin';

    /**
     * @param string $type
     *
     * @return bool
     */
    public static function isValid(string $type): bool
    {
        $key = __CLASS__ . '::' . strtoupper($type);

        return ((defined($key) === true) and (constant($key) === $type));
    }

    /**
     * @param $type
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $type)
    {
        if (self::isValid($type) === false)
        {
            throw new BadRequestValidationFailureException('Invalid document type: ' . $type);
        }
    }
}
