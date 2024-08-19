<?php

namespace RZP\Models\Merchant\Stakeholder;

use Database\Connection;
use RZP\Models\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\Traits\AsvEntityConnection;
use RZP\Models\Merchant\Acs\Traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\Traits\AsvFind;
use RZP\Models\Merchant\Acs\Traits\AsvFindEntity;
use RZP\Models\Merchant\Stakeholder\Entity as MerchantStakeholderEntity;
use RZP\Modules\Acs\Wrapper\MerchantStakeholder as MerchantStakeholderWrapper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Stakeholder as StakeholderSDKWrapper;
use RZP\Trace\TraceCode;


class Repository extends Base\Repository
{
    use Base\RepositoryUpdateTestAndLiveAndAsv;
    use AsvFetchCommon, AsvFindEntity;
    use AsvFind, AsvEntityConnection;

    protected $entity = 'stakeholder';

    public AsvRouter $asvRouter;

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }


    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
    ];

    public function fetchStakeholders(string $merchantId): Base\PublicCollection
    {
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_STAKEHOLDER_BY_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID),
            (new StakeholderSDKWrapper())->getByMerchantIdIgnoreInvalidArgumentCallback($merchantId),
            $this->fetchStakeholdersDatabaseCallback($merchantId),
            $this->fetchStakeholdersDatabaseCallback($merchantId, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    public function getStakeholderForMerchantIdForImplicitJoin(string $merchantId, string $entity)
    {
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_STAKEHOLDER_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN,
            $this->asvRouter->shouldRouteImplicitJoinToAccountService($merchantId, $entity, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN),
            (new StakeholderSDKWrapper())->findOneByMerchantIdCallback($merchantId),
            $this->findOneStakeholdersDatabaseCallback($merchantId),
            $this->findOneStakeholdersDatabaseCallback($merchantId, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    public function findOneStakeholdersDatabaseCallback(string $merchantId, $connectionType = null): \Closure
    {
        return function() use ($connectionType, $merchantId) {
            return $this->findOneStakeholdersDatabase($merchantId, $connectionType);
        };
    }

    public function findOneStakeholdersDatabase(string $merchantId, $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->get()
            ->first();
    }

    public function fetchStakeholdersDatabaseCallback(string $merchantId, $connectionType = null): \Closure
    {
        return function() use ($connectionType, $merchantId) {
            return $this->fetchStakeholdersDatabase($merchantId, $connectionType);
        };
    }

    public function fetchStakeholdersDatabase(string $merchantId, $connectionType = null): Base\PublicCollection
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->get();
    }

    public function fetchEsignCompletedMerchants(array $merchantIdList)
    {
        //NOTE: This change is being done for ASV Decomposition (#platform_account_service)
        //Changing the connection to TiDB as a fallback as no usage was found for this in the past 90 days.
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_MERCHANT))
            ->whereIn(Entity::MERCHANT_ID, $merchantIdList)
            ->where(Entity::AADHAAR_ESIGN_STATUS, '=', 'verified')
            ->get()
            ->pluck(Entity::MERCHANT_ID)
            ->toArray();
    }
    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service given the merchantId
     *
     * @throws \Throwable
     */
    public function __fetchStakeholders(string $merchantId): Base\PublicCollection
    {
        $apiStakeholders = $this->fetchStakeholders($merchantId);
        if (count($apiStakeholders) === 0) {
            return $apiStakeholders;
        }
        return (new MerchantStakeholderWrapper())->processFetchStakeholdersByMerchantId($merchantId, $apiStakeholders);
    }

    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service
     *
     * @throws \Throwable
     */
    public function __findOrFailPublic(string $id)
    {
        $apiStakeholder = $this->findOrFailPublic($id);
        $id = Entity::stripDefaultSign($id);
        return (new MerchantStakeholderWrapper())->processFetchStakeholderById($id, $apiStakeholder);
    }

    /**
     * Important: This function is for migration reads to account service.
     * Can be used to fetch stakeholder from Account Service
     *
     * @throws \Throwable
     */
    public function __findOrFail(string $id) {
        return $this->repo->transactionOnLiveAndTestAndAsv(function () use ($id) {
            $apiStakeholder = $this->findOrFail($id);
            return (new MerchantStakeholderWrapper())->processFetchStakeholderById($id, $apiStakeholder);
        });
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in stakeholder core while ramp-up
     *Once stakeholder saveOrFail is migrated to Account service only this method should be used while saving the stakeholder entity any save on stakeholder has to be called at any new place
     * @param MerchantStakeholderEntity $entity
     * @param bool $testAndLive - If true saveEntity on both test and live db else only live db
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantStakeholderEntity $entity, bool $testAndLive)
    {
        $this->repo->transactionOnLiveAndTestAndAsv(function () use ($testAndLive, $entity) {
            if ($testAndLive === true) {
                $this->saveOrFail($entity);
            } else {
                $this->repo->saveOrFail($entity);
            }
            (new MerchantStakeholderWrapper())->SaveOrFail($entity);
        });
    }
}
