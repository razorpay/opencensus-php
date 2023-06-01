<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Modules\Acs\Wrapper\MerchantDocument;
use RZP\Models\Merchant\Document\Entity as MerchantDocumentEntity;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;

    protected $entity             = 'merchant_document';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|alpha_num',
        Entity::DOCUMENT_TYPE => 'sometimes|string|max:255'
    ];

    /**
     * __deleteOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in document core while ramp-up
     * @param Entity $entity
     * @throws \Throwable
     */
    public function __deleteOrFail(MerchantDocumentEntity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $this->repo->deleteOrFail($entity);
            $merchantDocumentWrapper = new MerchantDocument();
            $merchantDocumentWrapper->DeleteOrFail($entity);
        });
    }

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
     * Returns first non deleted documents for given validationId
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
     * Returns all non deleted documents for given merchantId
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function __findDocumentsForMerchantId(string $merchantId)
    {
        $documentsFromAPI = $this->findDocumentsForMerchantIds([$merchantId]);
        if (count($documentsFromAPI) === 0) {
            return $documentsFromAPI;
        }
        return (new MerchantDocument())->FindDocumentsForMerchantId($merchantId, $documentsFromAPI);
    }

    /**
     * __saveOrFail - Saves MerchantDocument Entity in API DB and ASV
     * @param DocumentEntity $document
     * @throws \Throwable
     */
    public function __saveOrFail($document) {
        $this->repo->transactionOnLiveAndTest(function () use ($document) {
            $this->saveOrFail($document);
            (new MerchantDocument())->SaveOrFail($document);
        });
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
     * Fetch all the documents by merchantId, documentType and documentDate
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

    public function findDocumentsForMerchantIdAndDocumentTypesAndDate(string $merchantId, array $documentTypes, int $from, int $to)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->whereIn(Entity::DOCUMENT_TYPE,$documentTypes)
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

    /**
     * Returns all non deleted documents for given validationId
     *
     * @param string $merchantId
     * @param string $validationId
     *
     * @return mixed
     */
    public function findNonDeletedDocumentForMerchantIdAndValidationId(string $merchantId, string $validationId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::VALIDATION_ID, $validationId)
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->first();
    }

    /* Fetch the documents by merchantId and documentType.
     *
     * @param string $merchantId
     * @param string $documentType
     * @return mixed
     */
    public function findDocumentsForMerchantIdAndDocumentType(string $merchantId, string $documentType)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereNull(Entity::DELETED_AT)
            ->get()
            ->first();
    }

    public function findAllMerchantsAndDistinctDatedDocumentsAddedInRangeWithDocumentType(string $documentType,string $from, string $to)
    {
        return $this->newQuery()
            ->select(Entity::MERCHANT_ID,Entity::DOCUMENT_DATE)
            ->distinct()
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->whereNull(Entity::DELETED_AT)
            ->get();
    }

    public function findLatestDocumentForMerchantIdAndDocumentTypeInRange(string $merchantId, string $documentType, string $from, string $to)
    {
        return $this->newQueryOnSlave()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereBetween(Entity::DOCUMENT_DATE, [$from, $to])
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->get()
            ->first();
    }

    /**
     * Returns all non deleted documents for given merchantIds
     *
     * @param string $merchantId
     * @param array $documentTypes
     *
     * @return mixed
     */
    public function findNonDeletedDocumentsForMerchantId(string $merchantId, array $documentTypes, string $connectionType = null)
    {
        if($connectionType === null)
        {
            $connectionType = ConnectionType::REPLICA;
        }

        return $this->newQueryWithConnection($this->getConnectionFromType($connectionType))
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereIn(Entity::DOCUMENT_TYPE, $documentTypes)
            ->whereNull(Entity::DELETED_AT)
            ->get()
            ->first();
    }

    public function findAllDocumentsForMerchant(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::SOURCE, 'UFH')
                    ->where(Entity::ENTITY_TYPE, 'merchant')
                    ->get();
    }

}
