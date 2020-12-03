<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Models\Base;
use RZP\Jobs\SettlementOndemand\CreateSettlementOndemandBulkTransfer;

class Service extends Base\Service
{
    public function processXSettlementBulkTransfer()
    {
        [$attempt, $transfer] = $this->repo->transaction(function ()
        {
           return $this->app['api.mutex']->acquireAndRelease(
                'settlement_ondemand_transfer_create',
                function()
                {
                    return $this->core()->createSettlementOndemandTransfer();
                });
        });

        if($transfer !== null)
        {
            CreateSettlementOndemandBulkTransfer::dispatch($this->mode, $attempt->getId(), $transfer);
        }

        return $transfer;
    }
}
