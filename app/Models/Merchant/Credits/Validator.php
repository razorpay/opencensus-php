<?php

namespace RZP\Models\Merchant\Credits;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Credits;

class Validator extends Base\Validator
{
    const MAX_AMOUNT_CREDITS = 500000000;
    const MAX_FEE_CREDITS    = 500000000;
    const MIN_CREDITS        = -1000000;
    const MAX_REFUND_CREDITS = 500000000;

    protected static $createRules = [
        Entity::CAMPAIGN     => 'required|alpha_dash|max:255',
        # Value is in paise
        Entity::VALUE        => 'required|integer',
        Entity::TYPE         => 'sometimes|filled|string|max:20|in:amount,fee,refund',
        Entity::EXPIRED_AT   => 'sometimes|integer',
        Entity::PROMOTION_ID => 'sometimes|alpha_num|max:14',
    ];

    protected static $editRules = [
        Entity::VALUE        => 'required|integer',
    ];

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
        $this->validateBalanceCredits($creditsDifference, $currentCreditsBalance, $type);
    }

    public function validateBalanceCredits($credits, $merchantCredits, $type)
    {
        if (($credits < 0) and
            (abs($credits) > $merchantCredits))
        {
            $msg = 'Cannot update or add %d %s-credits. Merchant has only %d %s-credits.';

            $msg = sprintf($msg, $credits/100, $type,
                $merchantCredits/100, $type);

            throw new Exception\BadRequestValidationFailureException($msg);
        }
    }

    protected function validateCreditsBoundaryLimits($type, $creditsValue)
    {
        $maxCreditsValue = $this->getMaxCreditsForType($type);

        if ($creditsValue < self::MIN_CREDITS)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign credits less than '. self::MIN_CREDITS);
        }

        // removing refund credit upper limit because
        // of the Covid-19 situation which increased refunds.
        // and merchants are issuing huge amounts of refunds
        if (($type !== Credits\Type::REFUND) and
             ($creditsValue > $maxCreditsValue))
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

            case Credits\Type::REFUND:
                return self::MAX_REFUND_CREDITS;

            default:
                return self::MAX_AMOUNT_CREDITS;
        }
    }

    protected function getMerchantCredits(Credits\Entity $creditsLog)
    {
        $balance = $creditsLog->merchant->primaryBalance;
        $type = $creditsLog->getType();

        switch ($type)
        {
            case Credits\Type::AMOUNT:
                return $balance->getAmountCredits();

            case Credits\Type::FEE:
                return $balance->getFeeCredits();

            case Credits\Type::REFUND:
                return $balance->getRefundCredits();

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
                ($balance->getAmountCredits() > 0))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Cannot assign fee credits as amount credits are already present');
        }
    }

    public function validateCreditsValue($input)
    {
        // removing laravel validator for field value of type refund
        // because of the Covid-19 situation which increased refunds.
        // and merchants are issuing huge amounts of refunds
        if ((isset($input['type'])) and
             ($input['type'] === 'refund') and
              (($input['value'] >= -100000000)))
        {
            return;
        }

        elseif (($input['value'] < -100000000) or
                 ($input['value'] > 500000000))
        {
            throw new Exception\BadRequestException(
                'BAD_REQUEST_ERROR',
                'value',
                null,
                'The value must be between -100000000 and 500000000.');
        }
    }
}
