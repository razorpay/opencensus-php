<?php

namespace Models\Merchant\Webhook;

use Models\Base;
use Models\Merchant;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function findByMerchant($merchant)
    {
        $repo = $this->repo;

        return $repo->where(Entity::MERCHANT_ID, '=', $merchant->getId())
                    ->get();
    }

    public function findByIdAndMerchantId($webhookId, $merchantId)
    {
        $repo = $this->repo;

        return $repo->where(Entity::ID, '=', $webhookId)
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->findOrFailPublic();
    }
}