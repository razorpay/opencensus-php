<?php

namespace RZP\Models\Settlement\Ondemand\Transfer;

use RZP\Models\Base;
use RZP\Models\Settlement\Ondemand\Bulk;
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

    public function processXSettlementTransfer($settlementOndemand)
    {
       [$transfers, $attempts] = $this->repo->transaction(function () use($settlementOndemand)
       {

           [$transfers, $attempts] = $this->core()->createMultipleSettlementOndemandTransfer($settlementOndemand);

            foreach ($transfers as $transfer)
            {
                (new Bulk\Core)->createSettlementOndemandBulk($settlementOndemand, $transfer->getAmount(), $transfer->getId());
            }

            return [$transfers, $attempts];
       });

        for ($i = 0; $i < sizeof($transfers); $i++)
        {
            CreateSettlementOndemandBulkTransfer::dispatch($this->mode, $attempts[$i]->getId(), $transfers[$i]);
        }
    }

    public function markAsProcessed(string $id)
    {
        $settlementOndemandTransfer = (new Repository)->findById($id);

        $this->core()->markAsProcessed($settlementOndemandTransfer);

        return [];
    }

}
