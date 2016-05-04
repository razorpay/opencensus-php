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
        $refunds = [];

        if (isset($params['notes']))
        {
            $refunds = $this->fetchNotes($this->getEsType(), $params, $merchantId);
        }

        return $refunds;
    }

    public function getEsType()
    {
        return Table::REFUND;
    }
}
