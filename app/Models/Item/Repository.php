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
        Entity::TYPE        => 'filled|string|custom',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'filled|alpha_num|size:14'
    ];

    protected function validateType($attribute, $value)
    {
        Type::checkType($value);
    }

    public function findActiveByPublicIdAndMerchantForType(
        string $id,
        Merchant\Entity $merchant,
        string $type)
    {
        $item = $this->findByPublicIdAndMerchantForType($id, $merchant, $type);

        if ($item->isNotActive())
        {
            $payload = [
                Entity::ID     => $item->getId(),
                Entity::ACTIVE => $item->isActive(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_ITEM_INACTIVE, null, $payload);
        }

        return $item;
    }

    public function findByPublicIdAndMerchantForType(
        string $id,
        Merchant\Entity $merchant,
        string $type)
    {
        $item = $this->findByPublicIdAndMerchant($id, $merchant);

        if ($item->isNotOfType($type))
        {
            $payload = [
                Entity::ID   => $item->getId(),
                Entity::TYPE => $item->getType(),
            ];

            throw new BadRequestException(ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE, null, $payload);
        }

        return $item;
    }
}
