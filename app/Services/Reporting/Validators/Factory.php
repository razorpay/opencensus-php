<?php


namespace RZP\Services\Reporting\Validators;

use RZP\Services\Reporting;

/**
 * Class Factory
 * A factory class for getting a Validator instance based on different criteria
 *
 * @package RZP\Services\Reporting\Validators
 */
class Factory
{
    /**
     * Return a Validator instance based on the Report Type
     *
     * @param string $reportType type of Report
     * @param array $input request payload to be validated
     * @return BaseValidator|null
     */
    public static function getReportTypeBasedValidator(string $reportType, array $input): ? BaseValidator
    {
        if ($reportType === Reporting::MERCHANT or $reportType == Reporting::PARTNER)
        {
            return new PGReportValidator($input);
        }
        else if ($reportType === Reporting::RAZORPAYX)
        {
            return new RXReportValidator($input);
        }

        return null;
    }
}
