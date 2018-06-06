<?php

namespace RZP\Models\Base;

use Carbon\Carbon;
use InvalidArgumentException;

use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

class Utility
{
    /**
     * Formats allowed for human readable date time values in files
     * @var array
     */
    public static $allowedDateFormats = [
        'd-m-Y H:i:s',
        'd-m-Y H:i',
        'd-m-Y',
    ];

    public static function isUpdatedAndroidSdk($input)
    {
        if ((isset($input['_'])) and
            (isset($input['_']['platform'])) and
            ($input['_']['platform'] === 'android') and
            (isset($input['_']['library'])) and
            ($input['_']['library'] === 'checkoutjs') and
            (isset($input['_']['version'])) and
            (version_compare($input['_']['version'], '1.0.0') >= 0))
        {
            return true;
        }

        return false;
    }

    /**
     * Parse a value as epoch or fails
     * @param  int|string|null $value
     * @return int|null
     * @throws BadRequestValidationFailureException
     */
    public static function parseAsEpoch($value)
    {
        if (empty($value) === true)
        {
            return $value;
        }

        if (is_numeric($value) === true)
        {
            return (int) $value;
        }

        return self::createCarbonFromAllowedFormats($value)->getTimestamp();
    }

    /**
     * Creates Carbon instance for given value against allowed formats
     * @param  string $value
     * @return Carbon
     * @throws BadRequestValidationFailureException
     */
    public static function createCarbonFromAllowedFormats($value): Carbon
    {
        foreach (self::$allowedDateFormats as $allowedDateFormat)
        {
            try
            {
                return Carbon::createFromFormat($allowedDateFormat, $value, Timezone::IST);
            }
            // Above operation either returns valid Carbon instance else throws InvalidArgumentException (i.e. failed)
            catch (InvalidArgumentException $e)
            {
            }
        }

        throw new BadRequestValidationFailureException(
            "Date/time value is not in correct format: {$value}",
            null,
            compact('value'));
    }
}
