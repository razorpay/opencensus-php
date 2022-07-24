<?php

namespace RZP\Models\Merchant\Email;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

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
     * Returns the emails that were updated in the specified range
     * @param int $from
     * @param int $to
     * @return mixed
     */
    public function getEmailsUpdatedBetween(int $from, int $to)
    {
        return $this->newQueryOnSlave()
                    ->WhereBetween(Entity::UPDATED_AT, [$from, $to])
                    ->Where(Entity::TYPE, '<>', Type::PARTNER_DUMMY)
                    ->get();
    }
}
