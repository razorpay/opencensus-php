<?php

namespace RZP\Models\Order\OrderMeta;

use RZP\Base\ConnectionType;
use RZP\Constants;
use RZP\Models\Base;
use RZP\Models\Order;

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

    public function findByPublicOrderIdAndType(string $publicOrderId, string $type)
    {
        $entity = (new Order\Repository())->getEntityClass();
        $orderId = $entity::verifyIdAndStripSign($publicOrderId);
        return $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId)
            ->where(Entity::TYPE, '=', $type)
            ->first();
    }

    public function findByOrderIdAndType($orderId, $type)
    {
        return $this->newQuery()
            ->where(Entity::ORDER_ID, '=', $orderId, 'AND', Entity::TYPE, '=', $type)
            ->first();
    }

    public function fetchByOrderIdsAndTypeFromTiDB(array $orderIds, string $type)
    {
        $connectionType = $this->getConnectionFromType(ConnectionType::DATA_WAREHOUSE_ADMIN);
        return $this->newQueryWithConnection($connectionType)
            ->where(Entity::TYPE, '=', $type)
            ->whereIn(Entity::ORDER_ID, $orderIds)
            ->get();
    }

    // Get order meta based on type from orders fetched from PG router
    public function getOrderMetaByTypeFromPGOrder($order, $type)
    {
        if (isset($order['order_metas']) !== false)
        {
            foreach ($order['order_metas'] as $orderMeta)
            {
                if ($orderMeta['type'] === $type)
                {
                    return $orderMeta;
                }
            }
        }

        return null;
    }
}
