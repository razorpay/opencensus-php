<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use Database\Connection;
use RZP\Exception\BaseException;
use Razorpay\Asv\RequestMetadata;
use RZP\Models\Base\PublicCollection;
use RZP\Exception\BadRequestException;
use Illuminate\Database\Eloquent\Collection;
use RZP\Models\Merchant\Acs\AsvRouter\AsvMaps\FunctionConstant;
use RZP\Models\Merchant\Acs\AsvRouter\AsvRouter;
use RZP\Models\Merchant\Acs\Traits\AsvFindEntity;
use RZP\Modules\Acs\Wrapper\MerchantEmail;
use RZP\Models\Base\RepositoryUpdateTestAndLiveAndAsv;
use RZP\Models\Merchant\Acs\Traits\AsvFetchCommon;
use RZP\Models\Merchant\Acs\Traits\AsvFind;
use RZP\Models\Merchant\Acs\Traits\AsvEntityConnection;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\Constant\Constant as ASVV2Constant;
use RZP\Models\Merchant\Acs\AsvSdkIntegration\MerchantEmail as MerchantEmailSDKWrapper;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLiveAndAsv;
    use AsvFetchCommon, AsvFindEntity;
    use AsvFind;
    use AsvEntityConnection;

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
        $result = $this->getEntityDetails(
            ASVV2Constant::GET_EMAIL_BY_TYPE_AND_MERCHANT_ID,
            $this->asvRouter->shouldRouteToAccountService($merchantId, get_class($this), FunctionConstant::GET_BY_TYPE_AND_MERCHANT_ID),
            (new MerchantEmailSDKWrapper())->getByTypeAndMerchantIdCallBack($type, $merchantId),
            $this->getEmailByTypeFromDatabaseCallBack($type, $merchantId),
            $this->getEmailByTypeFromDatabaseCallBack($type, $merchantId, Connection::ASV_WRITER)
        );

        $this->resetConnectionOnModels($result);
        return $result;
    }

    private function getEmailByTypeFromDatabaseCallBack(string $type, string $merchantId, $connectionType = null): \Closure
    {
        return function () use ($connectionType, $type, $merchantId) {
            return $this->getEmailByTypeFromDatabase($type, $merchantId, $connectionType);
        };
    }

    public function getEmailByTypeFromDatabase(string $type, string $merchantId, $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query->where(Entity::TYPE, $type)
            ->merchantId($merchantId)
            ->first();
    }

    /**
     * This function does not check for verification status and
     * hence should not be used for getting emails for communication.
     * this function get all the emails by type except 'partner_dummy' type
     *
     * @param string $merchantId
     *
     * @return PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getEmailByMerchantId(string $merchantId): PublicCollection
    {
        if ($this->asvRouter->shouldRouteFilterToAsv(__FUNCTION__))
        {
            if ($this->repo->isTransactionActive())
            {
                $results = $this->getEmailByMerchantIdFromDatabase(
                    $merchantId, Connection::ASV_WRITER
                );
            }
            else
            {
                $requestMetadata = new RequestMetadata();
                $requestMetadata->setTimeoutInMicroSeconds(
                    MerchantEmailSDKWrapper::FILTER_TIMEOUT_IN_MICRO_SECONDS
                );

                $results = (new MerchantEmailSDKWrapper())->getAllExceptPartnerDummyByMerchantId(
                    $merchantId, $requestMetadata
                );
            }

            $this->resetConnectionOnModels($results);

            return $results;
        }

        return $this->getEmailByMerchantIdFromDatabase($merchantId);
    }

    private function getEmailByMerchantIdFromDatabaseCallBack(string $merchantId): \Closure
    {
        return function () use ($merchantId) {
            return $this->getEmailByMerchantIdFromDatabase($merchantId);
        };
    }

    public function getEmailByMerchantIdFromDatabase(string $merchantId, string $connectionType = null)
    {
        if (!is_null($connectionType))
        {
            $query = $this->newQueryWithConnection(
                $this->getConnectionFromType($connectionType)
            );
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query
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
     *
     * @return Collection|PublicCollection
     * @throws BadRequestException
     * @throws BaseException
     */
    public function getEmailsByMerchantIdsAndTypes(array $merchantIds, array $types): PublicCollection|Collection
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
                $results = (new MerchantEmailSDKWrapper())->getEmailsByMerchantIdsAndTypes(
                  $merchantIds, $types
                );

                $this->resetConnectionOnModels($results);

                return $results;
            }
        }
        else
        {
            $query = $this->newQuery();
        }

        $results = $query
            ->select(Base\PublicEntity::MERCHANT_ID, Entity::TYPE, Entity::EMAIL)
            ->whereIn(Base\PublicEntity::MERCHANT_ID, $merchantIds)
            ->whereIn(Entity::TYPE, $types)
            ->get();

        $this->resetConnectionOnModels($results);

        return $results;
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
