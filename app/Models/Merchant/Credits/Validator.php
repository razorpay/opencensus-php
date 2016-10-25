<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_CREDITS = 100000000;
    const MAX_FEE_CREDITS    = 50000000;
    const MIN_CREDITS        = 100;

    protected static $createRules = array(
        Entity::CAMPAIGN => 'required|alpha_dash|max:255',
        Entity::VALUE    => 'required|integer|min:1|max:'.self::MAX_AMOUNT_CREDITS,
        Entity::TYPE     => 'required|alpha_dash|max:20',
    );

    protected static $editRules = array(
        Entity::VALUE    => 'sometimes|integer',
    );

    /**
     * Validates credits value when credits is being edited.
     */
    public function validateNewCreditsValue($creditsLog, $creditsValue)
    {
        $type = $creditsLog->getType();

        // Validates min and max boundary for credits value
        $this->validateCreditsBoundaryLimits($type, $creditsValue);

        $currentCreditsBalance = $this->getMerchantCredits($creditsLog);

        $creditsDifference = $creditsValue - $creditsLog->getValue();

        //
        // Validate that merchant credits balance does not go negative
        // after the update
        //
        if (($creditsDifference < 0) and
            (abs($creditsDifference) > $currentCreditsBalance))
        {
            $msg = 'Cannot change %s from %d to %d. Merchant Total %s Remaining: %d';

            $msg = sprintf($msg, $type, $creditsLog->getValue(),
                $credits, $type, $currentCreditsBalance);

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

    protected function validateCreditsBoundaryLimits($type, $creditsValue)
    {
        $maxCreditsValue = $this->getMaxCreditsForType($type);

        if ($creditsValue < self::MIN_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits less than one rupee');
        }

        if ($creditsValue > $maxCreditsValue)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits more than '. $maxCreditsValue);
        }
    }

    protected function getMaxCreditsForType(string $type)
    {
        switch ($type)
        {
            case Credits\Type::AMOUNT:
                return self::MAX_AMOUNT_CREDITS;

            case Credits\Type::FEE:
                return self::MAX_FEE_CREDITS;

            default:
                return self::MAX_AMOUNT_CREDITS;
        }
    }

    protected function getMerchantCredits(Credits\Entity $creditsLog)
    {
        $balance = $creditsLog->merchant->balance;
        $type = $creditsLog->getType();

        switch ($type)
        {
            case Credits\Type::AMOUNT:
                return $balance->getAmountCredits();

            case Credits\Type::FEE:
                return $balance->getFeeCredits();

            default:
                return null;
        }
    }

    public function validateCreditsType(Merchant\Balance\Entity $balance, $type)
    {
        if (($type === Type::AMOUNT) and
            ($balance->getFeeCredits() > 0))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign amount credits as fee credits are already present');
        }
        else if(($type === Type::FEE) and
                ($balance->getCredits() > 0))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign fee credits as amount credits are already present');
        }
    }
}
