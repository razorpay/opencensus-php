<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
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
        /** @var Entity $fundAccount */
        $fundAccount = (new Repository)->findByMerchantId($merchantId);

        if (empty($fundAccount) === false)
        {
            $fundAccount->setFundAccountIdNull();

            $this->repo->saveOrFail($fundAccount);
        }

        CreateSettlementOndemandFundAccount::dispatch(Mode::TEST, $merchantId);

        CreateSettlementOndemandFundAccount::dispatch(Mode::LIVE, $merchantId);
    }

    public function dispatchSettlementOndemandFundAccountCreateJob($merchantId)
    {
        CreateSettlementOndemandFundAccount::dispatch(Mode::TEST, $merchantId);

        CreateSettlementOndemandFundAccount::dispatch(Mode::LIVE, $merchantId);
    }

    public function addOndemandFundAccountForMerchant($merchantId)
    {
        return $this->core()->addOndemandFundAccountForMerchant($merchantId);
    }
}
