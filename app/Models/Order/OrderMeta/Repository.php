<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Constants;
use RZP\Models\Base;

/**
 * Class Repository
 *
 * @package RZP\Models\Order\OrderMeta
 */
class Repository extends Base\Repository
{
    /**
     * @var string
     */
    protected $entity = Constants\Entity::ORDER_META;

    public function findByOrderIdAndType($orderId, $type): Entity
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId, 'AND', Entity::TYPE, '=', $type)
            ->first();
    }
}