<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_document';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
    ];

    /**
     * @param $fileStoreId
     *
     * @return mixed
     */
    public function findDocumentByFileStoreId(string $fileStoreId)
    {
        return $this->newQuery()
                    ->where(Entity::FILE_STORE_ID, '=', $fileStoreId)
                    ->first();
    }

    /**
     * Returns all non deleted documents for given merchantIds
     *
     * @param array $merchantIds
     *
     * @return mixed
     */
    public function findDocumentsForMerchantIds(array $merchantIds)
    {
        return $this->newQuery()
                    ->whereIn(Entity::MERCHANT_ID, $merchantIds)
                    ->get();
    }

    /**
     * Fetch all the documents by entityId and entityType
     * @param string $entityId
     * @param string $entityType
     *
     * @return mixed
     */
    public function findDocumentsForEntityTypeAndEntityId(string $entityType, string $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->orderBy(Entity::CREATED_AT, 'asc')
                    ->get();
    }
}
