<?php

namespace RZP\Models\BankTransferAttempt;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'bank_transfer_attempt';

    protected $appFetchParamRules = [
        Entity::ENTITY_TYPE         => 'sometimes|string|in:settlement',
        Entity::ENTITY_ID           => 'sometimes|string|size:14',
        Entity::STATUS              => 'sometimes|string|size:1',
        Entity::BANK_ACCOUNT_ID     => 'sometimes|alpha_num|max:14',
        Entity::UTR                 => 'sometimes|alpha_num',
        Entity::BATCH_TRANSFER_ID   => 'sometimes|alpha_num|max:14',
        Entity::VERSION             => 'sometimes|string|in:v1,v2',
    ];

    public function getBankTransferAttemptsByBatchSettlementIdWithRelations($batchSettlementId, $relations = [])
    {
        $query = $this->newQuery()
                      ->where(Entity::BATCH_TRANSFER_ID, '=', $batchSettlementId);

        if (count($relations) > 0)
        {
            $query->with($relations);
        }

        return $query->get();
    }
}