<?php

namespace RZP\Models\Merchant\Document;

use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Models\Merchant\Acs\traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\traits\AsvFind;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument as MerchantDocumentSDKWrapper;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLive;
    use AsvFetchCommon;
    use AsvFind;

    protected $entity             = 'merchant_document';

    public $asvRouter;

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID   => 'sometimes|alpha_num',
        Entity::DOCUMENT_TYPE => 'sometimes|string|max:255'
    ];

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }

    public function fetchAllMerchantIDsFromSlaveDB($input)
    {
        $query = $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->select([Entity::MERCHANT_ID])
            ->distinct()
            ->orderBy(Entity::MERCHANT_ID);

        if (isset($input['after_merchant_id']) === true) {
            $query->where(Entity::MERCHANT_ID, '>', $input['after_merchant_id']);
        }

        if (isset($input['count']) === true) {
            $query->take($input['count']);
        }

        return $query->get();
    }

    /**
     * fetch documents by Id
     *
     * @param string $id
     * @return mixed
     */
    public function findDocumentById(string $id)
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_DOCUMENT_BY_ID,
            $this->asvRouter->shouldRouteToAccountService($id, get_class($this), FunctionConstant::GET_BY_ID),
            (new MerchantDocumentSDKWrapper())->getDocumentByIdCallback($id),
            $this->findDocumentByIdFromDatabaseCallBack($id)
        );
    }

    private function findDocumentByIdFromDatabaseCallBack(string $id): \Closure
    {
        return function () use ($id) {
            return $this->findDocumentByIdFromDatabase($id);
        };
    }

    public function findDocumentByIdFromDatabase(string $id)
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
        return $this->getEntityDetails(
            ASVV2Constant::GET_DOCUMENT_BY_TYPE_AND_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID),
            (new MerchantDocumentSDKWrapper())->getByDocumentsByMerchantIdAndTypeCallBack($merchantId, $documentType),
            $this->findDocumentsForMerchantIdAndDocumentTypeFromDatabaseCallBack($merchantId, $documentType)
        );
    }

    private function findDocumentsForMerchantIdAndDocumentTypeFromDatabaseCallBack(string $merchantId, string $documentType): \Closure
    {
        return function () use ($merchantId, $documentType) {
            return $this->findDocumentsForMerchantIdAndDocumentTypeFromDatabase($merchantId, $documentType);
        };
    }

    public function findDocumentsForMerchantIdAndDocumentTypeFromDatabase(string $merchantId, string $documentType)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->orderBy(Entity::ID, 'desc')
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

    /*
     * Added for parity testing of account service.
     */
    public function getAllDocumentsFromReplica(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->get();
    }

}
