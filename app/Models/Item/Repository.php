<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Exception\BadRequestException;

class Repository extends Base\Repository
{
    protected $entity = 'item';

    protected $entityFetchParamRules = [
        Entity::ACTIVE      => 'filled|boolean',
    ];

    protected $proxyFetchParamRules = [
        Entity::TYPE        => 'filled|custom'
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'filled|alpha_num|size:14'
    ];

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

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
