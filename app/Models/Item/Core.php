<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
    /**
     * @param array           $input
     * @param Merchant\Entity $merchant
     *
     * @return Entity
     */
    public function create(array $input, Merchant\Entity $merchant)
    {
        $this->trace->info(
            TraceCode::ITEM_CREATE_REQUEST,
            $input
        );

        $item = (new Entity)->build($input);

        $item->merchant()->associate($merchant);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function update(Entity $item, array $input)
    {
        $this->trace->info(
            TraceCode::ITEM_UPDATE_REQUEST,
            [
                'item_id' => $item->getId(),
                'input'   => $input,
            ]);

        $item->edit($input);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function delete(Entity $item)
    {
        $item->getValidator()->validateDeleteOperation($item);

        $this->trace->info(
            TraceCode::ITEM_DELETE_REQUEST,
            [
                'item_id' => $item->getId(),
            ]);

        return $this->repo->item->deleteOrFail($item);
    }

    public function createItemForType(array $input, Merchant\Entity $merchant, string $type): Entity
    {
        if (empty($input[Entity::ITEM_ID]) === false)
        {
            $item = $this->repo->item->findByPublicIdAndMerchantForType(
                                                        $input[Entity::ITEM_ID],
                                                        $merchant, 
                                                        $type);
        }
        else
        {
            $itemInput = [
                Entity::NAME       => $input[Entity::NAME],
                Entity::AMOUNT     => $input[Entity::AMOUNT],
                Entity::CURRENCY   => $input[Entity::CURRENCY],
                Entity::TYPE       => $type,
            ];

            $item = $this->create($itemInput, $merchant);
        }

        return $item;
    }
}
