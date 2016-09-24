<?php

namespace RZP\Models\Payment\Refund;

use RZP\Constants\Table;
use RZP\Models\Base;

class EsRepository extends Base\EsRepository
{
    public function fetch($params, $merchantId)
    {
        $refunds = new Base\PublicCollection;;

        if (isset($params['notes']))
        {
            $refunds = $this->fetchNotes(Table::REFUND, $params, $merchantId);
        }

        return $refunds;
    }
}
