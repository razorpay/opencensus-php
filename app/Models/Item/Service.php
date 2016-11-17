<?php

namespace RZP\Models\Item;

use RZP\Models\Base;
use RZP\Exception;

class Service extends Base\Service
{
    protected $core;

    public function __construct()
    {
        parent::__construct();

        $this->core = new Core();
    }

    public function create($input)
    {
        $item = $this->core->create($input, $this->merchant);

        return $item->toArrayPublic();
    }

    public function fetch($id)
    {
        Entity::verifyIdAndStripSign($id);

        $item = $this->repo->item->findByIdAndMerchantId($id, $this->merchant->getId());

        return $item->toArrayPublic();
    }

    public function fetchMultiple(array $input)
    {
        $items = $this->repo->item->fetch($input, $this->merchant->getId());

        return $items->toArrayPublic();
    }

    public function put(string $id, array $input)
    {
        Entity::verifyIdAndStripSign($id);

        $item = $this->repo->item->findByIdAndMerchantId($id, $this->merchant->getId());
        $this->checkIfLineItemAssociated($item);

        return $this->core->put($item, $input)->toArrayPublic();
    }

    public function delete(string $id)
    {
        Entity::verifyIdAndStripSign($id);

        $item = $this->repo->item->findByIdAndMerchantId($id, $this->merchant->getId());
        $this->checkIfLineItemAssociated($item);

        $this->repo->item->deleteOrFail($item);
    }


    // -------------------- Protected methods --------------------

    protected function checkIfLineItemAssociated(Entity $item)
    {
        if ($this->repo->line_item->hasByItem($item)) {
            throw new Exception\BadRequestValidationFailureException(
                "You can not edit/delete an item with which invoices has been created already."
            );
        }
    }
}
