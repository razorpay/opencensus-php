<?php

namespace RZP\Models\Merchant\Document;

use RZP\Models\Base;
use Database\Connection;
use RZP\Base\ConnectionType;
use RZP\Exception\BaseException;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use RZP\Models\Merchant\Acs\Traits\AsvFind;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\Traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\Traits\AsvEntityConnection;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Base as AsvSdkIntegration;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantDocument as MerchantDocumentSDKWrapper;
use RZP\Models\Merchant\Acs\Traits\AsvFindEntity;

class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLiveAndAsv;
    use AsvFetchCommon, AsvFindEntity;
    use AsvFind;
    use AsvEntityConnection;

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
    public function deleteOnAccountService($entity): void {
        try
        {
            (new AsvSdkIntegration())->delete($entity);
        }
        catch (\Throwable $th)
        {
            // Map the ASV Error to DB QueryException, so that the handling for this error is not impacted.
            // However, we are not Setting SQL and binding and the SQL generated will be empty.
            // It is not possible to get the query executed as it is done on Account Service.
            throw new \Illuminate\Database\QueryException($entity->getConnectionName(),"", [], $th);
        }
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
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_DOCUMENT_BY_ID,
            $this->asvRouter->shouldRouteToAccountService($id, get_class($this), FunctionConstant::GET_BY_ID),
            (new MerchantDocumentSDKWrapper())->getDocumentByIdCallback($id),
            $this->findDocumentByIdFromDatabaseCallBack($id),
            $this->findDocumentByIdFromDatabaseCallBack($id, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    private function findDocumentByIdFromDatabaseCallBack(string $id, $connectionType = null): \Closure
    {
        return function () use ($connectionType, $id) {
            return $this->findDocumentByIdFromDatabase($id, $connectionType);
        };
    }

    public function findDocumentByIdFromDatabase(string $id, $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->where(Entity::ID,'=',$id)
            ->first();
    }


    public function getDocumentsForMerchantIdForImplicitJoin(string $merchantId, string $entity)
    {
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_DOCUMENT_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN,
            $this->asvRouter->shouldRouteImplicitJoinToAccountService($merchantId, $entity, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN),
            (new MerchantDocumentSDKWrapper())->findDocumentByMerchantIdCallback($merchantId),
            $this->findDocumentByMerchantIdFromDatabaseCallBack($merchantId),
            $this->findDocumentByMerchantIdFromDatabaseCallBack($merchantId, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    private function findDocumentByMerchantIdFromDatabaseCallBack(string $merchantId, $connectionType = null): \Closure
    {
        return function () use ($connectionType, $merchantId) {
            return $this->findDocumentByMerchantIdFromDatabase($merchantId, $connectionType);
        };
    }

    public function findDocumentByMerchantIdFromDatabase(string $merchantId, $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->where(Entity::MERCHANT_ID, $merchantId)
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->orderBy(Entity::ID, 'desc')
            ->get();
    }

    /**
     * @param string $fileStoreId
     *
     * @return ?Entity
     */
    public function findDocumentByFileStoreId(string $fileStoreId): ?Entity
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER));
            }
            else
            {
                $results = (new MerchantDocumentSDKWrapper())->findDocumentByFileStoreId($fileStoreId)->first();

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query->where(Entity::FILE_STORE_ID, '=', $fileStoreId)->first();

        $this->resetConnectionOnModels($results);

        return $results;
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
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER));
            }
            else
            {
                $results = (new MerchantDocumentSDKWrapper())
                    ->findDocumentsForMerchantIdAndValidationId($merchantId, $validationId)
                    ->first();

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::VALIDATION_ID, $validationId)
                    ->orderBy(Entity::CREATED_AT, 'desc')
                    ->first();

        $this->resetConnectionOnModels($results);

        return $results;
    }

    /**
     * Returns all non deleted documents for given merchantIds
     *
     * @param array $merchantIds
     *
     * @return mixed
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findDocumentsForMerchantIds(array $merchantIds): mixed
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastDocumentId = '';

                do
                {
                    $subset = (new MerchantDocumentSDKWrapper())->findDocumentsForMerchantIds(
                        $merchantIds, $lastDocumentId,
                    );

                    $lastDocumentId = $subset->pluck(Base\UniqueIdEntity::ID)->last();

                    $results->push($subset);

                } while(sizeof($subset) == AsvSdkIntegration::FETCH_SERVICE_FILTER_LIMIT);

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query->whereIn(Entity::MERCHANT_ID, $merchantIds)->get();

        $this->resetConnectionOnModels($results);

        return $results;
    }

    /**
     * Fetch all the documents by entityId and entityType
     *
     * @param string $entityType
     * @param string $entityId
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findDocumentsForEntityTypeAndEntityId(string $entityType, string $entityId): Collection|PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastDocumentId = '';

                do
                {
                    $subset = (new MerchantDocumentSDKWrapper())->findDocumentsForEntityTypeAndEntityId(
                        $entityId, $entityType, $lastDocumentId
                    );

                    $lastDocumentId = $subset->pluck(Base\UniqueIdEntity::ID)->last();

                    $results->push($subset);

                } while(sizeof($subset) == AsvSdkIntegration::FETCH_SERVICE_FILTER_LIMIT);

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query
            ->where(Entity::ENTITY_ID, $entityId)
            ->where(Entity::ENTITY_TYPE, $entityType)
            ->orderBy(Entity::CREATED_AT, 'asc')
            ->get();

        $this->resetConnectionOnModels($results);

        return $results;
    }

    /**
     * Fetch all the documents by merchantId, documentType and documentDate
     *
     * @param string $merchantId
     * @param string $documentType
     * @param int    $from
     * @param int    $to
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findDocumentsForMerchantIdAndDocumentTypeAndDate(
        string $merchantId, string $documentType, int $from, int $to
    ): Collection|PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                  $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results        = new PublicCollection();
                $lastDocumentId = '';

                do
                {
                    $subset = (new MerchantDocumentSDKWrapper())->findDocumentsForMerchantIdAndDocumentTypeAndDate(
                        $merchantId, $documentType, $from, $to, $lastDocumentId
                    );

                    $lastDocumentId = $subset->pluck(Base\UniqueIdEntity::ID)->last();

                    $results->push($subset);

                } while(sizeof($subset) == AsvSdkIntegration::FETCH_SERVICE_FILTER_LIMIT);

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DOCUMENT_TYPE,$documentType)
            ->whereBetween(Entity::DOCUMENT_DATE, [$from, $to])
            ->whereNull(Entity::DELETED_AT)
            ->get();

        $this->resetConnectionOnModels($results);

        return $results;
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
        //NOTE: This change is being done for ASV Decomposition (#platform_account_service)
        //Changing the connection to TiDB as a fallback as no usage was found for this in the past 90 days.
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
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
     * @return Entity|null
     * @throws BadRequestException
     * @throws BaseException
     */
    public function findNonDeletedDocumentForMerchantIdAndValidationId(
        string $merchantId, string $validationId
    ): ?Entity
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $query = $this->newQueryWithConnection(
                    $this->getConnectionFromType(Connection::ASV_WRITER)
                );
            }
            else
            {
                $results = (new MerchantDocumentSDKWrapper())->findNonDeletedDocumentForMerchantIdAndValidationId(
                    $merchantId, $validationId
                );

                $this->resetConnectionOnModels($results);

                return $results->first();
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::VALIDATION_ID, $validationId)
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->get();

        $this->resetConnectionOnModels($results);

        return $results->first();
    }

    /* Fetch the documents by merchantId and documentType.
     *
     * @param string $merchantId
     * @param string $documentType
     * @return mixed
     */
    public function findDocumentsForMerchantIdAndDocumentType(string $merchantId, string $documentType)
    {
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_DOCUMENT_BY_TYPE_AND_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID),
            (new MerchantDocumentSDKWrapper())->getByDocumentsByMerchantIdAndTypeCallBack($merchantId, $documentType),
            $this->findDocumentsForMerchantIdAndDocumentTypeFromDatabaseCallBack($merchantId, $documentType),
            $this->findDocumentsForMerchantIdAndDocumentTypeFromDatabaseCallBack($merchantId, $documentType, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    private function findDocumentsForMerchantIdAndDocumentTypeFromDatabaseCallBack(string $merchantId, string $documentType, $connectionType = null): \Closure
    {
        return function () use ($connectionType, $merchantId, $documentType) {
            return $this->findDocumentsForMerchantIdAndDocumentTypeFromDatabase($merchantId, $documentType, $connectionType);
        };
    }

    public function findDocumentsForMerchantIdAndDocumentTypeFromDatabase(string $merchantId, string $documentType, $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->where(Entity::MERCHANT_ID, $merchantId)
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereNull(Entity::DELETED_AT)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->orderBy(Entity::ID, 'desc')
            ->get()
            ->first();
    }

    public function findAllMerchantsAndDistinctDatedDocumentsAddedInRangeWithDocumentType(string $documentType,string $from, string $to)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
            ->select(Entity::MERCHANT_ID,Entity::DOCUMENT_DATE)
            ->distinct()
            ->where(Entity::DOCUMENT_TYPE, $documentType)
            ->whereBetween(Entity::CREATED_AT, [$from, $to])
            ->whereNull(Entity::DELETED_AT)
            ->get();
    }

    public function findLatestDocumentForMerchantIdAndDocumentTypeInRange(string $merchantId, string $documentType, string $from, string $to)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN))
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
     * @param string      $merchantId
     * @param array       $documentTypes
     * @param string|null $connectionType
     *
     * @return ?Entity
     */
    public function findNonDeletedDocumentsForMerchantId(string $merchantId, array $documentTypes, string $connectionType = null): ?Entity
    {
        if($connectionType === null)
        {
            $connectionType = ConnectionType::REPLICA;
        }

        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->isTransactionActive())
            {
                $query = $this->newQueryWithConnection($this->getConnectionFromType(Connection::ASV_WRITER));
            }
            else
            {
                $results = (new MerchantDocumentSDKWrapper())
                    ->findNonDeletedDocumentsForMerchantId($merchantId, $documentTypes)
                    ->first();

                $this->resetConnectionOnModels($results, $this->getConnectionFromType($connectionType));

                return $results;
            }
        }
        else
        {
            $query = $this->newQueryWithConnection(
                $this->getConnectionFromType($connectionType)
            );
        }

        $results = $query
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->whereIn(Entity::DOCUMENT_TYPE, $documentTypes)
            ->whereNull(Entity::DELETED_AT)
            ->get()
            ->first();

        $this->resetConnectionOnModels($results, $this->getConnectionFromType($connectionType));

        return $results;
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
