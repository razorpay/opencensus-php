<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Models\Base;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Permission;
use RZP\Models\Workflow\Action;
use RZP\Models\Workflow\Step;

class Service extends Base\Service
{
    public function getWorkflowRules()
    {
        $merchantID = $this->merchant->getMerchantId();

        $amountRules = $this->core->getWorkflowRulesForMerchant($merchantID);

        return $amountRules->toArrayPublic();
    }


}
