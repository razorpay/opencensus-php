<?php

namespace RZP\Models\Payout\Batch;

use RZP\Base;
use RZP\Models\Payout;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\FundAccount\Entity as FaEntity;

class Validator extends Base\Validator
{
    const MAX_BATCH_PAYOUTS_LIMIT = 100;

    const FUND_ACCOUNT_PAYOUT = 'fund_account_payout';

    const FUND_ACCOUNT_PAYOUT_COMPOSITE = 'fund_account_payout_composite';

    protected static $createRules = [
        Constants::REFERENCE_ID => 'sometimes|nullable|string|max:40',
        Constants::PAYOUTS      => 'required|array|custom',
    ];

    // This copies from Payout\Validator
    protected static $fundAccountPayoutRules = [
        Payout\Entity::ACCOUNT_NUMBER       => 'required|filled|string',
        Payout\Entity::PURPOSE              => 'required|filled|string|max:30|alpha_dash_space',
        Payout\Entity::AMOUNT               => 'required|integer|min:100',
        Payout\Entity::CURRENCY             => 'required|size:3|in:INR',
        Payout\Entity::NOTES                => 'sometimes|notes',
        Payout\Entity::FUND_ACCOUNT_ID      => 'required|public_id',
        Payout\Entity::MODE                 => 'required|string|custom',
        Payout\Entity::REFERENCE_ID         => 'sometimes|nullable|string|max:40',
        Payout\Entity::NARRATION            => 'sometimes|nullable|string|max:30|alpha_space_num',
        Payout\Entity::QUEUE_IF_LOW_BALANCE => 'sometimes|filled|boolean',
    ];

    // This copies from Payout\Validator
    protected static $fundAccountPayoutCompositeRules = [
        Payout\Entity::ACCOUNT_NUMBER                              => 'required|filled|string',
        Payout\Entity::PURPOSE                                     => 'required|filled|string|max:30|alpha_dash_space',
        Payout\Entity::AMOUNT                                      => 'required|integer|min:100',
        Payout\Entity::CURRENCY                                    => 'required|size:3|in:INR',
        Payout\Entity::NOTES                                       => 'sometimes|notes',
        Payout\Entity::MODE                                        => 'required|string|custom',
        Payout\Entity::REFERENCE_ID                                => 'sometimes|nullable|string|max:40',
        Payout\Entity::NARRATION                                   => 'sometimes|nullable|string|max:30|alpha_space_num',
        Payout\Entity::QUEUE_IF_LOW_BALANCE                        => 'sometimes|filled|boolean',
        Payout\Entity::SKIP_WORKFLOW                               => 'filled|boolean',
        Payout\Entity::FUND_ACCOUNT                                => 'required|filled|array|custom',
        Payout\Entity::FUND_ACCOUNT . "." . FaEntity::ACCOUNT_TYPE => 'required|filled|in:bank_account,vpa,wallet',
        Payout\Entity::FUND_ACCOUNT . "." . Payout\Entity::CONTACT => 'required|filled|array',
    ];

    protected function validatePayouts($attribute, $input)
    {
        foreach($input as $payout)
        {
            $isCompositePayout = false;

            if (isset($payout[Payout\Entity::FUND_ACCOUNT]) === true)
            {
                $isCompositePayout = true;
            }

            $validationRule =
                ($isCompositePayout === true) ? self::FUND_ACCOUNT_PAYOUT_COMPOSITE : self::FUND_ACCOUNT_PAYOUT;

            $this->setStrictFalse()->validateInput($validationRule, $payout);
        }
    }

    protected function validateFundAccount($attribute, $value)
    {
        if (isset($value[Payout\Entity::CONTACT_ID]) === true)
        {
            throw new ExtraFieldsException(
                Payout\Entity::FUND_ACCOUNT . '.' . Payout\Entity::CONTACT_ID
            );
        }
    }

    protected function validateMode($attribute, $value)
    {
        Payout\Mode::validateMode($value);
    }
}
