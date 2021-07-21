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

}

