<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{

    const MAX_CREDITS = 1000000;
    const MIN_CREDITS = 1;

    protected static $createRules = array(
        Entity::CAMPAIGN                => 'required|alpha_dash|max:255',
        Entity::VALUE                   => 'required|integer',
        Entity::NOTES                   => 'sometimes|notes',
    );

    protected static $editRules = array(
        Entity::VALUE                   => 'sometimes|integer',
    );

    public static function validateNewCreditLog($campaign, $merchant)
    {
        // Check if the log already exists, API is meant to use for creation only.
        $creditsLogExists = (new Credits\Repository)->creditsLogExists(
            $campaign, $merchant);

        if ($creditsLogExists)
        {
            throw new Exception\BadRequestValidationFailureException(
                'The record already exists for given campaign and merchant');
        }
    }

    public static function validateNewCreditsValue($creditsLog, $credits)
    {
        if($credits < self::MIN_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits less than zero');
        }
        if($credits > self::MAX_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot Assign credits more than '.(string)self::MAX_CREDITS);
        }
        $merchantBalance = $creditsLog->merchant->balance->getCredits();
        $creditsDifference = $credits - $creditsLog->getValue();
        if($creditsDifference < 0 && abs($creditsDifference) > $merchantBalance)
        {
            $msg = 'Cannot change credits from %d to %d. Merchant Total Credits Remaining: %d';
            throw new Exception\BadRequestValidationFailureException(sprintf(
                $msg, $creditsLog->getValue(), $credits, $merchantBalance));
        }
    }
}
