<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Modules\Acs\Wrapper\MerchantEmail;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Acs\traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\traits\AsvFind;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail as MerchantEmailSDKWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;
    use AsvFetchCommon;
    use AsvFind;

    protected $entity = 'merchant_email';

    public $asvRouter;

    /**
     * These are admin allowed params to search on.
     *
     * @var array
     */
    protected $appFetchParamRules = [
        Entity::TYPE => 'sometimes|string|size:18',
        Entity::EMAIL => 'sometimes|string|email',
        Entity::MERCHANT_ID => 'sometimes|string|unsigned_id',
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
     * This function does not check for verification status and
     * hence should not be used for getting emails for communication.
     *
     * @param string $type
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getEmailByType(string $type, string $merchantId)
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_EMAIL_BY_TYPE_AND_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID),
            (new MerchantEmailSDKWrapper())->getByTypeAndMerchantIdCallBack($type, $merchantId),
            $this->getEmailByTypeFromDatabaseCallBack($type, $merchantId)
        );
    }

    private function getEmailByTypeFromDatabaseCallBack(string $type, string $merchantId): \Closure
    {
        return function () use ($type, $merchantId) {
            return $this->getEmailByTypeFromDatabase($type, $merchantId);
        };
    }

    public function getEmailByTypeFromDatabase(string $type, string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::TYPE, $type)
            ->merchantId($merchantId)
            ->first();
    }

    /**
     * This function does not check for verification status and
     * hence should not be used for getting emails for communication.
     * this function get all the emails by type except 'partner_dummy' type
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getEmailByMerchantId(string $merchantId)
    {
        return $this->getEntityDetails(
            ASVV2Constant::GET_EMAIL_BY_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_MERCHANT_ID),
            (new MerchantEmailSDKWrapper())->getAllExceptPartnerDummyByMerchantIdCallback($merchantId),
            $this->getEmailByMerchantIdFromDatabaseCallBack($merchantId)
        );
    }

    private function getEmailByMerchantIdFromDatabaseCallBack(string $merchantId): \Closure
    {
        return function () use ($merchantId) {
            return $this->getEmailByMerchantIdFromDatabase($merchantId);
        };
    }

    public function getEmailByMerchantIdFromDatabase(string $merchantId)
    {
        return $this->newQuery()
            ->where(Entity::MERCHANT_ID, $merchantId)
            ->Where(Entity::TYPE, '<>', Type::PARTNER_DUMMY)
            ->get();
    }

    /**
     * This function fetched MerchantEmail for API DB and AccountService
     * and
     * this function get all the emails by type except 'partner_dummy' type
     * @param string $merchantId
     *
     * @return mixed
     */
    public function __getEmailByMerchantId(string $merchantId)
    {
        $merchantEmails = $this->getEmailByMerchantId($merchantId);
        if (count($merchantEmails) === 0) {
            return $merchantEmails;
        }
        return (new MerchantEmail())->FetchMerchantEmailsFromMerchantId($merchantId, $merchantEmails);
    }

    /**
     * @param array $merchantIds
     * @param array $types
     * @return mixed
     */
    public function getEmailsByMerchantIdsAndTypes(array $merchantIds, array $types)
    {
        return $this->newQuery()
            ->select(Entity::MERCHANT_ID, Entity::TYPE, Entity::EMAIL)
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->whereIn(Entity::TYPE, $types)
            ->get();
    }

    /**
     *
     * IMP: This function is for specific use case of Account Service Data Migration
     * Please consider going through the implementation before using
     *
     * Returns the emails that were updated in the specified range
     * @param int $from
     * @param int $to
     * @return mixed
     */
    public function getEmailsUpdatedBetween(int $from, int $to)
    {
        return $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
            ->WhereBetween(Entity::UPDATED_AT, [$from, $to])
            ->Where(Entity::TYPE, '<>', Type::PARTNER_DUMMY)
            ->get();
    }
}
