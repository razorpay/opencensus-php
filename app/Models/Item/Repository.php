<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Repository extends Base\Repository
{
    protected $entity = 'item';

    // These are merchant allowed params to search on. These also act as default params.
    protected $entityFetchParamRules = [
        Entity::ACTIVE => 'sometimes|boolean',
    ];

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num'
    ];

    /**
     * Finds item with given public id and where status is ACTIVE.
     * If not found, throws exception.
     *
     * @param integer         $id
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     * @throws Exception\BadRequestException
     */
    public function findActiveByPublicIdAndMerchantOrFail($id, Merchant\Entity $merchant)
    {
        $item = $this->findByPublicIdAndMerchant($id, $merchant);

        if ($item->isNotActive())
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ITEM_INACTIVE,
                null,
                [
                    'item_id' => $item->getId(),
                ]);
        }

        return $item;
    }

    public function findByPublicIdAndMerchantForType(string $id, Merchant\Entity $merchant, string $type)
    {
        $item = $this->findByPublicIdAndMerchant($id, $merchant);

        if ($item->getType !== $type)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INCOMPATIBLE_ITEM_TYPE,
                null,
                [
                    'item_id'       => $id,
                    'merchant_id'   => $merchant->getId(),
                    'item_type'     => $item->getType(),
                ]);
        }
    }
}
