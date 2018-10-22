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
     * @param  string $type
     * @param  string $email
     * @param  string $merchantId
     *
     * @return Entity
     */
    public function getByTypeEmailAndMerchantId(string $type, string $email, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, $type)
                    ->where(Entity::EMAIL, $email)
                    ->merchantId($merchantId)
                    ->first();
    }
}
