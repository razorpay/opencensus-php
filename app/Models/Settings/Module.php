<?php

namespace RZP\Models\Settings;

use RZP\Exception\BadRequestValidationFailureException;

/**
 * Class Module
 *
 * Defines modules that settings can be defined for,
 * and related validations
 *
 * @package RZP\Models\Settings
 */
class Module
{
    const USER              = 'user';
    const BATCH             = 'batch';
    const PARTNER           = 'partner';
    const ONBOARDING        = 'onboarding';
    const OPENWALLET        = 'openwallet';
    const SUBSCRIPTIONS     = 'subscriptions';
    const PAYMENT_LINK      = 'payment_link';
    const PAYMENT_PAGE_ITEM = 'payment_page_item';
    const PAYOUT_PURPOSE    = 'payout_purpose';
    const CONTACT_TYPE      = 'contact_type';

    /**
     * @param string $module
     *
     * @return bool
     */
    public static function isValid(string $module): bool
    {
        $const = __CLASS__ . '::' . strtoupper($module);

        return ((defined($const) === true) and (constant($const) === $module));
    }

    /**
     * @param string $module
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $module)
    {
        if (self::isValid($module) === false)
        {
            throw new BadRequestValidationFailureException(
                'The module specified is invalid',
                'module',
                ['module' => $module]);
        }
    }
}
