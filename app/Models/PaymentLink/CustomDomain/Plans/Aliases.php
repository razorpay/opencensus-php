<?php

namespace RZP\Models\PaymentLink\CustomDomain\Plans;

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Schedule;

class Aliases
{
    /**
     * * Plan Amount to be appended at the end of aliases
     * for the analytics purpose
     */
    const MONTHLY_ALIAS      = 'cds_pricing_monthly_500';

    const QUARTERLY_ALIAS    = 'cds_pricing_quarterly_1200';

    const BIYEARLY_ALIAS     = 'cds_pricing_biyearly_2100';

    const ALIASES = [
        self::MONTHLY_ALIAS,
        self::QUARTERLY_ALIAS,
        self::BIYEARLY_ALIAS,
        ];

    public static function checkDuplicateAlias(string $alias)
    {
        $plans = (new Schedule\Repository)->fetchSchedulesByType(Schedule\Type::CDS_PRICING);

        foreach ($plans as $plan)
        {
            if($plan->getName() === $alias)
            {
                throw new BadRequestValidationFailureException("Alias is not unique");
            }
        }
    }
}
