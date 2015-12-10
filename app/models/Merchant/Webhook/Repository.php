<?php

namespace Models\Merchant\Webhook;

use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Webhook';

    protected $appFetchParamRules = array(
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
        Entity::ACTIVE      => 'sometimes|in:0,1',
    );

    public function findByMerchant($merchant)
    {
        $repo = $this->repo;

        return $repo::where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->get();
    }

    public function incrementFailureCount($webhook)
    {
        $webhook->incrementFailureCount();
        $webhook->saveOrFail();
    }

    public function findByMerchantId($merchantId)
    {
        $repo = $this->repo;

        return $repo::where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }

    public function resetFailureCount($webhook)
    {
        $webhook->resetFailureCount();
        $webhook->saveOrFail();
    }
}