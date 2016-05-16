<?php

namespace Models\Payment\Refund;

use Constants\Table;
use Models\Base;

class EsRepository extends Base\EsRepository
{
    public function __construct()
    {
        parent::__construct();
    }

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
