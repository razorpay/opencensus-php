<?php

namespace RZP\Models\Pricing;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Action;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;


class Core extends Base\Core
{
    public function addPlanRule($input, $plan): Entity
    {
        $rule = $this->getRuleBuildAndAddToPlan($input, $plan);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    public function getRuleBuildAndAddToPlan($input, $plan): Entity
    {
        $rule = (new Pricing\Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $rule->getValidator()->matchPaymentRules($plan);

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        return $rule;
    }

    public function createPricingPlan($input): Plan
    {
        $pricing = $this->buildPricing($input);

        //setting Id as new Id because here everytime we will have a new workflow for create pricing plan.
        $this->app['workflow']
            ->setEntityAndId($pricing->getEntity(), $pricing->getId())
            ->handle((new \stdClass), $pricing);

        $this->repo->saveOrFail($pricing);

        return $this->createPlanFromPricing($pricing);
    }

    public function buildPricing($input): Entity
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $pricing = (new Pricing\Entity)->build($input);

        $pricing = $pricing->generateId();

        $pricing->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        return $pricing;
    }

    public function createMultiplePricing($input)
    {
        $plan_name = $input[Entity::PLAN_NAME];
        $input = $input['rules'];

        if (sizeof($input) === 0)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PRICING_BULK_CREATE,
                null,
                $input);
        }

        $input[0][Entity::PLAN_NAME] = $plan_name;

        $this->repo->transactionOnLiveAndTest(function() use ($input)
        {
            $rules = [];

            $rule = $this->buildPricing($input[0]);

            $plan = $this->createPlanFromPricing($rule);

            array_push($rules, $rule);

            array_shift($input);

            foreach ($input as $value)
            {
                $rule = $this->getRuleBuildAndAddToPlan($value, $plan);

                array_push($rules, $rule);
            }
            // $rules to be injected in workflow here

            foreach ($rules as $rule)
            {
                $this->repo->saveOrFail($rule);
            }
        });
    }

    public function createPlanFromPricing($pricing): Plan
    {
        $plan = new Plan(array($pricing));

        return $plan;
    }
}
