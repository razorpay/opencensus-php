<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'item';

    public function findActiveByPublicIdAndMerchant(string $id, Merchant\Entity $merchant)
    {
        $item = $this->findByPublicIdAndMerchant($id, $merchant);

        $item->getValidator()->validateItemIsActive();

        return $item;
    }

    public function findActiveByPublicIdAndMerchantForType(
        string $id,
        Merchant\Entity $merchant,
        string $type)
    {
        $item = $this->findByPublicIdAndMerchantForType($id, $merchant, $type);

        $item->getValidator()->validateItemIsActive();

        return $item;
    }

    public function findByPublicIdAndMerchantForType(
        string $id,
        Merchant\Entity $merchant,
        string $type)
    {
        $item = $this->findByPublicIdAndMerchant($id, $merchant);

        $item->getValidator()->validateItemIsOfType($type);

        return $item;
    }
}
