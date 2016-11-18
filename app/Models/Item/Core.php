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
     * @param array $input
     * @param Merchant\Entity $merchant
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

    // public function update(Entity $item, array $input)
    // {
    //     $this->checkIfLineItemAssociated($item);

    //     $item->edit($input);

    //     $this->repo->saveOrFail($item);

    //     return $item;
    // }

    // public function delete(Entity $item)
    // {
    //     $this->checkIfLineItemAssociated($item);

    //     $this->repo->item->deleteOrFail($item);

    //     return true;
    // }

    // // -------------------- Protected methods --------------------

    // protected function checkIfLineItemAssociated(Entity $item)
    // {
    //     if ($item->lineItems()->count() > 0)
    //     {
    //         throw new Exception\BadRequestException(ErrorCode::BAD_REQUEST_ITEM_EDIT_NOT_ALLOWED);
    //     }
    // }
}
