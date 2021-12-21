<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity             = 'merchant_document';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|alpha_num',
        Entity::DOCUMENT_TYPE => 'sometimes|string|max:255'
    ];

    /**
     * fetch documents by Id
     *
     * @param string $id
     * @return mixed
     */
    public function findDocumentById(string $id)
    {
        return $this->newQuery()
                    ->where(Entity::ID,'=',$id)
                    ->first();
    }

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
     * Returns all non deleted documents for given validationId
     *
     * @param string $merchantId
     * @param string $validationId
     *
     * @return mixed
     */
    public function findDocumentsForMerchantIdAndValidationId(string $merchantId, string $validationId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::VALIDATION_ID, $validationId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
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
     *
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

    /**
     * Fetch all the documents by merchantId , documentType and documentDate
     *
     * @param string $merchantId
     * @param string $documentType
     * @param int $from
     * @param int $to
     * @return mixed
     */

    public function findDocumentsForMerchantIdAndDocumentTypeAndDate(string $merchantId, string $documentType, int $from, int $to)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::DOCUMENT_TYPE,$documentType)
                    ->whereBetween(Entity::DOCUMENT_DATE, [$from, $to])
                    ->whereNull(Entity::DELETED_AT)
                    ->get();
    }

    public function filterMerchantIdsWithUploadedDocuments(array $merchantIdList, string $documentType)
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }

}
