<?php


namespace RZP\Models\Merchant\BusinessDetail;

use RZP\Base\ConnectionType;
use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\BusinessDetail as BusinessDetailSDKWrapper;
use RZP\Models\Merchant\Acs\SplitzHelper\SplitzHelper;
use RZP\Models\Merchant\Acs\traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\traits\AsvFind;
use RZP\Modules\Acs\Wrapper\MerchantBusinessDetail as MerchantBusinessDetailWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;
    use AsvFetchCommon;
    use AsvFind;

    protected $entity = 'merchant_business_detail';

    public AsvRouter $asvRouter;

    function __construct()
    {
        parent::__construct();

        $this->asvRouter = new AsvRouter();
    }

    public function getBusinessDetailsForMerchantId(string $merchantId): ?Entity
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_BUSINESS_DETAIL_BY_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID),
            (new BusinessDetailSDKWrapper())->getLatestByMerchantIdCallBack($merchantId),
            $this->getBusinessDetailsForMerchantIdDatabaseCallBack($merchantId)
        );
    }

    public function getBusinessDetailsForMerchantIdDatabaseCallBack(string $merchantId) {
        return function () use ($merchantId) {
            return $this->getBusinessDetailsForMerchantIdDatabase($merchantId);
        };
    }

    public function getBusinessDetailsForMerchantIdDatabase(string $merchantId): ?Entity
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->orderBy(Entity::CREATED_AT, 'desc')
            ->orderBy(Entity::ID, 'desc')
            ->first();
    }

    public function __getBusinessDetailsForMerchantId(string $merchantId)
    {
        $businessDetailFromApi = $this->getBusinessDetailsForMerchantId($merchantId);
        if ($businessDetailFromApi === null) {
            return $businessDetailFromApi;
        }
        return (new MerchantBusinessDetailWrapper())->GetMerchantBusinessDetailForMerchantId($merchantId, $businessDetailFromApi);
    }

    public function __saveOrFail($businessDetail) {
        return $this->repo->transactionOnLiveAndTest(function () use ($businessDetail) {
            $this->saveOrFail($businessDetail);
            (new MerchantBusinessDetailWrapper())->SaveOrFail($businessDetail);
        });
    }

    /*
     * Added for parity testing of account service.
     */
    public function getAllBusinessDetailsFromReplica(string $merchantId)
    {
        return $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->get();
    }


    /*
     * Added for parity testing of account service.
    */
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
}
