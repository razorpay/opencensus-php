<?php

namespace RZP\Models\Settlement\Ondemand\Bulk;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{

    public function createSettlementOndemandBulk($settlementOndemand)
    {
        $this->trace->info(TraceCode::SETTLEMENT_ONDEMAND_BULK_CREATE, [
            'settlement_ondemand_id'   => $settlementOndemand->getId(),
            'amount'                   => $settlementOndemand->getAmount()-$settlementOndemand->getTotalFees(),
        ]);

        $data = [
            Entity::SETTLEMENT_ONDEMAND_ID => $settlementOndemand->getId(),
            Entity::AMOUNT => $settlementOndemand->getAmount()-$settlementOndemand->getTotalFees(),
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
