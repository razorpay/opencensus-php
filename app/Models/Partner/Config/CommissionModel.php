<?php

namespace RZP\Models\Partner\Config;

use RZP\Exception\BadRequestValidationFailureException;

class CommissionModel
{
    //
    // this class represents types of commission models in partner config
    // 'commission' represents a model where commission will be borne by submerchant and given to the partner
    // 'subvention' represents a model where rzp fees will be (partially or fully) borne by the partner
    //
    const SUBVENTION  = 'subvention';
    const COMMISSION  = 'commission';

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
            throw new BadRequestValidationFailureException('Invalid commission model: ' . $type);
        }
    }
}
