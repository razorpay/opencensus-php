<?php

namespace RZP\Models\Pricing;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Pricing;

class Core extends Base\Core
{
    public function addPlanRule($input, $plan)
    {
        $rule = (new Entity)->addPlanRule($input, $plan);

        $rule->getValidator()->matchPaymentRules($plan);
        $rule->generateId();

        (new Pricing\Repository)->saveOrFail($rule);

        return $rule;
    }

    public function buildPricingPlan($input)
    {
        $pricing = (new Pricing\Entity)->build($input);

        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $pricing->generateId();

        (new Pricing\Repository)->saveOrFail($pricing);

        return $pricing;
    }
}
