<?php

namespace RZP\Models\Settlement\OndemandFundAccount;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Jobs\SettlementOndemand\CreateOndemandFundAccounts;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandFundAccount;
use RZP\Models\Settlement\OndemandFundAccount;

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

    public function dispatchSettlementOndemandFundAccountUpdateJob($merchantId, $bankAccount = null)
    {
        $this->core()->invalidateFundAccount($merchantId);

        if ($this->core()->isFundAccountMigrated('write') === true)
        {
            $this->app['capital_early_settlements']->invalidateAndCreateFundAccount($merchantId, false, false, $bankAccount->toArray());
            return;
        }

        CreateSettlementOndemandFundAccount::dispatch(Mode::TEST, $merchantId, $bankAccount);

        CreateSettlementOndemandFundAccount::dispatch(Mode::LIVE, $merchantId, $bankAccount);
    }

    public function addOndemandFundAccountForMerchant($merchantId, $bankAccount = null)
    {
        return $this->app['api.mutex']->acquireAndRelease(
            'create_ondemand_fund_account_'.$merchantId,
            function() use ($merchantId, $bankAccount) {
                return $this->core()->addOndemandFundAccountForMerchant($merchantId, $bankAccount);
            }
        );
    }

    public function getOrCreateFundAccountForMerchant($merchantId)
    {
        $fundAccount = $this->core()->getFundAccountByMerchantId($merchantId);

        if ($fundAccount !== null && $fundAccount[OndemandFundAccount\Entity::FUND_ACCOUNT_ID] !== null) {
            return $fundAccount;
        }

        if ($this->core()->isFundAccountMigrated('write') === true) {
            $this->app['capital_early_settlements']->invalidateAndCreateFundAccount($merchantId, false, true);
        } else {
            $this->addOndemandFundAccountForMerchant($merchantId);
        }

        return $this->core()->getFundAccountByMerchantId($merchantId);
    }
}
