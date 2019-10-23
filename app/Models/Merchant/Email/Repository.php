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
     * @param array $types
     * @param string $merchantId
     * @return mixed
     */
    public function getEmailByTypes(array $types, string $merchantId)
    {
        return $this->newQuery()
                    ->select(Entity::TYPE, Entity::EMAIL)
                    ->merchantId($merchantId)
                    ->whereIn(Entity::TYPE, $types)
                    ->get();
    }
}
