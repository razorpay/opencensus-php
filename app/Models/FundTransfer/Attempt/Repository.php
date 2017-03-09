<?php

namespace RZP\Models\FundTransfer\Attempt;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'fund_transfer_attempt';

    protected $signedIds = [
        Entity::BANK_ACCOUNT_ID,
    ];

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::SOURCE_TYPE         => 'sometimes|string|in:settlement',
        Entity::SOURCE_ID           => 'sometimes|alpha_dash|min:14|max:19',
        Entity::STATUS              => 'sometimes|string|size:1',
        Entity::UTR                 => 'sometimes|alpha_num',
        Entity::BATCH_TRANSFER_ID   => 'sometimes|alpha_num|size:14',
        Entity::VERSION             => 'sometimes|string|in:v1,v2',
    ];

    public function getFundTransferAttemptsByBatchIdWithRelations(
        string $batchSettlementId,
        array $relations = [])
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