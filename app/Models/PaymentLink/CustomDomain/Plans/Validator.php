<?php

namespace RZP\Models\PaymentLink\CustomDomain\Plans;

use RZP\Base;
use RZP\Models\Schedule;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Constants::ALIAS      => 'required|string|custom',
        Constants::PERIOD     => "required|custom",
        Constants::INTERVAL   => 'required|integer|min:1',
    ];

    protected static $createManyRules = [
        Constants::PLANS     => 'required|array|min:1'
    ];

    protected static $deleteRules = [
        Constants::PLAN_IDS => 'required|array|min:1'
    ];

    protected static $updatePlanForMerchantsRules = [
        Constants::OLD_PLAN_ID  => 'required|string',
        Constants::NEW_PLAN_ID  => 'required|string',
    ];

    protected static function validateAlias($attribute, string $value)
    {
        // duplicate alias check.

        Aliases::checkDuplicateAlias($value);

    }

    protected static function validatePeriod($attribute,$value)
    {
        Schedule\Period::validatePeriod($value);
    }

    public function validatePlanCreation(string $planAlias, Merchant\Entity $merchant)
    {
        $this->validatePlanValid($planAlias);
    }

    public function validatePlanExistForMerchant(Merchant\Entity $merchant)
    {
        $plan = (new Schedule\Task\Repository())->fetchActiveByMerchantWithTrashed(
            $merchant,
            Schedule\Type::CDS_PRICING,
            true
        );

        if($plan !== null)
        {
            throw new BadRequestValidationFailureException(
                 'Plan already exist for this merchant.'
            );
        }
    }

    public function validatePlanValid($merchantPlanId)
    {
        $plan = (new Schedule\Repository())->findOrFailPublic($merchantPlanId);

        if($plan === null)
        {
            throw new BadRequestValidationFailureException(
                $merchantPlanId . ' is not a valid plan.'
            );
        }

        // checking if plan exists in Plans.php as well
        if(array_key_exists($plan->getName(), Plans::PLAN) === false)
        {
            throw new BadRequestValidationFailureException(
                $merchantPlanId . ' is not a valid plan.'
            );
        }
    }

    public function validatePlanIdPresent(array $input)
    {
        if(array_key_exists(Constants::PLAN_ID, $input) === false)
        {
            throw new BadRequestValidationFailureException(
                Constants::PLAN_ID . ' is required'
            );
        }
    }

    public function validatePlansUpdateForMerchant(string $oldPlanId, string $newPlanId)
    {
        $this->validatePlanIdIsValid($newPlanId);

        $this->validatePlanIdIsValid($oldPlanId);
    }

    public function validatePlanIdIsValid(string $planId)
    {
        try
        {
            (new Schedule\Repository())->fetchScheduleById($planId);
        }
        catch(\Exception $e)
        {
            throw new BadRequestValidationFailureException($planId . 'is not a valid plan id.', [
                'exception' => $e
            ]);
        }
    }
}
