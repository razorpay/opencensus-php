<?php
namespace RZP\Models\Settlement\Ondemand\Bulk;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand.bulk';

    public function findWhereTransferIdNull()
    {
        return $this->newQuery()
                    ->whereNull(ENTITY::SETTLEMENT_ONDEMAND_TRANSFER_ID)
                    ->whereNotNull(ENTITY::ID)
                    ->get();
    }
}
