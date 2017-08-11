<?php

namespace RZP\Models\Pricing;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Pricing;
use RZP\Trace\TraceCode;
use RZP\Models\Admin\Action;

class Core extends Base\Core
{
    public function addPlanRule($input, $plan)
    {
        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule = $rule->generateId();

        $rule->getValidator()->matchPaymentRules($plan);

        $rule->setAuditAction(Action::CREATE_PRICING_PLAN_RULE);

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    public function createPricingPlan($input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $pricing = (new Pricing\Entity)->build($input);

        $pricing = $pricing->generateId();

        //setting Id as new Id because here everytime we will have a new workflow for create pricing plan.
        $this->app['workflow']
            ->setEntityAndId($pricing->getEntity(), $pricing->getId())
            ->handle((new \stdClass), $pricing);

        $pricing->setAuditAction(Action::CREATE_MERCHANT_PRICING_PLAN);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $this->repo->saveOrFail($pricing);

        $plan = new Plan(array($pricing));

        return $plan;
    }
}
