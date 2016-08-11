<?php

namespace RZP\Models\Merchant\Webhook;

use RZP\Models\Base;
use RZP\Models\Merchant;

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
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->get();
    }

    public function bumpFailureCount($webhook)
    {
        $webhook->bumpFailureCount();
        $webhook->saveOrFail();
    }

    public function findByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->first();
    }

    public function resetFailureCount($webhook)
    {
        $webhook->resetFailureCount();
        $webhook->saveOrFail();
    }

    public function setLastSuccessfulAt($webhook)
    {
        $webhook->setLastSuccessfulAt();
        $webhook->saveOrFail();
    }
}