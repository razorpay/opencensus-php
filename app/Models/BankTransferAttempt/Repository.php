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
        Entity::BANK_ACCOUNT_ID     => 'sometimes|alpha_num|size:14',
        Entity::UTR                 => 'sometimes|alpha_num',
        Entity::BATCH_TRANSFER_ID   => 'sometimes|alpha_num|size:14',
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

    public function findByIdWithSourceAndRelations($id, $relations = [])
    {
        // Find the bta entity
        $bta = $this->newQuery()->findOrFailPublic($id);

        $type = $bta->getEntityType();

        // Find the source entity of bta, and fetch relevant relations
        $entityWithRelations = $this->manager->$type->findByIdWithRelations(
                                    $bta->getEntityId(),
                                    $relations);

        $bta->setRelation('source', $entityWithRelations);

        return $bta;
    }
}