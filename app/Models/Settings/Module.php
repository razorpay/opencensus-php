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
    const OPENWALLET    = 'openwallet';
    const ONBOARDING    = 'onboarding';
    const SUBSCRIPTIONS = 'subscriptions';

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
