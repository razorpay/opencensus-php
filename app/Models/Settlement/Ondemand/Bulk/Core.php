<?php

namespace RZP\Models\Settlement\Ondemand\Bulk;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    public function createSettlementOndemandBulk($settlementOndemand, $amount, $settlementOndemandTransferId = null)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_BULK_CREATE, [
            'settlement_ondemand_id'   => $settlementOndemand->getId(),
            'amount'                   => $amount,
        ]);

        $data = [
            Entity::SETTLEMENT_ONDEMAND_ID => $settlementOndemand->getId(),
            Entity::AMOUNT => $amount,
            Entity::SETTLEMENT_ONDEMAND_TRANSFER_ID => $settlementOndemandTransferId,
        ];

        $settlementOndemandBulk = (new Entity)->build($data);

        $settlementOndemandBulk->generateId();

        $this->repo->saveOrFail($settlementOndemandBulk);
    }

    public function findSettlementOndemandBulksInPastCycle()
    {
        return (new Repository)->findWhereTransferIdNull();
    }

    public function  fillTransferId($bulks, $id)
    {
        foreach($bulks as $bulk)
        {
            $bulk->setOndemandTransferId($id);

            $this->repo->saveOrFail($bulk);
        }
    }
}
