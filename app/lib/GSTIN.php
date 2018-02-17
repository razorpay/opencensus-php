<?php

namespace Lib;

use RZP\Constants\IndianStates as State;
use RZP\Exception\BadRequestValidationFailureException;

class GSTIN
{
    /**
     * Details here: https://cleartax.in/s/know-your-gstin#struct
     *
     * First 2 digits matched are captured
     */
    const GSTIN_REGEX = '/^(\d{2})[A-Z]{5}\d{4}[A-Z]{1}\d[Z]{1}[A-Z\d]{1}$/';

    protected static $stateCodes = [
        '01' => State::JK,
        '02' => State::HP,
        '03' => State::PB,
        '04' => State::CH,
        '05' => State::UT,
        '06' => State::HA,
        '07' => State::DL,
        '08' => State::RJ,
        '09' => State::UP,
        '10' => State::BI,
        '11' => State::SK,
        '12' => State::AR,
        '13' => State::NA,
        '14' => State::MA,
        '15' => State::MI,
        '16' => State::TR,
        '17' => State::ME,
        '19' => State::WB,
        '20' => State::JH,
        '21' => State::OR,
        '22' => State::CT,
        '23' => State::MP,
        '24' => State::GJ,
        '25' => State::DD,
        '26' => State::DN,
        '27' => State::MH,
        '28' => State::AP,
        '29' => State::KA,
        '30' => State::GO,
        '31' => State::LD,
        '32' => State::KE,
        '33' => State::TN,
        '34' => State::PO,
        '35' => State::AN,
        '36' => State::TG,
        '37' => State::AD,
    ];

    public static function isValid(string $gstin): bool
    {
        $valid = preg_match(self::GSTIN_REGEX, $gstin, $matches);

        $stateCode = $matches[1] ?? '00';

        //
        // Regex is valid and
        // state code is valid as per list in `$stateCodes`
        //
        return (($valid === 1) and
                (array_key_exists($stateCode, static::$stateCodes) === true))l
    }

    /**
     * Validate GST Number
     *
     * @param string $gstin
     *
     * @throws BadRequestValidationFailureException
     */
    public static function validate(string $gstin)
    {
        if (static::isValid($gstin) === false)
        {
            throw new BadRequestValidationFailureException(
                'The GSTIN is invalid',
                null,
                ['gstin' => $gstin]);
        }
    }
}
