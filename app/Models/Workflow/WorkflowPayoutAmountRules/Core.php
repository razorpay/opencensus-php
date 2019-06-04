<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Models\Payment;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\Method;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Processor\Netbanking;

class Core extends Base\Core
{

    public function getWorkflowRulesForMerchant(string $merchantId): array
    {
       $this->repo->fetchWorkflowRulesForMerchant($merchantId);
    }
}
