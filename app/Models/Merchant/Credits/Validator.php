<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Merchant\Credits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const MAX_CREDITS     = 100000000;
    const MAX_FEE_CREDITS = 50000000;
    const MIN_CREDITS     = 100;

    protected static $createRules = array(
        Entity::CAMPAIGN => 'required|alpha_dash|max:255',
        Entity::VALUE    => 'required|integer|min:1|max:'.self::MAX_CREDITS,
        Entity::TYPE     => 'required|alpha_dash|max:20',
    );

    protected static $editRules = array(
        Entity::VALUE    => 'sometimes|integer',
    );

    public static function validateNewCreditsValue($creditsLog, $credits)
    {
        $type = $creditsLog->getType();

        $MAX_CREDITS = self::getMaxCreditsForType($type);

        if ($credits < self::MIN_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits less than one rupee');
        }

        if ($credits > $MAX_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot Assign credits more than '. $MAX_CREDITS);
        }

        $merchantBalance = self::getMerchantBalance($creditsLog);

        $creditsDifference = $credits - $creditsLog->getValue();

        if (($creditsDifference < 0) and
            (abs($creditsDifference) > $merchantBalance))
        {
            $msg = 'Cannot change %s from %d to %d. Merchant Total %s Remaining: %d';

            $msg = sprintf($msg, $type, $creditsLog->getValue(),
                $credits, $type, $merchantBalance);

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

    protected static function getMaxCreditsForType(string $type)
    {
        switch($type)
        {
            case Credits\Type::AMOUNT:
                return self::MAX_CREDITS;
            case Credits\Type::FEE:
                return self::MAX_FEE_CREDITS;

            default:
                return self::MAX_CREDITS;
        }
    }

    protected static function getMerchantBalance(Credits\Entity $creditsLog)
    {
        $balance = $creditsLog->merchant->balance;
        $type = $creditsLog->getType();

        switch($type)
        {
            case Credits\Type::AMOUNT:
                return $balance->getCredits();

            case Credits\Type::FEE:
                return $balance->getFeeCredits();

            default:
                return null;
        }
    }
}
