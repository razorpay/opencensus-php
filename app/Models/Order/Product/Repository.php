<?php


namespace RZP\Models\Order\Product;

use RZP\Constants;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = Constants\Entity::PRODUCT;

    public function findByOrderID(string $orderID)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID, $orderID)
            ->get();
    }
}
