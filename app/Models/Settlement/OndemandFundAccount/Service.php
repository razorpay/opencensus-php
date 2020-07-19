<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\SettlementOndemand\CreateOndemandFundAccounts;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandFundAccount;

use Razorpay\Trace\Logger as Trace;

class Service extends Base\Service
{
    public function createFundAccount()
    {
        CreateOndemandFundAccounts::dispatch($this->mode);
    
        $response = [
            'response'  => 'create fund account job dispatched',
        ];

        return $response;
    }

    public function dispatchSettlementOndemandFundAccountUpdateJob($merchantId)
    {
        CreateSettlementOndemandFundAccount::dispatch($this->mode, $merchantId);
    }

    public function addOndemandFundAccountForMerchant($merchantId)
    {
        $this->core()->addOndemandFundAccountForMerchant($merchantId);
    }
}
