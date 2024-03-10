<?php


namespace RZP\Models\Merchant\Website;

use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base;
use RZP\Base\ConnectionType;
use RZP\Models\Base\RepositoryUpdateTestAndLiveAndAsv;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\Traits\AsvFind;
use RZP\Models\Merchant\Acs\Traits\AsvFindEntity;
use RZP\Models\Merchant\Website\Entity as MerchantWebsiteEntity;
use RZP\Modules\Acs\Wrapper\MerchantWebsite as MerchantWebsiteWrapper;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantWebsite as MerchantWebsiteSDKWrapper;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Acs\Traits\AsvFetchCommon;


class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLiveAndAsv;
    use AsvFind, AsvFindEntity;
    use AsvFetchCommon;

    protected $entity ='merchant_website';
    public AsvRouter $asvRouter;


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

    public function getWebsiteDetailsForMerchantId(string $merchantId)
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_WEBSITE_BY_MERCHANT_ID,
            true,
            (new MerchantWebsiteSDKWrapper())->getLatestByMerchantIdOrFailCallBack($merchantId),
            $this->getWebsiteDetailsForMerchantIdFromDatabaseCallBack($merchantId)
        );
    }

    public function getWebsiteDetailsForMerchantIdForImplicitJoin(string $merchantId, string $entity)
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_WEBSITE_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN,
            $this->asvRouter->shouldRouteImplicitJoinToAccountService($merchantId, $entity, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID_FOR_IMPLICIT_JOIN),
            (new MerchantWebsiteSDKWrapper())->getLatestByMerchantIdCallBack($merchantId),
            $this->getWebsiteDetailsForMerchantIdFromDatabaseCallBack($merchantId)
        );
    }

    private function getWebsiteDetailsForMerchantIdFromDatabaseCallBack($merchantId): \Closure
    {
        return function() use ($merchantId) {
            return $this->getWebsiteDetailsForMerchantIdFromDatabase($merchantId);
        };
    }

    public function getWebsiteDetailsForMerchantIdFromDatabase(string $merchantId){
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->orderBy(Entity::ID, 'desc')
            ->first();
    }

    public function getAllWebsiteDetailsForMerchantId(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getConnectionFromType(ConnectionType::REPLICA))
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->get();
    }

    /*
     * Important: This function is to be used to migrate reads to Account Service
     * Only use this if you are trying to do read on account service database.
     */
    public function __getWebsiteDetailsForMerchantId(string $merchantId)
    {
        $apiMerchantWebsiteEntity = $this->getWebsiteDetailsForMerchantId($merchantId);
        if ($apiMerchantWebsiteEntity === null) {
            return $apiMerchantWebsiteEntity;
        }
        return (new MerchantWebsiteWrapper())->processGetWebsiteDetailsForMerchantId($merchantId, $apiMerchantWebsiteEntity);
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in merchant website core while ramp-up
     * @param MerchantWebsiteEntity $entity
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantWebsiteEntity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $this->saveOrFail($entity);
            $merchantWebsiteWrapper = new MerchantWebsiteWrapper();
            $merchantWebsiteWrapper->SaveOrFail($entity);
        });
    }

    /*
     * Important: This function is to be used to migrate reads to Account Service
     * Only use this if you are trying to do read on account service database.
     */
    public function __findOrFail(string $id)
    {
        $apiMerchantWebsiteEntity = $this->findOrFail($id);
        return (new MerchantWebsiteWrapper())->processGetWebsiteDetailsForId($id, $apiMerchantWebsiteEntity);
    }
}
