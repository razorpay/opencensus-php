<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const MAX_CREDITS = 1000000;
    const MIN_CREDITS = 100;

    protected static $createRules = array(
        Entity::CAMPAIGN                => 'required|alpha_dash|max:255',
        Entity::VALUE                   => 'required|integer|min:1|max:'.self::MAX_CREDITS,
    );

    protected static $editRules = array(
        Entity::VALUE                   => 'sometimes|integer',
    );

    public static function validateNewCreditsValue($creditsLog, $credits)
    {
        if ($credits < self::MIN_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits less than one rupee');
        }

        if ($credits > self::MAX_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot Assign credits more than '. self::MAX_CREDITS);
        }

        $merchantBalance = $creditsLog->merchant->balance->getCredits();
        $creditsDifference = $credits - $creditsLog->getValue();

        if (($creditsDifference < 0) and
            (abs($creditsDifference) > $merchantBalance))
        {
            $msg = 'Cannot change credits from %d to %d. Merchant Total Credits Remaining: %d';

            $msg = sprintf($msg, $creditsLog->getValue(), $credits, $merchantBalance);

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }
}
