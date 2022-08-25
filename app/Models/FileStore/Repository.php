<?php

namespace RZP\Models\FileStore;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::TYPE                => 'sometimes|alpha_dash|max:100',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
    ];

    protected $entity = 'file_store';

    public function findByBatchId(string $batchId)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_TYPE, '=', 'batch')
            ->where(Entity::ENTITY_ID, '=', $batchId)
            ->first();
    }

    public function getFileWithNameAndMerchantIdAndName(string $merchantId, string $name, string $type)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, '=', $name)
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }

    public function removeFileStoreEntryWithMerchantIdAndName(string $merchantId, string $name, string $type)
    {
        return $this->newQuery()
                    ->where(Entity::NAME, '=', $name)
                    ->where(Entity::TYPE, '=', $type)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->delete();
    }

    public function getFileStoreIdWithEntiyId(array $invoiceIds)
    {
        return $this->newQuery()
            ->select(Entity::ID)
            ->whereIn(Entity::ENTITY_ID, $invoiceIds)
            ->get()
            ->pluck(Entity::ID)
            ->toArray();
    }

    public function getFilesBasedOnEntity(string $entityId)
    {
        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entityId)
            ->get()
            ->toArray();
    }
}
