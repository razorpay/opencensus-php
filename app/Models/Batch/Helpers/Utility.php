<?php

namespace RZP\Models\Batch\Helpers;

use Carbon\Carbon;

use InvalidArgumentException;

use RZP\Constants\Timezone;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Utility: Contains general utility functions to be used across batch types
 */
class Utility
{
    /**
     * Formats allowed for human readable date time values in files
     * @var array
     */
    public static $allowedDateFormats = [
        // Todo:
        // - Check if should allow default excel format when selector is used & other common softwares default formats
        // - Check if both / and - separator is needed to be supported
        // 'Y-m-d',        // Excel date selector puts value in this format
        'd/m/Y h:i:s',
        'd/m/Y h:i',
        'd/m/Y',
        'd-m-Y h:i:s',
        'd-m-Y h:i',
        'd-m-Y',
    ];

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
