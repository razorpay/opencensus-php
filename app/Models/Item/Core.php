<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;

class Core extends Base\Core
{
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
        $traceData = [
            'item_id' => $item->getId(),
            'input'   => $input,
        ];

        $this->trace->info(TraceCode::ITEM_UPDATE_REQUEST, $traceData);

        $item->getValidator()->validateEditOperation($item, $input);

        $item->edit($input);

        $this->repo->saveOrFail($item);

        return $item;
    }

    public function delete(Entity $item)
    {
        $item->getValidator()->validateDeleteOperation($item);

        $traceData = [
            'item_id' => $item->getId(),
        ];

        $this->trace->info(TraceCode::ITEM_DELETE_REQUEST, $traceData);

        return $this->repo->item->deleteOrFail($item);
    }
}
