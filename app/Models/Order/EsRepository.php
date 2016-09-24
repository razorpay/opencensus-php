<?php

namespace RZP\Models\Order;

use RZP\Models\Base;
use RZP\Constants\Table;

class EsRepository extends Base\EsRepository
{
    public function fetch($params, $merchantId)
    {
        $orders = new Base\PublicCollection;

        if (isset($params['notes']))
        {
            $orders = $this->fetchNotes(Table::ORDER, $params, $merchantId);
        }

        return $orders;
    }
}