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

        $this->repo->saveOrFail($rule);

        return $rule;
    }

    public function createPricingPlan($input)
    {
        $this->trace->info(
            TraceCode::PRICING_PLAN_CREATE_ATTEMPT,
            $input);

        $pricing = (new Pricing\Entity)->build($input);

        $plan = $this->repo->pricing->getPricingPlanByName($input[Entity::PLAN_NAME]);

        Pricing\Validator::validatePlanCountZero($plan);

        $this->repo->saveOrFail($pricing);

        return $pricing;
    }
}
