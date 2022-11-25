<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Modules\Acs\ASVEntityMapper;
use RZP\Modules\Acs\Wrapper\MerchantEmail;
use RZP\Models\Base\RepositoryUpdateTestAndLive;
use RZP\Models\Merchant\Email\Entity as MerchantEmailEntity;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_email';

    /**
     * These are admin allowed params to search on.
     *
     * @var array
     */
    protected $appFetchParamRules = [
        Entity::TYPE        => 'sometimes|string|size:18',
        Entity::EMAIL       => 'sometimes|string|email',
        Entity::MERCHANT_ID => 'sometimes|string|unsigned_id',
    ];

    /**
     * __delete -  Keeping the method name not same with base repository method, this to be renamed  and used in merchant email core while ramp-up
     * @param MerchantEmailEntity $entity
     * @throws \Throwable
     */
    public function __delete(MerchantEmailEntity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $this->repo->delete($entity);
            $merchantEmailWrapper = new MerchantEmail();
            $merchantEmailWrapper->Delete($entity);
        });
    }

    /**
     * __saveOrFail -  Keeping the method name not same with base repository method, this to be renamed  and used in merchant email core while ramp-up
     * @param MerchantEmailEntity $entity
     * @throws \Throwable
     */
    public function __saveOrFail(MerchantEmailEntity $entity)
    {
        $this->repo->transactionOnLiveAndTest(function () use ($entity) {
            $this->repo->saveOrFail($entity);
            $merchantEmailWrapper = new MerchantEmail();
            $merchantEmailWrapper->SaveOrFail($entity);
        });
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
        return $this->newQuery()
                    ->where(Entity::TYPE, $type)
                    ->merchantId($merchantId)
                    ->first();
    }

    /**
     * This function does not check for verification status and
     * hence should not be used for getting emails for communication.
     * this function get all the emails by type except 'partner_dummy' type
     * @param  string  $merchantId
     *
     * @return mixed
     */
    public function getEmailByMerchantId(string $merchantId)
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
     * @param  string  $merchantId
     *
     * @return mixed
     */
    public function __getEmailByMerchantId(string $merchantId)
    {
        return $this->repo->transactionOnLiveAndTest(function () use ($merchantId) {
            $merchantEmails = $this->getEmailByMerchantId($merchantId);
            $merchantEmailWrapper = new MerchantEmail();
            if ($merchantEmailWrapper->isShadowOrReverseShadowOnForOperation($merchantId, "shadow", "read")) {
                $asvEmails = $merchantEmailWrapper->FetchAndCompareMerchantEmailsFromMerchantId($merchantId, $merchantEmails);
                return $merchantEmails;
            }
            if ($merchantEmailWrapper->isShadowOrReverseShadowOnForOperation($merchantId, "reverse_shadow", "read")) {
                $asvEmails = $merchantEmailWrapper->FetchAndCompareMerchantEmailsFromMerchantId($merchantId, $merchantEmails);
                return ASVEntityMapper::OverwriteWithAsvEntities(MerchantEmailEntity::class, 'id', $merchantEmails->toArray(), $asvEmails->toArray());
            }
            return $merchantEmails;
        });
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
        return  $this->newQueryWithConnection($this->getAccountServiceReplicaConnection())
                    ->WhereBetween(Entity::UPDATED_AT, [$from, $to])
                    ->Where(Entity::TYPE, '<>', Type::PARTNER_DUMMY)
                    ->get();
    }
}
